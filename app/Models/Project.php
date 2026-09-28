<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public const STATUSES = [
        'planning'  => 'Planning',
        'active'    => 'Active',
        'on_hold'   => 'On hold',
        'completed' => 'Completed',
    ];

    protected $guarded = ['id'];

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    /**
     * Share of done tasks in percent, from withCount(['tasks', 'tasks as done_tasks_count' => …]) when loaded.
     */
    public function getProgressAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->done_tasks_count ?? $this->tasks()->where('status', 'done')->count();
        return $total ? (int) round($done / $total * 100) : 0;
    }

    /**
     * People working on the project: its owner plus everyone assigned to one of its tasks.
     */
    public function getMembersAttribute()
    {
        $assignees = User::whereIn('id', $this->tasks()->whereNotNull('assignee_id')->select('assignee_id'))->get();
        return collect([$this->owner])->filter()->merge($assignees)->unique('id')->values();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getDeadlineFormatAttribute(): ?string
    {
        return $this->deadline ? Carbon::parse($this->deadline)->format('d/m/Y') : null;
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->deadline
            && Carbon::parse($this->deadline)->lt(Carbon::today());
    }
}
