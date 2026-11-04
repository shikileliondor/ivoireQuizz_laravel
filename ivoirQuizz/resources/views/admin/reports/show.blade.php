@extends('admin.layouts.app')

@section('title', 'Signalement #'.$report->id)

@section('content')
    @php($question = $report->question)

    <x-admin.page-header :title="'Signalement #'.$report->id" :subtitle="'Envoyé '.$report->created_at->diffForHumans().($report->user ? ' par '.$report->user->name : '')" :back="route('admin.reports.index')">
        <x-slot:actions>
            <x-admin.enum group="report_reason" :value="$report->reason" />
            <x-admin.enum group="report_status" :value="$report->status" />
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($report->message)
                <section class="card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Message du joueur</h2>
                    <blockquote class="mt-2 border-l-4 border-brand-300 pl-4 text-sm whitespace-pre-line text-slate-700">{{ $report->message }}</blockquote>
                </section>
            @endif

            <section class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-900">Question signalée</h2>
                    @if ($question && ! $question->trashed())
                        <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-secondary btn-sm"><x-admin.icon name="pencil" class="size-4" /> Corriger la question</a>
                    @endif
                </div>
                @if ($question)
                    <p class="mt-3 text-base font-medium text-slate-900">{{ $question->question_text }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $question->level?->city?->name }} · {{ $question->level?->title }}
                        @if ($question->category) · {{ $question->category->name }} @endif
                        @if ($question->trashed()) · <span class="font-semibold text-red-600">archivée</span> @endif
                    </p>
                    <ul class="mt-4 space-y-2">
                        @foreach ($question->answers as $answer)
                            <li @class(['flex items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1', 'bg-ci-50 font-medium text-ci-600 ring-ci-500/30' => $answer->is_correct, 'text-slate-700 ring-slate-200' => ! $answer->is_correct])>
                                @if ($answer->is_correct)
                                    <x-admin.icon name="check" class="size-4" />
                                @else
                                    <span class="size-4"></span>
                                @endif
                                {{ $answer->answer_text }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($question->explanation)
                        <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600"><span class="font-medium text-slate-700">Explication :</span> {{ $question->explanation }}</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-slate-500">La question n’existe plus.</p>
                @endif
            </section>

            @if ($related->isNotEmpty())
                <section class="card overflow-hidden">
                    <header class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">Autres signalements sur cette question ({{ $related->count() }})</h2>
                    </header>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($related as $other)
                            <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                                <x-admin.enum group="report_reason" :value="$other->reason" />
                                <span class="min-w-0 flex-1 truncate text-slate-600">{{ $other->message ?: '—' }}</span>
                                <x-admin.enum group="report_status" :value="$other->status" />
                                <a href="{{ route('admin.reports.show', $other) }}" class="text-xs text-brand-600 hover:text-brand-700">Ouvrir</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside>
            <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="card space-y-4 p-5">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-slate-900">Décision</h2>
                <fieldset class="space-y-2">
                    <legend class="sr-only">Nouveau statut</legend>
                    @foreach ($statuses as $value)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-400">
                            <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $report->status === 'pending' ? 'fixed' : $report->status) === $value) class="accent-brand-500">
                            <span class="text-sm font-medium text-slate-700">{{ \App\Support\AdminLabels::label('report_status', $value) }}</span>
                        </label>
                    @endforeach
                </fieldset>
                @if ($related->where('status', 'pending')->isNotEmpty())
                    <x-admin.checkbox name="apply_to_question" label="Appliquer aux autres signalements en attente" :hint="$related->where('status', 'pending')->count().' autre(s) signalement(s) en attente sur cette question.'" :checked="true" />
                @endif
                <button type="submit" class="btn btn-primary w-full">Enregistrer la décision</button>
                @if ($report->reviewed_at)
                    <p class="text-center text-xs text-slate-500">Traité le {{ $report->reviewed_at->format('d/m/Y à H:i') }}</p>
                @endif
            </form>
        </aside>
    </div>
@endsection
