<?php

namespace App\Http\Controllers;

use App\Services\Assistant\AssistantConversation;
use App\Services\Assistant\TeamAssistant;
use Anthropic\Core\Exceptions\APIException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssistantController extends Controller
{
    public function index()
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        return view('frontend.assistant.index', [
            'enabled' => TeamAssistant::enabled(),
            'exchanges' => AssistantConversation::for($team)->exchanges(),
        ]);
    }

    public function ask(Request $request)
    {
        $team = $this->currentTeam();
        abort_unless($team && TeamAssistant::enabled(), 404);
        $request->validate(['question' => 'required|string|max:4000']);

        $question = trim($request->input('question'));
        $conversation = AssistantConversation::for($team);
        try {
            $reply = app(TeamAssistant::class)->ask(Auth::user(), $team, $question, $conversation->contextMessages());
        } catch (APIException $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['question' => 'The assistant is not reachable right now. Please try again in a moment.']);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['question' => $this->userFacingError($e)]);
        }

        $conversation->add($question, $reply['answer'], $reply['drafts'], $reply['failed']);
        return redirect()->to(route('assistant') . '#latest');
    }

    public function clear()
    {
        $team = $this->currentTeam();
        if ($team) {
            AssistantConversation::for($team)->clear();
        }
        return redirect()->route('assistant');
    }
}
