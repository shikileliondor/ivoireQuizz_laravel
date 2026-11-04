@extends('admin.layouts.app')

@section('title', 'Niveaux')

@section('content')
    @php($cityOptions = $cities->groupBy(fn ($city) => $city->region?->name ?? 'Sans région')->map(fn ($group) => $group->pluck('name', 'id')->all())->all())

    <x-admin.page-header title="Niveaux" subtitle="Étapes de campagne jouées dans chaque ville.">
        <x-slot:actions>
            <a href="{{ route('admin.levels.create', request()->only('city')) }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouveau niveau</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :action="route('admin.levels.index')">
        <div class="min-w-48 flex-1">
            <label for="q" class="form-label">Recherche</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Titre du niveau" class="form-input">
        </div>
        <x-admin.select name="city" label="Ville" :options="$cityOptions" :value="request('city')" placeholder="Toutes" class="w-52" />
        <x-admin.select name="difficulty" label="Difficulté" :options="\App\Support\AdminLabels::options('difficulty', $difficulties)" :value="request('difficulty')" placeholder="Toutes" class="w-40" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Niveau</th><th>Ville</th><th>Difficulté</th><th class="text-right">Questions</th><th class="text-right">Réussite</th><th class="text-right">Récompenses</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($levels as $level)
                        <tr>
                            <td>
                                <p class="font-medium text-slate-900">
                                    <span class="text-slate-400 tabular-nums">#{{ $level->order }}</span> {{ $level->title }}
                                    @if ($level->is_boss)
                                        <x-admin.badge color="purple" class="ml-1">Boss</x-admin.badge>
                                    @endif
                                </p>
                                <p class="font-mono text-xs text-slate-500">{{ $level->slug }}</p>
                            </td>
                            <td>{{ $level->city?->name ?? '—' }} <span class="text-xs text-slate-400">{{ $level->city?->region?->name }}</span></td>
                            <td><x-admin.enum group="difficulty" :value="$level->difficulty" /></td>
                            <td class="text-right tabular-nums">
                                <a href="{{ route('admin.questions.index', ['level' => $level->id]) }}" @class(['hover:text-brand-600', 'font-semibold text-red-600' => $level->active_questions_count < $level->questions_count])>
                                    {{ $level->active_questions_count }}
                                </a>
                                <span class="text-slate-400">/ {{ $level->questions_count }}</span>
                            </td>
                            <td class="text-right tabular-nums">{{ $level->passing_score }} %</td>
                            <td class="whitespace-nowrap text-right text-xs text-slate-600 tabular-nums">{{ $level->xp_reward }} XP · {{ $level->coins_reward }} pièces · {{ $level->gems_reward }} gemmes</td>
                            <td><x-admin.active :active="$level->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.questions.create', ['level' => $level->id]) }}" class="btn btn-ghost btn-sm" title="Ajouter une question"><x-admin.icon name="plus" class="size-4" /><span class="sr-only">Ajouter une question</span></a>
                                <a href="{{ route('admin.levels.edit', $level) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.levels.destroy', $level)" confirm="Supprimer le niveau {{ $level->title }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-admin.empty message="Aucun niveau." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">Questions : actives / nombre tiré par partie. En rouge, le niveau n’a pas assez de questions actives.</p>
    </div>

    <div class="mt-4">{{ $levels->links() }}</div>
@endsection
