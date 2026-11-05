<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustBalanceRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\Notifications\PushSender;
use App\Services\User\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    private const SORTS = [
        'recent' => ['created_at', 'desc'],
        'xp' => ['xp_total', 'desc'],
        'score' => ['total_score', 'desc'],
        'login' => ['last_login_at', 'desc'],
        'name' => ['name', 'asc'],
    ];

    public function index(Request $request): View
    {
        [$column, $direction] = self::SORTS[$request->query('sort')] ?? self::SORTS['recent'];

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('pseudo', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('friend_code', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->query('role') === 'admin', fn ($query) => $query->where('is_admin', true))
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function show(User $user): View
    {
        $user->loadCount(['gameSessions', 'userCollectibles', 'userChests', 'questionReports', 'tokens']);

        return view('admin.users.show', [
            'user' => $user,
            'sessions' => $user->gameSessions()->with(['level.city', 'category'])->latest()->limit(10)->get(),
            'transactions' => $user->rewardTransactions()->latest()->limit(10)->get(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.show', $user)->with('success', 'Profil du joueur mis à jour.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->ensureNotSelf($request, $user, 'Vous ne pouvez pas modifier votre propre statut.');

        $validated = $request->validate([
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        $status = UserStatus::from($validated['status']);
        $user->forceFill(['status' => $status])->save();

        if ($status !== UserStatus::Active) {
            $user->tokens()->delete();
        }

        return back()->with('success', 'Statut mis à jour : '.$status->label().'.');
    }

    public function adjustBalance(AdjustBalanceRequest $request, User $user, PushSender $push): RedirectResponse
    {
        $data = $request->validated();
        $column = $data['currency'] === 'coin' ? 'coins' : 'gems';

        DB::transaction(function () use ($data, $column, $user, $request): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $balance = (int) $locked->{$column} + (int) $data['amount'];

            if ($balance < 0) {
                throw ValidationException::withMessages(['amount' => 'Le solde ne peut pas devenir négatif.']);
            }

            $locked->forceFill([$column => $balance])->save();

            RewardTransaction::query()->create([
                'user_id' => $locked->id,
                'type' => $data['currency'],
                'amount' => (int) $data['amount'],
                'source_type' => 'admin',
                'source_id' => $request->user()->id,
                'description' => $data['reason'],
            ]);
        });

        if ($request->boolean('notify') && $data['amount'] > 0) {
            $push->notify($user, 'admin_gift', [
                'montant' => number_format((int) $data['amount'], 0, ',', ' '),
                'monnaie' => $data['currency'] === 'coin' ? 'pièces' : 'gemmes',
            ]);
        }

        return back()->with('success', 'Solde ajusté.');
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        $this->ensureNotSelf($request, $user, 'Vous ne pouvez pas retirer vos propres droits.');

        $user->forceFill(['is_admin' => ! $user->is_admin])->save();

        return back()->with('success', $user->is_admin ? 'Droits administrateur accordés.' : 'Droits administrateur retirés.');
    }

    public function revokeTokens(User $user): RedirectResponse
    {
        $count = $user->tokens()->delete();

        return back()->with('success', "{$count} session(s) mobile(s) révoquée(s).");
    }

    public function destroy(Request $request, User $user, AccountService $accounts): RedirectResponse
    {
        $this->ensureNotSelf($request, $user, 'Vous ne pouvez pas supprimer votre propre compte ici.');

        $accounts->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'Compte supprimé et anonymisé.');
    }

    private function ensureNotSelf(Request $request, User $user, string $message): void
    {
        abort_if($request->user()->is($user), 403, $message);
    }
}
