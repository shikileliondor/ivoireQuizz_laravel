@props(['label', 'value', 'hint' => null, 'icon' => null, 'href' => null, 'tone' => 'brand'])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'green' => 'bg-ci-50 text-ci-600',
        'blue' => 'bg-sky-50 text-sky-600',
        'red' => 'bg-red-50 text-red-600',
        'purple' => 'bg-violet-50 text-violet-600',
    ];
@endphp

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif class="card flex items-start gap-4 p-5 {{ $href ? 'transition hover:ring-brand-300' : '' }}">
    @if ($icon)
        <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-admin.icon :name="$icon" class="size-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</{{ $href ? 'a' : 'div' }}>
