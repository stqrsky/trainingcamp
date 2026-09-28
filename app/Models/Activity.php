<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Record an activity for a team; the signed-in user is the actor.
     */
    public static function record(?int $teamId, string $action, string $description, ?Model $subject = null): void
    {
        if (!$teamId) {
            return;
        }
        static::create([
            'team_id'      => $teamId,
            'user_id'      => Auth::id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'action'       => $action,
            'description'  => $description,
        ]);
    }

    public function getIconAttribute(): string
    {
        return match (strtok($this->action, '.')) {
            'task'     => 'task_alt',
            'sparring' => 'sports_kabaddi',
            'member'   => 'person',
            'post'     => 'campaign',
            default    => 'groups',
        };
    }

    /**
     * Where the activity points to, if its subject still exists and belongs to the same team.
     */
    public function getUrlAttribute(): ?string
    {
        $subject = $this->subject;
        return match (true) {
            $subject instanceof Task => route('tasks.edit', $subject),
            $subject instanceof Schedule => route('schedules.index', ['date' => $subject->date_format]),
            $subject instanceof Notification => route('notification.edit', $subject),
            default => null,
        };
    }

    public function getActorNameAttribute(): string
    {
        if (!$this->user_id) {
            return 'Someone';
        }
        return $this->user_id === Auth::id() ? 'You' : ($this->actor?->full_name ?? 'Someone');
    }
}
