<x-admin.layout title="قوالب النشرة" :breadcrumb="['قوالب النشرة' => null]">
    <x-admin.page-header title="قوالب النشرة" subtitle="صمّم الغلاف والصفحة المتكررة وصفحة الختام بالسحب والإفلات. المسودة تُعدّل، والمعتمد يُستخدم في النشرات ويُنسخ لتعديله.">
        @can('pdf_templates.create')
            <a href="{{ route('admin.pdf-templates.create') }}" class="btn btn-primary gap-2"><x-admin.icon name="plus" class="w-4 h-4" /> قالب جديد</a>
        @endcan
    </x-admin.page-header>
    <x-admin.errors />
    @include('admin.pdf-templates._assets-alert', ['templates' => $templates])

    <section class="space-y-4">
        <h2 class="text-lg font-extrabold">القوالب المصمَّمة</h2>
        @if ($templates->isEmpty())
            <div class="card card-body text-center py-12 space-y-3">
                <p class="font-bold">لا توجد قوالب مصمَّمة بعد.</p>
                <p class="text-sm text-muted">ابدأ بقالب فارغ، أو بنسخة من أحد القوالب القديمة أدناه لتعدّله.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($templates as $template)
                    <div class="card">
                        <div class="card-body space-y-4">
                            <a href="{{ route('admin.pdf-templates.edit', $template) }}" class="grid grid-cols-3 gap-2" title="{{ $template->isEditable() ? 'تصميم' : 'عرض' }}">
                                @foreach (['cover', 'section', 'last'] as $page)
                                    <x-admin.pdf-design-page :design="$template->design" :page="$page" />
                                @endforeach
                            </a>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-extrabold truncate">{{ $template->name }}</p>
                                    <p class="text-xs text-muted mt-1">
                                        {{ number_format($template->publications_count) }} نشرة
                                        @if ($template->based_on_version) · من القالب القديم {{ $template->based_on_version }} @endif
                                        · عُدّل {{ $template->updated_at?->format('Y-m-d') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap justify-end gap-1 shrink-0">
                                    @if ($template->is_default)<span class="badge badge-primary">الافتراضي</span>@endif
                                    <span @class(['badge', 'badge-success' => $template->isPublished(), 'badge-warning' => ! $template->isPublished()])>{{ $template->isPublished() ? 'معتمد' : 'مسودة' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.pdf-templates.edit', $template) }}" class="btn btn-sm btn-secondary gap-1.5">
                                <x-admin.icon :name="$template->isEditable() ? 'edit' : 'eye'" class="w-4 h-4" /> {{ $template->isEditable() && auth()->user()->can('pdf_templates.update') ? 'تصميم' : 'عرض' }}
                            </a>
                            @can('pdf_templates.create')
                                <form method="POST" action="{{ route('admin.pdf-templates.duplicate', $template) }}">@csrf
                                    <button type="submit" class="btn btn-sm btn-ghost gap-1.5"><x-admin.icon name="copy" class="w-4 h-4" /> نسخ</button>
                                </form>
                            @endcan
                            @can('pdf_templates.publish')
                                @if ($template->isEditable())
                                    <form method="POST" action="{{ route('admin.pdf-templates.publish', $template) }}" data-confirm="اعتماد القالب «{{ $template->name }}» ليصبح متاحاً للنشرات؟"
                                          data-confirm-title="اعتماد القالب" data-confirm-ok="اعتماد" data-confirm-tone="primary"
                                          data-confirm-note="بعد الاعتماد لا يُعدّل التصميم حتى لا تتغير النشرات التي تستخدمه، ويمكن نسخه لتعديله.">@csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-ghost gap-1.5 text-success-600"><x-admin.icon name="check" class="w-4 h-4" /> اعتماد</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.pdf-templates.default', $template) }}">@csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-ghost gap-1.5">{{ $template->is_default ? 'إلغاء الافتراضي' : 'جعله الافتراضي' }}</button>
                                    </form>
                                @endif
                            @endcan
                            @if (! $template->publications_count && ! $template->is_default)
                                <span class="ms-auto">
                                    <x-admin.actions permission="pdf_templates" :destroy="route('admin.pdf-templates.destroy', $template)" :confirm="'حذف القالب «'.$template->name.'»؟'" />
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-extrabold">القوالب القديمة</h2>
            <p class="text-sm text-muted mt-1">محفوظة كما هي للنشرات السابقة. أنشئ من أيّها قالباً مصمَّماً بنفس الصور لتعدّله.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
            @foreach ($legacy as $item)
                <div class="card card-body space-y-3">
                    @include('admin.publications._template-pages', ['template' => $item])
                    <div class="flex items-center justify-between gap-2">
                        <strong class="text-sm">{{ $item['name'] }}</strong>
                        <span class="badge badge-muted">v{{ $item['version'] }}</span>
                    </div>
                    <p class="text-xs text-muted">{{ number_format($item['count']) }} نشرة</p>
                    @can('pdf_templates.create')
                        <form method="POST" action="{{ route('admin.pdf-templates.store') }}">@csrf
                            <input type="hidden" name="from" value="v{{ $item['version'] }}">
                            <input type="hidden" name="name" value="{{ $item['name'] }} (مصمَّم)">
                            <button type="submit" class="btn btn-sm btn-outline btn-block gap-1.5"><x-admin.icon name="palette" class="w-4 h-4" /> إنشاء قالب مصمَّم منه</button>
                        </form>
                    @endcan
                </div>
            @endforeach
        </div>
    </section>
</x-admin.layout>
