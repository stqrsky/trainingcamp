<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'skills';

    protected $guarded = ['id'];

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'user_skill'
        );
    }
}
