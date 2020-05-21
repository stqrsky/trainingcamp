<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    protected $table = 'schedules';

    protected $guarded = ['id'];

    public function participants()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'schedule_participant'
        );
    }

    public function getStartAttribute($start)
    {
        return Carbon::parse($start)->format('H:i');
    }

    public function getEndAttribute($end)
    {
        return Carbon::parse($end)->format('H:i');
    }

    public function getDateFormatAttribute()
    {
        return Carbon::parse($this->date)->format('d/m/Y');
    }

    public function getStartEndTimeAttribute()
    {
        $start = Carbon::parse($this->start)->format('h:i a');
        $end = Carbon::parse($this->end)->format('h:i a');
        return "$start - $end";
    }
}
