<?php

namespace Tests\Feature;

use Anthropic\Client;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Assistant\AssistantConversation;
use App\Services\Assistant\AssistantTools;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Fakes\FakeClaudeTransport;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $max;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 14:00:00');
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->max = User::factory()->member()->create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $this->team->athletes()->attach($this->max);
        $this->actingAs($this->user);

        // Never reach the real API from a test, whatever the local .env says
        config(['services.anthropic.key' => null]);
        $this->app->bind(Client::class, fn () => throw new RuntimeException('Real Claude API used in a test'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enableAssistant(): FakeClaudeTransport
    {
        config(['services.anthropic.key' => 'test-key']);
        $claude = new FakeClaudeTransport();
        $this->app->instance(Client::class, $claude->client());
        return $claude;
    }

    private function ask(string $question)
    {
        return $this->from(route('assistant'))->post(route('assistant.ask'), ['question' => $question]);
    }

    private function draft(array $attributes = [], ?Team $team = null): array
    {
        $conversation = AssistantConversation::for($team ?? $this->team);
        $conversation->add('Notes to tasks', 'Here are two drafts.', [array_merge([
            'title' => 'Book the hall',
            'notes' => 'Ask for the big room',
            'priority' => 'high',
            'due_date' => '2026-10-05',
            'assignee_id' => $this->max->id,
            'assignee_name' => 'Max Muster',
        ], $attributes)]);
        return last($conversation->exchanges())['drafts'][0];
    }

    public function test_review_link_prefills_the_task_form_from_a_draft()
    {
        $draft = $this->draft();

        $response = $this->get(route('tasks.create', ['draft' => $draft['id']]))->assertOk();

        $this->assertSame([
            'title' => 'Book the hall',
            'notes' => 'Ask for the big room',
            'due_date' => '05/10/2026',
            'priority' => 'high',
            'assignee_id' => $this->max->id,
        ], $response->viewData('prefill'));
        $response->assertSee('name="assistant_draft" value="' . $draft['id'] . '"', false)
            ->assertSee('value="Book the hall"', false);
    }

    public function test_saving_a_draft_creates_the_task_and_marks_the_draft()
    {
        $draft = $this->draft();

        $this->post(route('tasks.store'), [
            'title' => 'Book the hall',
            'priority' => 'high',
            'assignee_id' => $this->max->id,
            'assistant_draft' => (string) $draft['id'],
        ])->assertRedirect(route('assistant') . '#latest');

        $task = Task::where('title', 'Book the hall')->firstOrFail();
        $this->assertSame($task->id, AssistantConversation::for($this->team)->draft($draft['id'])['task_id']);

        // A created draft is not offered again, so it cannot be saved twice by accident
        $this->get(route('tasks.create', ['draft' => $draft['id']]))->assertViewHas('prefill', []);
        $this->post(route('tasks.store'), ['title' => 'Again', 'assistant_draft' => (string) $draft['id']])
            ->assertRedirect(route('tasks.index'));
        $this->assertSame($task->id, AssistantConversation::for($this->team)->draft($draft['id'])['task_id']);
    }

    public function test_drafts_of_other_teams_and_gone_members_are_ignored()
    {
        $otherTeam = Team::factory()->create(['user_id' => $this->user->id]);
        $foreign = $this->draft(['title' => 'Other team draft'], $otherTeam);
        $this->get(route('tasks.create', ['draft' => $foreign['id']]))->assertViewHas('prefill', []);

        $this->team->athletes()->updateExistingPivot($this->max->id, ['active' => false]);
        $draft = $this->draft();
        $prefill = $this->get(route('tasks.create', ['draft' => $draft['id']]))->viewData('prefill');
        $this->assertNull($prefill['assignee_id'], 'An inactive member is no longer assignable');

        $this->get(route('tasks.create', ['draft' => 'abc']))->assertOk()->assertViewHas('prefill', []);
    }

    public function test_assistant_is_off_without_an_api_key()
    {
        $this->get(route('assistant'))->assertOk()
            ->assertSee('The assistant is off')
            ->assertDontSee('name="question"', false);
        $this->ask('What is overdue?')->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee('auto_awesome');
    }

    public function test_question_runs_team_scoped_tools_and_shows_the_answer()
    {
        $claude = $this->enableAssistant();
        $overdue = ['team_id' => $this->team->id, 'due_date' => '2026-09-20'];
        Task::factory()->create($overdue + ['title' => 'Renew gym insurance', 'assignee_id' => $this->max->id]);
        Task::factory()->create($overdue + ['title' => 'Already done', 'status' => 'done']);
        Task::factory()->create(['title' => 'Foreign secret task', 'due_date' => '2026-09-20']);
        $claude->toolCall('find_tasks', ['filter' => 'overdue'])
            ->text('One task is overdue: Renew gym insurance (Max Muster).');

        $this->ask('What is overdue?')->assertRedirect(route('assistant') . '#latest');

        $this->get(route('assistant'))->assertOk()
            ->assertSee('What is overdue?')
            ->assertSee('One task is overdue: Renew gym insurance (Max Muster).');
        $this->assertCount(2, $claude->requests);

        $first = $claude->body(0);
        $this->assertSame('claude-opus-5', $first['model']);
        $this->assertSame(['type' => 'adaptive'], $first['thinking']);
        $this->assertSame('default', $first['fallbacks']);
        $betas = $claude->requests[0]->getHeaderLine('anthropic-beta');
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $betas);
        $this->assertSame('test-key', $claude->requests[0]->getHeaderLine('x-api-key'));
        $this->assertSame(['type' => 'ephemeral'], $first['system'][0]['cache_control']);
        $this->assertStringContainsString("Active team: {$this->team->name}", $first['system'][1]['text']);
        $this->assertStringContainsString('Wednesday, 30 September 2026', $first['system'][1]['text']);
        foreach ($first['tools'] as $tool) {
            $this->assertTrue($tool['strict'], "{$tool['name']} is strict");
            $this->assertFalse($tool['input_schema']['additionalProperties']);
        }

        $second = $claude->body(1);
        $this->assertSame('tool_use', $second['messages'][1]['content'][0]['type']);
        [$result] = $claude->toolResults(1);
        $this->assertSame('toolu_1', $result['tool_use_id']);
        $tasks = json_decode($result['content'], true);
        $this->assertSame(1, $tasks['total']);
        $this->assertSame('Renew gym insurance', $tasks['tasks'][0]['title']);
        $this->assertSame('Max Muster', $tasks['tasks'][0]['assignee']);
        $this->assertStringNotContainsString('Foreign secret task', json_encode($claude->body(1)));
    }

    public function test_tool_errors_are_reported_back_to_claude()
    {
        $claude = $this->enableAssistant();
        $claude->push([
            ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'find_tasks', 'input' => ['filter' => 'everything']],
            ['type' => 'tool_use', 'id' => 'toolu_2', 'name' => 'delete_everything', 'input' => []],
        ], 'tool_use')->text('I could not look that up.');

        $this->ask('Show me everything');

        $results = $claude->toolResults(1);
        $this->assertCount(2, $results, 'All results go back in one message');
        $this->assertTrue($results[0]['is_error']);
        $this->assertStringContainsString('filter must be one of', $results[0]['content']);
        $this->assertTrue($results[1]['is_error']);
        $this->assertStringContainsString('Unknown tool', $results[1]['content']);
    }

    public function test_follow_up_questions_carry_earlier_answers_but_not_refusals()
    {
        $claude = $this->enableAssistant();
        $refusal = ['type' => 'refusal', 'category' => null, 'explanation' => null];
        $claude->push([], 'refusal', ['stop_details' => $refusal])
            ->text('Max has no open tasks.')
            ->text('He is free on Tuesday.');

        $this->ask('Something odd');
        $this->get(route('assistant'))->assertSee("The assistant can't help with this request.");

        $this->ask('What is Max working on?');
        $this->assertCount(1, $claude->body(1)['messages'], 'The refused exchange is not sent again');

        $this->ask('And when is he free?');
        $this->assertSame(
            ['What is Max working on?', 'Max has no open tasks.', 'And when is he free?'],
            array_column($claude->body(2)['messages'], 'content')
        );
    }

    public function test_drafts_from_meeting_notes_are_listed_for_review_without_creating_tasks()
    {
        $claude = $this->enableAssistant();
        $otherTeam = Team::factory()->create();
        $otherTeam->athletes()->attach(User::factory()->member()->create(['first_name' => 'Stranger']));
        $claude->toolCall('draft_tasks', ['tasks' => [
            ['title' => 'Book the hall', 'assignee' => 'Max', 'due_date' => '2026-10-05', 'priority' => 'high'],
            ['title' => 'Call the stranger', 'assignee' => 'Stranger'],
            ['title' => 'Order gloves', 'due_date' => '2026-02-30'],
        ]])->text('I drafted three tasks for you to review.');

        $this->ask('Meeting notes: Max books the hall by Oct 5, call Stranger, order gloves.');

        $this->assertSame(0, Task::count());
        $drafts = last(AssistantConversation::for($this->team)->exchanges())['drafts'];
        $this->assertSame([$this->max->id, null, null], array_column($drafts, 'assignee_id'));
        $this->assertSame(['2026-10-05', null, null], array_column($drafts, 'due_date'));
        $this->assertSame(['high', 'medium', 'medium'], array_column($drafts, 'priority'));

        $notes = json_decode($claude->toolResults(1)[0]['content'], true)['notes'];
        $this->assertCount(2, $notes, 'Unknown member and impossible date are reported');

        $this->get(route('assistant'))->assertSee('Book the hall')
            ->assertSee(route('tasks.create', ['draft' => $drafts[0]['id']]), false);
    }

    public function test_api_failure_keeps_the_question_for_a_retry()
    {
        $claude = $this->enableAssistant();
        $claude->error(500);

        $this->ask('What is overdue?')
            ->assertRedirect(route('assistant'))
            ->assertSessionHasErrors([
                'question' => 'The assistant is not reachable right now. Please try again in a moment.',
            ])
            ->assertSessionHasInput('question', 'What is overdue?');
        $this->assertSame([], AssistantConversation::for($this->team)->exchanges());
    }

    public function test_endless_tool_use_and_cut_off_answers_are_flagged()
    {
        $claude = $this->enableAssistant();
        foreach (range(1, 6) as $round) {
            $claude->toolCall('find_tasks', ['filter' => 'open'], "toolu_{$round}");
        }
        $this->ask('Loop forever');
        $this->assertCount(6, $claude->requests);
        $lastAnswer = fn () => last(AssistantConversation::for($this->team)->exchanges())['answer'];
        $this->assertStringContainsString('too many lookups', $lastAnswer());

        $claude->text('Here is a very long', 'max_tokens');
        $this->ask('Tell me everything');
        $this->assertStringEndsWith('(The answer was cut off. Ask a narrower question.)', $lastAnswer());
    }

    public function test_member_lookups_leave_out_contact_and_body_data()
    {
        $this->max->userDetail()->create([
            'nick_name' => 'Maxi',
            'weight' => 81.5,
            'height' => 184,
            'date_of_birth' => '1999-04-12',
            'about' => 'Private note',
            'experience_level' => 'advanced',
        ]);
        $boxing = $this->team->skills()->create(['name' => 'Boxing']);
        $this->max->skills()->attach($boxing, ['level' => 'expert']);

        $tools = new AssistantTools($this->user, $this->team);
        $json = $tools->run('find_members', ['role' => 'all', 'name' => 'maxi']);

        $member = json_decode($json, true)['members'][0];
        $fields = ['name', 'role', 'experience_level', 'skills', 'available', 'open_tasks'];
        $this->assertSame($fields, array_keys($member));
        $this->assertSame(['Boxing (expert)'], $member['skills']);
        foreach ([$this->max->email, '81.5', '184', '1999', 'Private note'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
    }

    public function test_sparring_suggestions_need_one_active_athlete_of_the_team()
    {
        $tools = new AssistantTools($this->user, $this->team);
        $anna = User::factory()->member()->create(['first_name' => 'Anna', 'last_name' => 'Alpha']);
        $this->team->athletes()->attach($anna);
        $annaBeta = User::factory()->member()->create(['first_name' => 'Anna', 'last_name' => 'Beta']);
        $this->team->athletes()->attach($annaBeta);
        Team::factory()->create()->athletes()->attach(User::factory()->member()->create(['first_name' => 'Stranger']));

        $suggest = fn ($name) => json_decode($tools->run('suggest_sparring_partners', ['athlete' => $name]), true);

        $this->assertSame('No active athlete matches “Stranger”.', $suggest('Stranger')['error']);
        $this->assertSame(['Anna Alpha', 'Anna Beta'], $suggest('anna')['matches']);

        $result = $suggest('Anna Alpha');
        $this->assertSame('Anna Alpha', $result['athlete']);
        $this->assertEqualsCanonicalizing(['Anna Beta', 'Max Muster'], array_column($result['suggestions'], 'partner'));
    }

    public function test_sparring_lookup_is_limited_to_the_team_and_date_range()
    {
        $ours = ['team_id' => $this->team->id];
        Schedule::factory()->create($ours + ['title' => 'Tuesday sparring', 'date' => '2026-10-06'])
            ->participants()->attach($this->max);
        Schedule::factory()->create($ours + ['title' => 'Far future', 'date' => '2026-12-01']);
        Schedule::factory()->create(['title' => 'Other team session', 'date' => '2026-10-02']);

        $tools = new AssistantTools($this->user, $this->team);

        $all = json_decode($tools->run('find_sparrings', ['range' => 'next_7_days']), true);
        $this->assertSame(['Tuesday sparring'], array_column($all['sessions'], 'title'));

        $mine = json_decode($tools->run('find_sparrings', ['range' => 'next_30_days', 'athlete' => 'max']), true);
        $this->assertSame(['Tuesday sparring'], array_column($mine['sessions'], 'title'));
        $this->assertSame(['Max Muster'], $mine['sessions'][0]['participants']);
    }
}
