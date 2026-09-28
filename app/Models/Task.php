<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    public const STATUSES = [
        'backlog'     => 'Backlog',
        'todo'        => 'Todo',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    public const PRIORITIES = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ];

    protected $table = 'tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assignee_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'done');
    }

    /**
     * Tasks with a deadline inside the given date range (inclusive).
     */
    public function scopeDueBetween($query, Carbon $from, Carbon $to)
    {
        return $query->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $to->toDateString());
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw(
            "CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END"
        );
    }

    /**
     * Change the workflow status and keep completed_at in sync with it.
     */
    public function moveTo(string $status): void
    {
        $this->status = $status;
        if ($status === 'done') {
            $this->completed_at = $this->completed_at ?? now();
        } else {
            $this->completed_at = null;
        }
        $this->save();
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent'], true);
    }

    public function isOverdue(): bool
    {
        return !$this->isDone()
            && $this->due_date
            && Carbon::parse($this->due_date)->startOfDay()->lt(Carbon::today());
    }

    public function isDueToday(): bool
    {
        return $this->due_date && Carbon::parse($this->due_date)->isToday();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getDueDateFormatAttribute(): ?string
    {
        return $this->due_date ? Carbon::parse($this->due_date)->format('d/m/Y') : null;
    }

    public function getDueLabelAttribute(): ?string
    {
        if (!$this->due_date) return null;
        $d = Carbon::parse($this->due_date);
        if ($d->isToday()) return 'Today';
        if ($d->isYesterday()) return 'Yesterday';
        if ($d->isTomorrow()) return 'Tomorrow';
        return $d->format('d M');
    }
}
