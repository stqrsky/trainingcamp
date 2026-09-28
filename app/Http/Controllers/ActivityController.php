<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Carbon\Carbon;

class ActivityController extends Controller
{
    private const PER_PAGE = 30;

    public function index()
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $activities = Activity::with(['actor', 'subject'])->where('team_id', $team->id)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE);
        $days = $activities->getCollection()->groupBy(fn ($activity) => $activity->created_at->toDateString());
        return view('frontend.activity.index', compact('activities', 'days'));
    }
}
