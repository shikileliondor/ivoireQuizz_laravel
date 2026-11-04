@extends('admin.layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    @php($fmt = fn ($n) => number_format($n, 0, ',', ' '))

    <x-admin.page-header title="Tableau de bord" :subtitle="'Bonjour '.auth()->user()->name.', voici l’activité d’IvoireQuizz.'">
        <x-slot:actions>
            <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">
                <x-admin.icon name="plus" class="size-4" /> Nouvelle question
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Joueurs inscrits" :value="$fmt($stats['players'])" :hint="'+'.$fmt($stats['new_players_week']).' sur 7 jours'" icon="users" :href="route('admin.users.index')" />
        <x-admin.stat label="Actifs aujourd’hui" :value="$fmt($stats['active_today'])" :hint="$fmt($stats['suspended']).' compte(s) suspendu(s) ou désactivé(s)'" icon="bolt" tone="green" />
        <x-admin.stat label="Parties aujourd’hui" :value="$fmt($stats['sessions_today'])" :hint="$fmt($stats['sessions_week']).' sur 7 jours'" icon="play" tone="blue" :href="route('admin.sessions.index')" />
        <x-admin.stat label="Signalements en attente" :value="$fmt($stats['pending_reports'])" hint="Questions à vérifier" icon="flag" :tone="$stats['pending_reports'] > 0 ? 'red' : 'green'" :href="route('admin.reports.index')" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Taux de réussite (7 j)" :value="str_replace('.', ',', $stats['success_rate_week']).' %'" hint="Parties réussies / terminées" icon="trophy" tone="green" />
        <x-admin.stat label="Précision moyenne (7 j)" :value="str_replace('.', ',', $stats['accuracy_week']).' %'" hint="Bonnes réponses par partie" icon="chart" tone="purple" />
        <x-admin.stat label="Questions actives" :value="$fmt($stats['questions_active'])" :hint="$fmt($stats['questions_total']).' au total'" icon="question" :href="route('admin.questions.index')" />
        <x-admin.stat label="Carte de jeu" :value="$stats['regions'].' / '.$stats['cities'].' / '.$stats['levels']" hint="Régions / villes / niveaux" icon="map" tone="blue" :href="route('admin.regions.index')" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-admin.bar-chart title="Parties par jour" :data="$activity" field="sessions" />
        <x-admin.bar-chart title="Inscriptions par jour" :data="$activity" field="signups" tone="green" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <section class="card overflow-hidden lg:col-span-1">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Signalements récents</h2>
                <a href="{{ route('admin.reports.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Tout voir</a>
            </header>
            @forelse ($pendingReports as $report)
                <a href="{{ route('admin.reports.show', $report) }}" class="block border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-2">
                        <x-admin.enum group="report_reason" :value="$report->reason" />
                        <span class="text-xs text-slate-400">{{ $report->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1.5 line-clamp-2 text-sm text-slate-700">{{ $report->question?->question_text ?? 'Question supprimée' }}</p>
                </a>
            @empty
                <x-admin.empty message="Aucun signalement en attente." />
            @endforelse
        </section>

        <section class="card overflow-hidden">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Meilleurs joueurs (XP)</h2>
                <a href="{{ route('admin.users.index', ['sort' => 'xp']) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Classement</a>
            </header>
            <ol>
                @forelse ($topPlayers as $index => $player)
                    <li class="flex items-center gap-3 border-b border-slate-100 px-5 py-2.5 last:border-0">
                        <span class="w-5 text-center text-sm font-bold text-slate-400 tabular-nums">{{ $index + 1 }}</span>
                        <a href="{{ route('admin.users.show', $player) }}" class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 hover:text-brand-600">{{ $player->name }}</a>
                        <span class="text-sm text-slate-600 tabular-nums">{{ $fmt($player->xp_total) }} XP</span>
                    </li>
                @empty
                    <li><x-admin.empty message="Aucun joueur." /></li>
                @endforelse
            </ol>
        </section>

        <section class="card overflow-hidden">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Derniers inscrits</h2>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Tous les joueurs</a>
            </header>
            <ul>
                @forelse ($latestPlayers as $player)
                    <li class="flex items-center gap-3 border-b border-slate-100 px-5 py-2.5 last:border-0">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{{ mb_strtoupper(mb_substr($player->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.users.show', $player) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-brand-600">{{ $player->name }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $player->email }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">{{ $player->created_at?->diffForHumans() }}</span>
                    </li>
                @empty
                    <li><x-admin.empty message="Aucun joueur." /></li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
