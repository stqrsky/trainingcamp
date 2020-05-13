<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\Role::class,
            'user_role',
            'user_id',
            'role_id'
        );
    }
    public function coachTeam()
    {
        return $this->belongsToMany(
            \App\Models\Team::class,
            'team_coach',
            'user_id',
            'team_id'
        );
    }

    public function athleteTeam()
    {
        return $this->belongsToMany(
            \App\Models\Team::class,
            'team_athlete',
            'user_id',
            'team_id'
        );
    }

    public function skills()
    {
        return $this->belongsToMany(
            \App\Models\Skill::class,
            'user_skill',
            'user_id',
            'skill_id'
        );
    }

    public function image()
    {
        return $this->belongsTo(\App\Models\Image::class, 'image_id');
    }

    public function participants()
    {
        return $this->belongsToMany(
            \App\Models\Schedule::class,
            'schedule_participant',
            'user_id',
            'schedule_id'
        );
    }
}
