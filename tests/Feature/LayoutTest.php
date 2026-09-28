<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Team::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);
    }

    public function test_navigation_marks_the_current_section_in_sidebar_and_bottom_nav()
    {
        $html = $this->get(route('tasks.index', ['view' => 'board']))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'aria-current="page"'), 'Sidebar and bottom nav each mark one item');
        $this->assertMatchesRegularExpression('/href="[^"]*\/tasks" class="active"\s+aria-current="page"/', $html);
        $this->assertStringContainsString('<span class="tc-nav-label">Tasks</span>', $html);
    }

    public function test_layout_has_skip_link_main_landmark_and_no_legacy_bundle()
    {
        $this->get(route('home'))
            ->assertSee('href="#main"', false)
            ->assertSee('<main id="main"', false)
            ->assertDontSee('js/app.js', false);
    }

    public function test_team_pages_highlight_the_athletes_section()
    {
        $html = $this->get(route('teams.edit'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/href="[^"]*\/user\/athletes" class="active"\s+aria-current="page"/', $html);
    }
}
