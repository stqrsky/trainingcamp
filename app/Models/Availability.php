<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    public const WEEKDAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getStartAttribute($start)
    {
        return Carbon::parse($start)->format('H:i');
    }

    public function getEndAttribute($end)
    {
        return Carbon::parse($end)->format('H:i');
    }

    public function getLabelAttribute(): string
    {
        return substr(self::WEEKDAYS[$this->weekday] ?? '', 0, 3) . " {$this->start}–{$this->end}";
    }
}
