<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::now()->format('Y-m-d');
        if ($request->get('date')) {
            $date = Carbon::createFromFormat('d/m/Y', $request->get('date'))->format('Y-m-d');
        }
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
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
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $athletes = [];
        if ($team) {
            $athletes = $team->athletes;
        }
        return view('frontend.schedules.create', compact('athletes'));
    }

    private function validateRequest($request)
    {
        $this->validate($request, [
            'date'           => 'required|date_format:d/m/Y',
            'start'          => 'required|date_format:H:i',
            'end'            => 'required|date_format:H:i',
            'first_athlete'  => 'required|exists:users,id',
            'second_athlete' => 'required|exists:users,id|different:first_athlete',
            'video_url'      => 'nullable|url|max:512',
        ]);
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);
        DB::beginTransaction();
        try {
            $profile = User::find(Auth::user()->id);
            $team = $profile->team;
            $date = Carbon::createFromFormat('d/m/Y', $request->input('date'))->format('Y-m-d');
            $schedule = Schedule::create([
                'team_id'    => $team->id,
                'user_id'    => $profile->id,
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
            return redirect()->back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
        DB::commit();
        return redirect()->route('schedules.index');
    }

    public function edit($schedule)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $schedule = $team->schedules()->where('id', $schedule)->first();
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $parcicipants = $schedule->participants;
        $first_athlete = $parcicipants[0]->id;
        $second_athlete = $parcicipants[1]->id;
        $athletes = [];
        if ($team) {
            $athletes = $team->athletes;
        }
        return view('frontend.schedules.edit', compact('schedule', 'athletes', 'first_athlete', 'second_athlete'));
    }

    public function update(Request $request, $schedule)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $schedule = $team->schedules()->where('id', $schedule)->first();
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $this->validateRequest($request);
        DB::beginTransaction();
        try {
            $profile = User::find(Auth::user()->id);
            $team = $profile->team;
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
            return redirect()->back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
        DB::commit();
        return redirect()->route('schedules.index');
    }

    public function destroy($schedule)
    {
        $profile = User::find(Auth::user()->id);
        $team = $profile->team;
        $schedule = $team->schedules()->where('id', $schedule)->first();
        if (!$schedule) {
            return redirect()->back()->withErrors(['error' => 'Schedule not found'])->withInput();
        }
        $schedule->participants()->detach();
        $schedule->delete();
        return redirect()->back()->withInput();
    }

    public function month(Request $request)
    {
        $date = Carbon::now()->startOfMonth();
        if ($request->get('month')) {
            $date = Carbon::createFromFormat('Y-m', $request->get('month'))->startOfMonth();
        }
        $team = User::find(Auth::id())->team;
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
        $date = Carbon::now()->startOfWeek(Carbon::MONDAY);
        if ($request->get('week')) {
            $date = Carbon::createFromFormat('Y-m-d', $request->get('week'))->startOfWeek(Carbon::MONDAY);
        }
        $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY);
        $team = User::find(Auth::id())->team;
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
        $date = Carbon::now();
        if ($request->get('date')) {
            $date = Carbon::createFromFormat('d/m/Y', $request->get('date'));
        }
        $team = User::find(Auth::id())->team;
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
        $date = Carbon::now();
        if ($request->get('date')) {
            $date = Carbon::createFromFormat('d/m/Y', $request->get('date'));
        }
        $team = User::find(Auth::id())->team;
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
