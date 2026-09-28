<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    private const COLORS = ['blue', 'green', 'red', 'orange', 'purple', 'pink', 'teal', 'amber'];
    private const VIDEO_TYPES = ['google_meet', 'zoom', 'gotomeeting'];

    public function index(Request $request)
    {
        $date = ($this->dateFromQuery($request, 'date', 'd/m/Y') ?? Carbon::now())->format('Y-m-d');
        $team = $this->currentTeam();
        $schedules = [];
        if ($team) {
            $schedules = $team->schedules()->whereDate('date', $date)->with([
                'participants',
                'participants.userDetail',
                'participants.userDetail.image',
            ])->orderBy('start', 'DESC')->get();
        }
        $date_format = Carbon::parse($date)->format('l, j F Y');
        $date = Carbon::parse($date)->format('d/m/Y');
        return view('frontend.schedules.schedules', compact('date', 'date_format', 'schedules'));
    }

    public function create()
    {
        $team = $this->currentTeam();
        $athletes = [];
        if ($team) {
            $athletes = $team->athletes;
        }
        return view('frontend.schedules.create', compact('athletes'));
    }

    private function validateRequest($request, $team)
    {
        $teamAthlete = Rule::exists('team_athlete', 'user_id')->where('team_id', $team->id);
        $this->validate($request, [
            'date'           => 'required|date_format:d/m/Y',
            'start'          => 'required|date_format:H:i',
            'end'            => 'required|date_format:H:i|after:start',
            'first_athlete'  => ['required', $teamAthlete],
            'second_athlete' => ['required', $teamAthlete, 'different:first_athlete'],
            'title'          => 'nullable|string|max:255',
            'location'       => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:5000',
            'color'          => 'nullable|in:' . implode(',', self::COLORS),
            'video_type'     => 'nullable|in:' . implode(',', self::VIDEO_TYPES),
            'video_url'      => 'nullable|url|max:512',
        ]);
    }

    /**
     * Parse a date query parameter; invalid or missing values fall back to null
     * so a malformed URL shows the default view instead of a server error.
     */
    private function dateFromQuery(Request $request, string $key, string $format): ?Carbon
    {
        $value = $request->query($key);
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat($format, $value);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function store(Request $request)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $this->validateRequest($request, $team);
        DB::beginTransaction();
        try {
            $date = Carbon::createFromFormat('d/m/Y', $request->input('date'))->format('Y-m-d');
            $schedule = Schedule::create([
                'team_id'    => $team->id,
                'user_id'    => Auth::id(),
                'title'      => $request->input('title') ?: null,
                'location'   => $request->input('location') ?: null,
                'notes'      => $request->input('notes') ?: null,
                'video_url'  => $request->input('video_url') ?: null,
                'video_type' => $request->input('video_type') ?: null,
                'color'      => $request->input('color', 'blue'),
                'date'       => $date,
                'start'      => $request->input('start'),
                'end'        => $request->input('end'),
                'status'     => 1,
            ]);
            $schedule->participants()->attach([
                $request->input('first_athlete'),
                $request->input('second_athlete'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to save this schedule.')])
                ->withInput();
        }
        DB::commit();
        return redirect()->route('schedules.index');
    }

    public function edit($schedule)
    {
        $team = $this->currentTeam();
        $schedule = $team ? $team->schedules()->where('id', $schedule)->first() : null;
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $parcicipants = $schedule->participants;
        $first_athlete = $parcicipants->get(0)?->id;
        $second_athlete = $parcicipants->get(1)?->id;
        $athletes = [];
        if ($team) {
            $athletes = $team->athletes;
        }
        return view('frontend.schedules.edit', compact('schedule', 'athletes', 'first_athlete', 'second_athlete'));
    }

    public function update(Request $request, $schedule)
    {
        $team = $this->currentTeam();
        $schedule = $team ? $team->schedules()->where('id', $schedule)->first() : null;
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $this->validateRequest($request, $team);
        DB::beginTransaction();
        try {
            $date = Carbon::createFromFormat('d/m/Y', $request->input('date'))->format('Y-m-d');
            $schedule->update([
                'title'      => $request->input('title') ?: null,
                'location'   => $request->input('location') ?: null,
                'notes'      => $request->input('notes') ?: null,
                'video_url'  => $request->input('video_url') ?: null,
                'video_type' => $request->input('video_type') ?: null,
                'color'      => $request->input('color', 'blue'),
                'date'       => $date,
                'start'      => $request->input('start'),
                'end'        => $request->input('end'),
                'status'     => 1,
            ]);
            $parcicipant = [
                $request->input('first_athlete'),
                $request->input('second_athlete'),
            ];
            $schedule->participants()->detach();
            $schedule->participants()->attach($parcicipant);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => $this->userFacingError($th, 'Unable to save this schedule.')])
                ->withInput();
        }
        DB::commit();
        return redirect()->route('schedules.index');
    }

    public function destroy($schedule)
    {
        $team = $this->currentTeam();
        $schedule = $team ? $team->schedules()->where('id', $schedule)->first() : null;
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $schedule->participants()->detach();
        $schedule->delete();
        return redirect()->back()->withInput();
    }

    public function month(Request $request)
    {
        // "!" resets unspecified fields, so "2026-02" on the 30th does not overflow into March
        $date = ($this->dateFromQuery($request, 'month', '!Y-m') ?? Carbon::now())->startOfMonth();
        $team = $this->currentTeam();
        $schedulesByDate = [];
        if ($team) {
            $schedulesByDate = $team->schedules()
                ->whereBetween('date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
                ->with(['participants'])->orderBy('start')->get()->groupBy('date');
        }
        $calStart = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calEnd   = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        return view('frontend.schedules.month', compact('date', 'schedulesByDate', 'calStart', 'calEnd'));
    }

    public function week(Request $request)
    {
        $date = ($this->dateFromQuery($request, 'week', 'Y-m-d') ?? Carbon::now())->startOfWeek(Carbon::MONDAY);
        $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY);
        $team = $this->currentTeam();
        $schedulesByDate = [];
        if ($team) {
            $schedulesByDate = $team->schedules()
                ->whereBetween('date', [$date, $weekEnd])
                ->with(['participants'])->orderBy('start')->get()->groupBy('date');
        }
        $days = [];
        for ($i = 0; $i < 7; $i++) $days[] = $date->copy()->addDays($i);
        return view('frontend.schedules.week', compact('date', 'schedulesByDate', 'days'));
    }

    public function day(Request $request)
    {
        $date = $this->dateFromQuery($request, 'date', 'd/m/Y') ?? Carbon::now();
        $team = $this->currentTeam();
        $schedules = collect();
        if ($team) {
            $schedules = $team->schedules()
                ->whereDate('date', $date->format('Y-m-d'))
                ->with(['participants'])->orderBy('start')->get();
        }
        return view('frontend.schedules.day', compact('date', 'schedules'));
    }

    public function planner(Request $request)
    {
        $date = $this->dateFromQuery($request, 'date', 'd/m/Y') ?? Carbon::now();
        $team = $this->currentTeam();
        $schedules = collect();
        $tasks = collect();
        if ($team) {
            $schedules = $team->schedules()
                ->whereDate('date', $date->format('Y-m-d'))
                ->with(['participants'])->orderBy('start')->get();
            $tasks = Task::where('team_id', $team->id)
                ->where('status', 0)
                ->where(function ($q) use ($date) {
                    $q->whereDate('due_date', $date->format('Y-m-d'))->orWhereNull('due_date');
                })->orderByDesc('priority')->orderBy('due_time')->get();
        }
        return view('frontend.schedules.planner', compact('date', 'schedules', 'tasks'));
    }
}
