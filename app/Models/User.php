<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

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

    public function setPasswordAttribute($password)
    {
        return $this->attributes['password'] = \Hash::make($password);
    }

    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\Role::class,
            'user_role'
        );
    }

    public function getRolesNameAttribute()
    {
        $roles = $this->roles;
        $roles_name = collect($roles)->map(function ($role) {
            return $role->title;
        })->all();
        return $roles_name;
    }

    public function team()
    {
        return $this->hasOne(\App\Models\Team::class);
    }

    public function athleteTeam()
    {
        return $this->belongsToMany(
            \App\Models\Team::class,
            'team_athlete'
        );
    }

    public function skills()
    {
        return $this->belongsToMany(
            \App\Models\Skill::class,
            'user_skill'
        );
    }

    public function userDetail()
    {
        return $this->hasOne(\App\Models\UserDetail::class);
    }

    public function participants()
    {
        return $this->belongsToMany(
            \App\Models\Schedule::class,
            'schedule_participant'
        );
    }

    public function getFullNameAttribute()
    {
        $first_name = $this->first_name;
        $last_name = $this->last_name;
        return "$first_name $last_name";
    }
}
