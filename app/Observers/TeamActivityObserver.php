<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Notification;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;

/**
 * Team-level events for the activity feed: new teams, announcements and membership changes.
 */
class TeamActivityObserver
{
    public function teamCreated(Team $team): void
    {
        Activity::record($team->id, 'team.created', "created the team “{$team->name}”", $team);
    }

    public function postCreated(Notification $post): void
    {
        Activity::record($post->team_id, 'post.created', "posted the announcement “{$post->title}”", $post);
    }

    public function membershipCreated(TeamMembership $membership): void
    {
        if ($this->isOwner($membership)) {
            return;
        }
        $description = "added {$this->name($membership)} as {$membership->role()}";
        Activity::record($membership->team_id, 'member.added', $description);
    }

    public function membershipUpdated(TeamMembership $membership): void
    {
        if ($membership->wasChanged('active') && !$this->isOwner($membership)) {
            $state = $membership->active ? 'active' : 'inactive';
            Activity::record($membership->team_id, 'member.status', "set {$this->name($membership)} {$state}");
        }
    }

    public function membershipDeleted(TeamMembership $membership): void
    {
        if ($this->isOwner($membership)) {
            return;
        }
        Activity::record($membership->team_id, 'member.removed', "removed {$this->name($membership)} from the team");
    }

    private function name(TeamMembership $membership): string
    {
        return User::find($membership->user_id)?->full_name ?? 'a member';
    }

    // The account owner joins their own team as coach when it is created; that is not news
    private function isOwner(TeamMembership $membership): bool
    {
        return Team::whereKey($membership->team_id)->where('user_id', $membership->user_id)->exists();
    }
}
