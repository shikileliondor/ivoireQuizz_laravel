@extends('admin.layouts.app')

@section('title', 'Parties')

@section('content')
    <x-admin.page-header title="Parties" :subtitle="number_format($sessions->total(), 0, ',', ' ').' partie(s)'" />

    <x-admin.filters :action="route('admin.sessions.index')">
        @if (request('user'))
            <input type="hidden" name="user" value="{{ request('user') }}">
        @endif
        <div class="min-w-48 flex-1">
            <label for="q" class="form-label">Joueur</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nom, pseudo ou email" class="form-input">
        </div>
        <x-admin.select name="status" label="Statut" :options="\App\Support\AdminLabels::options('session_status', $statuses)" :value="request('status')" placeholder="Tous" class="w-40" />
        <x-admin.select name="mode" label="Mode" :options="\App\Support\AdminLabels::options('session_mode', $modes)" :value="request('mode')" placeholder="Tous" class="w-40" />
        <x-admin.input name="from" label="Du" type="date" :value="request('from')" class="w-40" />
        <x-admin.input name="to" label="Au" type="date" :value="request('to')" class="w-40" />
    </x-admin.filters>

    @if (request('user'))
        <p class="mb-3 text-sm text-slate-600">
            Filtré sur un joueur. <a href="{{ route('admin.sessions.index', request()->except('user', 'page')) }}" class="font-medium text-brand-600 hover:text-brand-700">Retirer ce filtre</a>
        </p>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Date</th><th>Joueur</th><th>Contenu</th><th>Mode</th><th>Statut</th><th class="text-right">Bonnes rép.</th><th class="text-right">Score</th><th class="text-right">Gains</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="whitespace-nowrap">{{ $session->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($session->user)
                                    <a href="{{ route('admin.users.show', $session->user) }}" class="font-medium hover:text-brand-600">{{ $session->user->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-sm">{{ $session->level?->title ?? $session->category?->name ?? '—' }}<span class="block text-xs text-slate-400">{{ $session->level?->city?->name }}</span></td>
                            <td><x-admin.enum group="session_mode" :value="$session->mode" /></td>
                            <td><x-admin.enum group="session_status" :value="$session->status" /></td>
                            <td class="text-right tabular-nums">{{ $session->correct_answers }}/{{ $session->total_questions }}</td>
                            <td class="text-right tabular-nums">{{ $session->score }}</td>
                            <td class="whitespace-nowrap text-right text-xs text-slate-600 tabular-nums">{{ $session->xp_earned }} XP · {{ $session->coins_earned }} p.</td>
                            <td class="text-right"><a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="eye" class="size-4" /><span class="sr-only">Détail</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-admin.empty message="Aucune partie ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $sessions->links() }}</div>
@endsection
