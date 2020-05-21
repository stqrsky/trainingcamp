<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Schedule;
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
            'date' => 'required|date_format:d/m/Y',
            'start' => 'required|date_format:H:i',
            'end' => 'required|date_format:H:i',
            'first_athlete' => 'required|exists:users,id',
            'second_athlete' => 'required|exists:users,id|different:first_athlete'
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
                'team_id' => $team->id,
                'user_id' => $profile->id,
                'date' => $date,
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'status' => 1
            ]);
            $parcicipant = [
                $request->input('first_athlete'),
                $request->input('second_athlete'),
            ];
            $schedule->participants()->attach($parcicipant);
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
                'date' => $date,
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'status' => 1
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
}
