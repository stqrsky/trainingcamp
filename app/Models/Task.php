<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

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

    public function isOverdue(): bool
    {
        return $this->status == 0
            && $this->due_date
            && Carbon::parse($this->due_date)->startOfDay()->lt(Carbon::today());
    }

    public function isDueToday(): bool
    {
        return $this->due_date && Carbon::parse($this->due_date)->isToday();
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
