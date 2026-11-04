@extends('admin.layouts.app')

@section('title', 'Partie #'.$session->id)

@section('content')
    @php($place = $session->level ? collect([$session->level->city?->region?->name, $session->level->city?->name, $session->level->title])->filter()->implode(' › ') : ($session->category?->name ?? 'Partie libre'))

    <x-admin.page-header :title="'Partie #'.$session->id" :subtitle="$place" :back="route('admin.sessions.index')">
        <x-slot:actions>
            <x-admin.enum group="session_mode" :value="$session->mode" />
            <x-admin.enum group="session_status" :value="$session->status" />
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Joueur" :value="$session->user?->name ?? '—'" :hint="$session->user?->email" icon="users" :href="$session->user ? route('admin.users.show', $session->user) : null" />
        <x-admin.stat label="Score" :value="$session->score" :hint="'Bonus '.$session->bonus_score.' · total '.$session->total_score" icon="trophy" tone="green" />
        <x-admin.stat label="Bonnes réponses" :value="$session->correct_answers.' / '.$session->total_questions" :hint="'Précision '.rtrim(rtrim(number_format((float) $session->accuracy, 1, ',', ''), '0'), ',').' %'" icon="check" tone="blue" />
        <x-admin.stat label="Gains" :value="$session->xp_earned.' XP'" :hint="$session->coins_earned.' pièce(s) · '.$session->gems_earned.' gemme(s)'" icon="gift" tone="purple" />
    </div>

    <p class="mt-4 text-sm text-slate-500">
        Commencée le {{ ($session->started_at ?? $session->created_at)->format('d/m/Y à H:i:s') }}
        @if ($session->finished_at) · terminée le {{ $session->finished_at->format('d/m/Y à H:i:s') }} @endif
        @if ($session->duration_seconds) · durée {{ gmdate($session->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $session->duration_seconds) }} @endif
    </p>

    <section class="card mt-6 overflow-hidden">
        <header class="border-b border-slate-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Réponses ({{ $session->gameSessionAnswers->count() }})</h2>
        </header>
        <ol class="divide-y divide-slate-100">
            @forelse ($session->gameSessionAnswers as $index => $entry)
                @php($correctAnswer = $entry->question?->answers->firstWhere('is_correct', true))
                <li class="flex gap-4 px-5 py-3">
                    <span @class(['grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold', 'bg-ci-50 text-ci-600' => $entry->is_correct, 'bg-red-50 text-red-600' => ! $entry->is_correct])>{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900">
                            @if ($entry->question && ! $entry->question->trashed())
                                <a href="{{ route('admin.questions.edit', $entry->question) }}" class="hover:text-brand-600">{{ $entry->question->question_text }}</a>
                            @else
                                {{ $entry->question?->question_text ?? 'Question supprimée' }}
                            @endif
                        </p>
                        <p class="mt-1 text-sm">
                            <span @class(['font-medium', 'text-ci-600' => $entry->is_correct, 'text-red-600' => ! $entry->is_correct])>
                                {{ $entry->answer?->answer_text ?? 'Pas de réponse' }}
                            </span>
                            @if (! $entry->is_correct && $correctAnswer)
                                <span class="text-slate-500">· bonne réponse : {{ $correctAnswer->answer_text }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0 text-right text-xs text-slate-500 tabular-nums">
                        <p>{{ $entry->points_earned }} pts</p>
                        @if ($entry->response_time !== null)
                            <p>{{ $entry->response_time }} s</p>
                        @endif
                    </div>
                </li>
            @empty
                <li><x-admin.empty message="Aucune réponse enregistrée pour cette partie." /></li>
            @endforelse
        </ol>
    </section>
@endsection
