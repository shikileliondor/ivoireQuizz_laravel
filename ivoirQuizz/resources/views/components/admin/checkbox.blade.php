@props(['name', 'label', 'checked' => false, 'hint' => null])

@php($id = 'field-'.str_replace(['[', ']', '.'], '-', $name))

<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            @checked(old(str_replace(['[', ']'], ['.', ''], $name), $checked))
            class="mt-0.5 size-4 rounded border-slate-300 text-brand-500 accent-brand-500 focus:ring-brand-500"
        >
        <span>
            <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
            @if ($hint)
                <span class="block text-xs text-slate-500">{{ $hint }}</span>
            @endif
        </span>
    </label>
</div>
