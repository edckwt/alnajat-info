@props(['name', 'label', 'checked' => false])
<label class="form-switch gap-3 w-full">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))>
    <span class="switch-track"></span><span class="switch-thumb"></span>
    <span class="text-sm ms-2">{{ $label }}</span>
</label>
