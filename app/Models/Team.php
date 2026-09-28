<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

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
        )->using(TeamMembership::class)->withPivot('id', 'active');
    }

    public function athletes()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'team_athlete'
        )->using(TeamMembership::class)->withPivot('id', 'active');
    }
    public function activeCoaches()
    {
        return $this->coaches()->wherePivot('active', true);
    }

    public function activeAthletes()
    {
        return $this->athletes()->wherePivot('active', true);
    }

    public function skills()
    {
        return $this->hasMany(\App\Models\Skill::class)->orderBy('name');
    }

    /**
     * People work can be assigned to: the account holder plus the team's active coaches and athletes.
     */
    public function assignablePeople(User $manager)
    {
        return collect([$manager])
            ->merge($this->activeCoaches()->get())
            ->merge($this->activeAthletes()->get())
            ->unique('id')->sortBy('full_name')->values();
    }

    public function projects()
    {
        return $this->hasMany(\App\Models\Project::class);
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
