@props(['title', 'data', 'field', 'tone' => 'brand', 'unit' => ''])

{{-- Une seule série par graphique (pas de double axe) ; infobulle par barre et tableau des données pour l'accessibilité. --}}
@php
    $max = max(1, $data->max($field));
    $total = $data->sum($field);
    $bar = $tone === 'green' ? 'bg-ci-600 group-hover:bg-ci-500 group-focus:bg-ci-500' : 'bg-brand-600 group-hover:bg-brand-500 group-focus:bg-brand-500';
    $lastIndex = $data->count() - 1;
@endphp

<div class="card p-5">
    <div class="flex items-baseline justify-between gap-3">
        <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
        <p class="text-xs text-slate-500"><span class="font-semibold text-slate-700 tabular-nums">{{ number_format($total, 0, ',', ' ') }}</span> sur {{ $data->count() }} jours</p>
    </div>

    <div class="mt-4 flex gap-2">
        <div class="flex h-36 flex-col justify-between text-right text-[11px] text-slate-400 tabular-nums" aria-hidden="true">
            <span>{{ $max }}</span>
            <span>0</span>
        </div>
        <div class="flex h-36 flex-1 items-end gap-0.5 border-b border-slate-200" role="img" aria-label="{{ $title }} : {{ $total }} au total">
            @foreach ($data as $point)
                @php($value = (int) $point[$field])
                <div class="group relative flex h-full flex-1 items-end outline-none" tabindex="0" aria-label="{{ $point['date']->translatedFormat('j F') }} : {{ $value }}">
                    <div
                        class="w-full rounded-t-[4px] transition-colors {{ $value > 0 ? $bar : 'bg-slate-200' }}"
                        style="height: {{ $value > 0 ? max(3, round($value * 100 / $max, 1)) : 1 }}%"
                    ></div>
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white shadow-lg group-hover:block group-focus:block">
                        <span class="text-slate-300">{{ $point['date']->translatedFormat('D j M') }}</span>
                        <span class="ml-1 font-semibold tabular-nums">{{ $value }}{{ $unit }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="mt-1.5 flex justify-between pl-8 text-[11px] text-slate-400" aria-hidden="true">
        <span>{{ $data->first()['date']->format('d/m') }}</span>
        <span>{{ $data[intdiv($lastIndex, 2)]['date']->format('d/m') }}</span>
        <span>{{ $data->last()['date']->format('d/m') }}</span>
    </div>

    <details class="mt-3 text-xs text-slate-500">
        <summary class="cursor-pointer select-none hover:text-slate-700">Voir les données</summary>
        <table class="mt-2 w-full">
            <tbody class="divide-y divide-slate-100">
                @foreach ($data as $point)
                    <tr>
                        <td class="py-1">{{ $point['date']->translatedFormat('l j F') }}</td>
                        <td class="py-1 text-right font-medium text-slate-700 tabular-nums">{{ $point[$field] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
</div>
