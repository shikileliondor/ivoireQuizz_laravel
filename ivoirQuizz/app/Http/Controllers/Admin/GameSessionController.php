<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameSessionController extends Controller
{
    public const STATUSES = ['started', 'completed', 'failed', 'abandoned'];

    public const MODES = ['level', 'boss', 'daily_challenge', 'mixed'];

    public function index(Request $request): View
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $sessions = GameSession::query()
            ->with(['user', 'level.city', 'category'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('mode'), fn ($query) => $query->where('mode', $request->query('mode')))
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->whereHas('user', fn ($users) => $users->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('username', 'like', $term)));
            })
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.sessions.index', [
            'sessions' => $sessions,
            'statuses' => self::STATUSES,
            'modes' => self::MODES,
        ]);
    }

    public function show(GameSession $session): View
    {
        $session->load([
            'user',
            'level.city.region',
            'category',
            'gameSessionAnswers' => fn ($query) => $query->orderBy('id'),
            'gameSessionAnswers.question' => fn ($query) => $query->withTrashed()->with('answers'),
            'gameSessionAnswers.answer',
        ]);

        return view('admin.sessions.show', ['session' => $session]);
    }
}
