@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
<div>
    <label class="form-label" for="f-{{ $key }}">{{ $label }}</label>
    <select id="f-{{ $key }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($key)]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($key, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error-text">{{ $message }}</p>@enderror
</div>
