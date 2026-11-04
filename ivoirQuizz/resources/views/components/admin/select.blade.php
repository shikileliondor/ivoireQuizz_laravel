@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false])

@php($id = 'field-'.$name)
@php($selected = (string) old($name, $value))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->except('class')->class(['form-input', 'form-input-error' => $errors->has($name)]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @if (is_array($optionLabel))
                <optgroup label="{{ $optionValue }}">
                    @foreach ($optionLabel as $groupValue => $groupLabel)
                        <option value="{{ $groupValue }}" @selected($selected === (string) $groupValue)>{{ $groupLabel }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endif
        @endforeach
    </select>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
