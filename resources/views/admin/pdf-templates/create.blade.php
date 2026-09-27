<x-admin.layout title="قالب جديد" :breadcrumb="['قوالب النشرة' => route('admin.pdf-templates.index'), 'قالب جديد' => null]">
    <x-admin.page-header title="قالب نشرة جديد" :back="route('admin.pdf-templates.index')" back-label="عودة للقوالب" />
    <x-admin.errors />

    <form method="POST" action="{{ route('admin.pdf-templates.store') }}" class="space-y-6">
        @csrf
        <div class="card max-w-2xl">
            <div class="card-body"><x-admin.input name="name" label="اسم القالب" :value="old('name', 'قالب '.now()->format('Y-m-d'))" required /></div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">ابدأ من</h2></div>
            <div class="card-body grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                <label class="choice">
                    <input type="radio" name="from" value="blank" @checked(old('from', 'blank') === 'blank')>
                    <span class="choice-card">
                        <span class="w-16 aspect-[210/297] rounded-md border-2 border-dashed border-line grid place-items-center text-muted"><x-admin.icon name="plus" class="w-5 h-5" /></span>
                        قالب فارغ مرتب
                    </span>
                </label>
                @foreach ($legacy as $item)
                    <label class="choice">
                        <input type="radio" name="from" value="v{{ $item['version'] }}" @checked(old('from') === 'v'.$item['version'])>
                        <span class="choice-card">
                            @if ($item['cover'])<img src="{{ asset($item['cover']) }}" alt="" class="w-16 aspect-[210/297] object-cover object-top rounded-md shadow-card">@endif
                            {{ $item['name'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @if ($templates->isNotEmpty())
                <div class="card-body border-t border-line">
                    <p class="form-label">أو نسخة من قالب مصمَّم</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($templates as $t)
                            <label class="choice"><input type="radio" name="from" value="t{{ $t->id }}" @checked(old('from') === 't'.$t->id)>
                                <span class="choice-card !py-2 !px-4 !flex-row">{{ $t->name }} <span class="badge badge-muted">{{ $t->isPublished() ? 'معتمد' : 'مسودة' }}</span></span></label>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="card-footer"><button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="palette" class="w-4 h-4" /> إنشاء وفتح المحرر</button></div>
        </div>
    </form>
</x-admin.layout>
