{{-- contain : l'aperçu montre l'image entière (un logo ne doit pas être recadré). --}}
@props(['name' => 'image', 'label' => 'Image', 'current' => null, 'hint' => 'JPG, PNG ou WebP, 2 Mo maximum.', 'contain' => false])

@php($id = 'field-'.$name)
@php($url = $current ? (str_starts_with($current, 'http') ? $current : Storage::disk('public')->url($current)) : null)

<div {{ $attributes->only('class') }}>
    <span class="form-label">{{ $label }}</span>
    <div class="flex items-start gap-4">
        <img
            id="{{ $id }}-preview"
            src="{{ $url ?? '' }}"
            alt=""
            @class(['size-20 shrink-0 rounded-lg ring-1 ring-slate-200', 'object-cover' => ! $contain, 'bg-white object-contain p-1' => $contain, 'hidden' => ! $url])
        >
        <div class="min-w-0 flex-1 space-y-2">
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="file"
                accept="image/*"
                data-preview="{{ $id }}-preview"
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
            >
            @if ($current)
                <label class="flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_{{ $name }}" value="1" class="size-3.5 accent-red-600">
                    Supprimer l’image actuelle
                </label>
            @endif
            @if ($hint)
                <p class="text-xs text-slate-500">{{ $hint }}</p>
            @endif
            @error($name)
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
