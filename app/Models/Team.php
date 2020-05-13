<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'teams';

    protected $guarded = ['id'];

    public function coaches()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'team_coach',
            'team_id',
            'user_id'
        );
    }

    public function athletes()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'team_athlete',
            'team_id',
            'user_id'
        );
    }

    public function image()
    {
        return $this->belongsTo(\App\Models\Image::class, 'image_id');
    }
}
