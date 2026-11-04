@extends('admin.layouts.app')

@section('title', 'Signalements')

@section('content')
    <x-admin.page-header title="Signalements" subtitle="Questions signalées par les joueurs depuis l’application." />

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filtrer par statut">
        @foreach ([...$statuses, 'all'] as $value)
            <a
                href="{{ route('admin.reports.index', ['status' => $value]) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium ring-1',
                    'bg-slate-900 text-white ring-slate-900' => $status === $value,
                    'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => $status !== $value,
                ])
            >
                {{ $value === 'all' ? 'Tous' : \App\Support\AdminLabels::label('report_status', $value) }}
                @if ($value !== 'all')
                    <span class="tabular-nums opacity-70">{{ $counts[$value] ?? 0 }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Motif</th><th class="w-[45%]">Question</th><th>Joueur</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr>
                            <td><x-admin.enum group="report_reason" :value="$report->reason" /></td>
                            <td>
                                <p class="line-clamp-2 text-slate-800">{{ $report->question?->question_text ?? 'Question supprimée' }}</p>
                                @if ($report->message)
                                    <p class="mt-1 line-clamp-1 text-xs text-slate-500">« {{ $report->message }} »</p>
                                @endif
                            </td>
                            <td>
                                @if ($report->user)
                                    <a href="{{ route('admin.users.show', $report->user) }}" class="hover:text-brand-600">{{ $report->user->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td><x-admin.enum group="report_status" :value="$report->status" /></td>
                            <td class="whitespace-nowrap text-slate-500">{{ $report->created_at->diffForHumans() }}</td>
                            <td class="text-right"><a href="{{ route('admin.reports.show', $report) }}" class="btn btn-secondary btn-sm">Traiter</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-admin.empty message="Aucun signalement dans cette catégorie." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
@endsection
