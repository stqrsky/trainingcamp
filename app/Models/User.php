<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'login_enabled' => 'boolean',
        'notification_preferences' => 'array',
    ];

    public function setPasswordAttribute($password)
    {
        if ($password === null || $password === '') {
            return;
        }

        if (is_string($password) && password_get_info($password)['algoName'] !== 'unknown') {
            $this->attributes['password'] = $password;

            return;
        }

        $this->attributes['password'] = \Hash::make($password);
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

    public function teams()
    {
        return $this->hasMany(\App\Models\Team::class);
    }

    /**
     * The team the user is working in. Falls back to the oldest owned team
     * when nothing is selected or the stored id is not one of the user's teams.
     */
    public function currentTeam(): ?Team
    {
        $team = $this->current_team_id ? $this->teams()->find($this->current_team_id) : null;

        return $team ?? $this->teams()->orderBy('id')->first();
    }

    public function switchTeam(Team $team): void
    {
        $this->update(['current_team_id' => $team->id]);
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
        )->withPivot('level');
    }

    public function availabilities()
    {
        return $this->hasMany(\App\Models\Availability::class)->orderBy('weekday')->orderBy('start');
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

    /**
     * Every word must match the first name, last name or nickname, so "Max Mus" finds Max Muster.
     */
    public function scopeMatchingName($query, string $search)
    {
        foreach (preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function ($name) use ($term) {
                $name->where('users.first_name', 'like', "%$term%")
                    ->orWhere('users.last_name', 'like', "%$term%")
                    ->orWhereHas('userDetail', fn ($detail) => $detail->where('nick_name', 'like', "%$term%"));
            });
        }
        return $query;
    }

    /**
     * Reminder types are on unless the user switched them off in the notification settings.
     */
    public function wantsReminder(string $type): bool
    {
        return (bool) ($this->notification_preferences[$type] ?? true);
    }

    public function getInitialsAttribute(): string
    {
        $parts = array_filter(explode(' ', trim("{$this->first_name} {$this->last_name}")));
        $initials = collect($parts)->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

        return $initials ?: '?';
    }

    public function getFullNameAttribute()
    {
        $first_name = $this->first_name ?? '';
        $last_name = $this->last_name ?? '';
        return trim("$first_name $last_name") ?: $this->email;
    }
}
