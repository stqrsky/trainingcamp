<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'skills';

    public const LEVELS = [
        'beginner'     => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced'     => 'Advanced',
        'expert'       => 'Expert',
    ];

    protected $guarded = ['id'];

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'user_skill'
        )->withPivot('level');
    }
}
