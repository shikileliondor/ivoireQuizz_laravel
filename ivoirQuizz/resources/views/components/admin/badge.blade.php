@props(['color' => 'slate'])

@php
    $palette = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'orange' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'purple' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $palette[$color] ?? $palette['slate']]) }}>{{ $slot }}</span>
