@extends('admin.layouts.app')

@section('title', 'Questions')

@section('content')
    @php($levelOptions = $levels->groupBy(fn ($level) => $level->city?->name ?? 'Sans ville')->map(fn ($group) => $group->pluck('title', 'id')->all())->all())

    <x-admin.page-header title="Questions" :subtitle="number_format($questions->total(), 0, ',', ' ').' question(s) trouvée(s)'">
        <x-slot:actions>
            <a href="{{ route('admin.questions.create', request()->only('level')) }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouvelle question</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.questions.index')">
        <div class="min-w-56 flex-1">
            <label for="q" class="form-label">Recherche</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Texte de la question" class="form-input">
        </div>
        <x-admin.select name="level" label="Niveau" :options="$levelOptions" :value="request('level')" placeholder="Tous" class="w-52" />
        <x-admin.select name="category" label="Catégorie" :options="$categories->pluck('name', 'id')->all()" :value="request('category')" placeholder="Toutes" class="w-44" />
        <x-admin.select name="difficulty" label="Difficulté" :options="\App\Support\AdminLabels::options('difficulty', $difficulties)" :value="request('difficulty')" placeholder="Toutes" class="w-36" />
        <x-admin.select name="status" label="Statut" :options="['active' => 'Actives', 'inactive' => 'Inactives']" :value="request('status')" placeholder="Toutes" class="w-36" />
        <x-admin.select name="source" label="Origine" :options="['campaign' => 'Import campagne', 'manual' => 'Saisie manuelle']" :value="request('source')" placeholder="Toutes" class="w-44" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th class="w-[45%]">Question</th><th>Niveau</th><th>Catégorie</th><th>Difficulté</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($questions as $question)
                        <tr>
                            <td>
                                <a href="{{ route('admin.questions.edit', $question) }}" class="line-clamp-2 font-medium text-slate-900 hover:text-brand-600">{{ $question->question_text }}</a>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 text-ci-600"><x-admin.icon name="check" class="size-3.5" />{{ $question->answers->first()?->answer_text ?? 'Aucune bonne réponse' }}</span>
                                    @if ($question->source_key)
                                        <x-admin.badge color="blue">Campagne v{{ $question->source_version }}</x-admin.badge>
                                    @endif
                                    @if ($question->pending_reports_count > 0)
                                        <x-admin.badge color="red">{{ $question->pending_reports_count }} signalement(s)</x-admin.badge>
                                    @endif
                                </p>
                            </td>
                            <td class="text-sm">{{ $question->level?->title ?? '—' }}<span class="block text-xs text-slate-400">{{ $question->level?->city?->name }}</span></td>
                            <td>{{ $question->category?->name ?? '—' }}</td>
                            <td><x-admin.enum group="difficulty" :value="$question->difficulty" /></td>
                            <td>
                                <form method="POST" action="{{ route('admin.questions.toggle', $question) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="{{ $question->is_active ? 'Désactiver' : 'Activer' }}" class="cursor-pointer">
                                        <x-admin.active :active="$question->is_active" />
                                    </button>
                                </form>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.questions.destroy', $question)" label="Archiver" confirm="Archiver cette question ? Elle ne sera plus proposée aux joueurs." />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-admin.empty message="Aucune question ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $questions->links() }}</div>
@endsection
