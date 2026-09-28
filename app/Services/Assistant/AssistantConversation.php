<?php

namespace App\Services\Assistant;

use App\Models\Team;

/**
 * The assistant chat of one team, kept in the session: questions, answers and the task
 * drafts the assistant proposed. Nothing is stored in the database until the user
 * creates a draft through the normal task form.
 */
class AssistantConversation
{
    private const MAX_EXCHANGES = 10;

    /** Earlier exchanges sent along as context for follow-up questions */
    private const CONTEXT_EXCHANGES = 4;

    private string $key;

    public function __construct(Team $team)
    {
        $this->key = "assistant.team.{$team->id}";
    }

    public static function for(Team $team): self
    {
        return new self($team);
    }

    /**
     * @return array<int, array{question: string, answer: string, failed: bool, drafts: array}>
     */
    public function exchanges(): array
    {
        return session("{$this->key}.exchanges", []);
    }

    /**
     * Earlier questions and answers as plain-text messages for the API.
     */
    public function contextMessages(): array
    {
        return collect($this->exchanges())
            ->reject(fn ($exchange) => $exchange['failed'])
            ->take(-self::CONTEXT_EXCHANGES)
            ->flatMap(fn ($exchange) => [
                ['role' => 'user', 'content' => $exchange['question']],
                ['role' => 'assistant', 'content' => $exchange['answer']],
            ])
            ->values()->all();
    }

    public function add(string $question, string $answer, array $drafts = [], bool $failed = false): void
    {
        $nextId = session("{$this->key}.next_draft_id", 1);
        $drafts = array_map(function ($draft) use (&$nextId) {
            return ['id' => $nextId++, 'task_id' => null] + $draft;
        }, $drafts);

        $exchanges = $this->exchanges();
        $exchanges[] = compact('question', 'answer', 'failed', 'drafts');
        session([
            "{$this->key}.exchanges" => array_slice($exchanges, -self::MAX_EXCHANGES),
            "{$this->key}.next_draft_id" => $nextId,
        ]);
    }

    public function draft(int $id): ?array
    {
        foreach ($this->exchanges() as $exchange) {
            foreach ($exchange['drafts'] as $draft) {
                if ($draft['id'] === $id) {
                    return $draft;
                }
            }
        }
        return null;
    }

    public function markDraftCreated(int $id, int $taskId): void
    {
        $exchanges = $this->exchanges();
        foreach ($exchanges as &$exchange) {
            foreach ($exchange['drafts'] as &$draft) {
                if ($draft['id'] === $id) {
                    $draft['task_id'] = $taskId;
                }
            }
        }
        unset($exchange, $draft);
        session(["{$this->key}.exchanges" => $exchanges]);
    }

    public function clear(): void
    {
        session()->forget($this->key);
    }
}
