<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
