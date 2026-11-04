@extends('admin.layouts.app')

@section('title', 'Coffres')

@section('content')
    <x-admin.page-header title="Coffres" subtitle="Récompenses aléatoires gagnées en jouant. Les gains sont tirés entre le minimum et le maximum.">
        <x-slot:actions>
            <a href="{{ route('admin.chests.create') }}" class="btn btn-primary"><x-admin.icon name="plus" class="size-4" /> Nouveau coffre</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Coffre</th><th>Type</th><th class="text-right">XP</th><th class="text-right">Pièces</th><th class="text-right">Gemmes</th><th class="text-right">Distribués / ouverts</th><th>Statut</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($chests as $chest)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($chest->image)
                                        <img src="{{ Storage::disk('public')->url($chest->image) }}" alt="" class="size-9 rounded-md object-cover">
                                    @else
                                        <span class="grid size-9 place-items-center rounded-md bg-slate-100 text-slate-400"><x-admin.icon name="gift" class="size-4" /></span>
                                    @endif
                                    <span class="font-medium text-slate-900">{{ $chest->name }}</span>
                                </div>
                            </td>
                            <td><x-admin.enum group="chest_type" :value="$chest->type" /></td>
                            <td class="text-right tabular-nums">{{ $chest->min_xp }}–{{ $chest->max_xp }}</td>
                            <td class="text-right tabular-nums">{{ $chest->min_coins }}–{{ $chest->max_coins }}</td>
                            <td class="text-right tabular-nums">{{ $chest->min_gems }}–{{ $chest->max_gems }}</td>
                            <td class="text-right tabular-nums">{{ $chest->user_chests_count }} / {{ $chest->opened_count }}</td>
                            <td><x-admin.active :active="$chest->is_active" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.chests.edit', $chest) }}" class="btn btn-ghost btn-sm"><x-admin.icon name="pencil" class="size-4" /><span class="sr-only sm:not-sr-only">Modifier</span></a>
                                <x-admin.delete-button :action="route('admin.chests.destroy', $chest)" confirm="Supprimer le coffre {{ $chest->name }} ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-admin.empty message="Aucun coffre." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
