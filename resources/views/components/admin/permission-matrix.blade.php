{{--
    جدول الصلاحيات: لكل وحدة عملياتها كمربعات اختيار، مع «تحديد الكل» للوحدة.
    <x-admin.permission-matrix name="permissions" :selected="$keys" :inherited="$roleKeys" :grantable="$mine" />
    - inherited: صلاحيات تأتي من الدور (تظهر محددة ومعطّلة)
    - grantable: ما يملكه العضو الحالي؛ لا يمنح غيره ما لا يملكه
--}}
@props(['name' => 'permissions', 'selected' => [], 'inherited' => [], 'grantable' => null])
@php
    $selected = array_flip(old($name, $selected) ?? []);
    $inherited = array_flip($inherited);
    $grantable = $grantable === null ? null : array_flip($grantable);
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" data-permission-matrix>
    @foreach (\App\Support\Permissions::modules() as $module => $config)
        <fieldset class="rounded-2xl border border-line p-4 space-y-3" data-permission-module>
            <legend class="sr-only">{{ $config['label'] }}</legend>
            <div class="flex items-center justify-between gap-2">
                <p class="font-bold">{{ $config['label'] }}</p>
                <label class="form-check text-xs text-muted">
                    <input type="checkbox" class="form-checkbox" data-permission-all aria-label="تحديد كل صلاحيات {{ $config['label'] }}">
                    <span>الكل</span>
                </label>
            </div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                @foreach ($config['abilities'] as $ability => $label)
                    @php
                        $key = "$module.$ability";
                        $fromRole = isset($inherited[$key]);
                        $locked = $fromRole || ($grantable !== null && ! isset($grantable[$key]));
                    @endphp
                    <label @class(['form-check text-sm', 'opacity-60' => $locked]) @if ($fromRole) title="من الدور" @elseif ($locked) title="لا تملك هذه الصلاحية لتمنحها" @endif>
                        <input type="checkbox" class="form-checkbox" name="{{ $name }}[]" value="{{ $key }}"
                               @checked($fromRole || isset($selected[$key])) @disabled($locked) data-permission-item>
                        <span>{{ $label }} @if ($fromRole)<span class="text-[10px] text-primary-600 font-bold">(الدور)</span>@endif</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function sync(module) {
                    var items = [].slice.call(module.querySelectorAll('[data-permission-item]:not(:disabled)'));
                    var all = module.querySelector('[data-permission-all]');
                    var on = items.filter(function (i) { return i.checked; }).length;
                    all.checked = items.length > 0 && on === items.length;
                    all.indeterminate = on > 0 && on < items.length;
                    all.disabled = items.length === 0;
                }
                document.querySelectorAll('[data-permission-module]').forEach(function (module) {
                    sync(module);
                    module.addEventListener('change', function (e) {
                        if (e.target.matches('[data-permission-all]')) {
                            module.querySelectorAll('[data-permission-item]:not(:disabled)').forEach(function (i) { i.checked = e.target.checked; });
                        }
                        sync(module);
                    });
                });
            })();
        </script>
    @endpush
@endonce
