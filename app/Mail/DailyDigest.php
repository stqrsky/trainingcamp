<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Morning summary per account: reminders and today's sparrings for each owned team.
 */
class DailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{team: Team, reminders: array, sparrings: Collection}> $sections
     */
    public function __construct(public User $user, public array $sections)
    {
    }

    public function envelope(): Envelope
    {
        $count = collect($this->sections)
            ->sum(fn ($section) => $section['reminders']['count'] + $section['sparrings']->count());
        return new Envelope(subject: "Trainingcamp today: {$count} " . ($count === 1 ? 'item' : 'items'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.daily-digest');
    }
}
