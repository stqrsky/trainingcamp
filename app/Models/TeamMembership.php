<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Row of team_athlete or team_coach. A custom pivot class so attach, detach and
 * status changes fire model events (used for the activity feed).
 */
class TeamMembership extends Pivot
{
    public $incrementing = true;

    public $timestamps = false;

    public function role(): string
    {
        return $this->getTable() === 'team_coach' ? 'coach' : 'athlete';
    }
}
