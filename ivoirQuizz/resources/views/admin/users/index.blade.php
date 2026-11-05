@extends('admin.layouts.app')

@section('title', 'Joueurs')

@section('content')
    <x-admin.page-header title="Joueurs" :subtitle="number_format($users->total(), 0, ',', ' ').' compte(s)'" />

    <x-admin.filters :action="route('admin.users.index')">
        <div class="min-w-56 flex-1">
            <label for="q" class="form-label">Recherche</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nom, pseudo, nom d’utilisateur, email, code ami…" class="form-input">
        </div>
        <x-admin.select name="status" label="Statut" :options="\App\Support\AdminLabels::options('user_status')" :value="request('status')" placeholder="Tous" class="w-40" />
        <x-admin.select name="role" label="Rôle" :options="['admin' => 'Administrateurs']" :value="request('role')" placeholder="Tous" class="w-44" />
        <x-admin.select name="sort" label="Tri" :options="['recent' => 'Plus récents', 'xp' => 'XP', 'score' => 'Score', 'login' => 'Dernière connexion', 'name' => 'Nom']" :value="request('sort', 'recent')" class="w-44" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Code ami</th>
                        <th class="text-right">Niveau</th>
                        <th class="text-right">XP</th>
                        <th class="text-right">Parties</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $user->name }}</a>
                                @if ($user->pseudo)
                                    <span class="ml-1 text-xs font-medium text-brand-600" title="Pseudo public">« {{ $user->pseudo }} »</span>
                                @endif
                                @if ($user->is_admin)
                                    <x-admin.badge color="orange" class="ml-1">Admin</x-admin.badge>
                                @endif
                                <p class="text-xs text-slate-500">{{ $user->username ? '@'.$user->username.' · ' : '' }}{{ $user->email }}</p>
                            </td>
                            <td class="font-mono text-xs">{{ $user->friend_code }}</td>
                            <td class="text-right tabular-nums">{{ $user->current_level }}</td>
                            <td class="text-right tabular-nums">{{ number_format($user->xp_total, 0, ',', ' ') }}</td>
                            <td class="text-right tabular-nums">{{ $user->games_played }}</td>
                            <td><x-admin.enum group="user_status" :value="$user->status" /></td>
                            <td class="whitespace-nowrap text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Jamais' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="eye" class="size-4" /><span class="sr-only">Voir</span></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-admin.empty message="Aucun joueur ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
