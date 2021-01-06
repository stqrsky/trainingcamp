<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'teams';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function coaches()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'team_coach'
        );
    }

    public function athletes()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'team_athlete'
        );
    }
    public function schedules()
    {
        return $this->hasMany(\App\Models\Schedule::class);
    }

    public function image()
    {
        return $this->belongsTo(\App\Models\Image::class, 'image_id');
    }
}
