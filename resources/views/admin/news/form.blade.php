@php
    $editing = $news->exists;
    $fields = $type['fields'];
    $has = fn (string $field) => in_array($field, $fields, true);
    $label = fn (string $field, string $default) => $type['labels'][$field] ?? $default;
    $pageTitle = $editing ? 'تعديل '.$type['label'] : ($duplicateOf ? 'نسخة مطابقة' : 'إضافة '.$type['label']);
    $singleCategory = count($type['categories']) === 1;
@endphp
<x-admin.layout :title="$pageTitle" :breadcrumb="['الأخبار' => route('admin.news.index'), $pageTitle => null]">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('admin.news.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 hover:underline">
                <x-admin.icon name="chevronStart" class="w-3.5 h-3.5 flip-rtl" /> عودة للأخبار
            </a>
            <h1 class="text-2xl font-extrabold mt-2 flex items-center gap-2">
                <x-admin.icon :name="$type['icon']" class="w-6 h-6 text-primary-600" /> {{ $pageTitle }}
            </h1>
            @if ($editing)
                <p class="text-xs text-muted mt-1">
                    #{{ $news->id }} · {{ number_format($news->views) }} مشاهدة
                    @if ($news->updated_at) · آخر تعديل {{ $news->updated_at->format('Y-m-d H:i') }} @endif
                </p>
            @endif
        </div>
        @if ($editing && auth()->user()->can('news.create'))
            <a href="{{ route('admin.news.create', ['duplicate' => $news->id]) }}" class="btn btn-secondary btn-sm gap-2">
                <x-admin.icon name="copy" class="w-4 h-4" /> نسخة مطابقة بتاريخ اليوم
            </a>
        @endif
    </div>

    @unless ($editing || $duplicateOf)
        <div class="tabs-pill flex-wrap">
            @foreach ($types as $id => $t)
                <a href="{{ route('admin.news.create', ['type' => $id]) }}" class="tab flex items-center gap-2"
                   aria-selected="{{ $news->type == $id ? 'true' : 'false' }}">
                    <x-admin.icon :name="$t['icon']" class="w-4 h-4" /> {{ $t['label'] }}
                </a>
            @endforeach
        </div>
    @endunless

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
            <div>
                <p class="font-bold">لم يُحفظ الخبر:</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.news.update', $news) : route('admin.news.store') }}"
          class="grid grid-cols-12 gap-6 items-start">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="type" value="{{ $news->type }}">

        {{-- ===================== المحتوى ===================== --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="card">
                <div class="card-body space-y-5">
                    <div>
                        <label class="form-label" for="title">العنوان</label>
                        <input @class(['form-input', 'is-invalid' => $errors->has('title')]) id="title" name="title" type="text"
                               value="{{ old('title', $news->title) }}" required maxlength="255">
                        @error('title')<p class="form-error-text">{{ $message }}</p>@enderror
                    </div>

                    @if ($has('description'))
                        <div>
                            <label class="form-label" for="description">{{ $label('description', 'الوصف') }}</label>
                            <textarea @class(['form-textarea', 'is-invalid' => $errors->has('description')]) id="description" name="description" rows="3">{{ old('description', $news->description) }}</textarea>
                            @error('description')<p class="form-error-text">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    @foreach (['source_url' => 'المصدر', 'sound_url' => 'رابط الصوت', 'video_url' => 'رابط الفيديو', 'tweet_url' => 'رابط التغريدة'] as $field => $default)
                        @if ($has($field))
                            <div>
                                <label class="form-label" for="{{ $field }}">{{ $label($field, $default) }}</label>
                                <div class="input-group">
                                    <span class="input-group-icon"><x-admin.icon name="link" class="w-4 h-4" /></span>
                                    <input @class(['form-input', 'is-invalid' => $errors->has($field)]) id="{{ $field }}" name="{{ $field }}" type="text" dir="ltr"
                                           value="{{ old($field, $news->{$field}) }}">
                                </div>
                                @error($field)<p class="form-error-text">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($has('body'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">التفاصيل</h2></div>
                    <div class="card-body">
                        <textarea id="body" name="body" class="form-textarea" rows="16">{{ old('body', $news->body) }}</textarea>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===================== الجانب ===================== --}}
        <aside class="col-span-12 lg:col-span-4 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">النشر</h2></div>
                <div class="card-body space-y-5">
                    <div>
                        <label class="form-label" for="published_date">تاريخ النشر</label>
                        <input @class(['form-input', 'is-invalid' => $errors->has('published_date')]) id="published_date" name="published_date" type="date" required
                               value="{{ old('published_date', $news->published_date?->format('Y-m-d')) }}">
                        <p class="form-hint">يظهر الخبر في نشرة هذا اليوم، وتُنشأ النشرة تلقائياً إن لم تكن موجودة.</p>
                        @error('published_date')<p class="form-error-text">{{ $message }}</p>@enderror
                    </div>
                    @if (auth()->user()->can('news.publish', $editing ? [$news] : []))
                        <label class="form-switch gap-3 w-full">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $news->is_active))>
                            <span class="switch-track"></span><span class="switch-thumb"></span>
                            <span class="text-sm ms-2">منشور على الموقع</span>
                        </label>
                    @else
                        <input type="hidden" name="is_active" value="{{ $news->is_active ? 1 : 0 }}">
                        <p class="text-sm text-muted">الحالة: <x-admin.status :active="$editing && $news->is_active" /> — النشر يحتاج صلاحية «نشر وإخفاء».</p>
                    @endif
                </div>
                <div class="card-footer flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary gap-2"><x-admin.icon name="save" class="w-4 h-4" /> {{ $editing ? 'حفظ التعديلات' : 'إضافة' }}</button>
                    @unless ($editing)
                        <button type="submit" name="add_another" value="1" class="btn btn-secondary">إضافة وإضافة آخر</button>
                    @endunless
                </div>
            </div>

            @if ($has('categories'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">الأقسام</h2></div>
                    <div class="card-body">
                        @if ($singleCategory)
                            <p class="text-sm text-muted">يُضاف تلقائياً إلى قسم «{{ $categories->first()?->name }}».</p>
                        @else
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($categories as $category)
                                    <label class="form-check">
                                        <input type="checkbox" class="form-checkbox" name="categories[]" value="{{ $category->id }}"
                                               @checked(in_array($category->id, $selectedCategories))>
                                        <span class="text-sm">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('categories.*')<p class="form-error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            @if ($has('image'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">الصورة</h2></div>
                    <div class="card-body space-y-4">
                        <x-admin.dropzone name="image_file" accept="image/*" :current="$news->image_url"
                                          label="اسحب صورة الخبر وأفلتها هنا" />
                        @error('image_file')<p class="form-error-text">{{ $message }}</p>@enderror
                        <div>
                            <label class="form-label" for="image">أو رابط/مسار الصورة</label>
                            <input class="form-input" id="image" name="image" type="text" dir="ltr" value="{{ old('image', $news->image) }}">
                            <p class="form-hint">مثال: upload/news_123.jpg</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($has('newspaper') || $has('newspaper_number'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">الصحيفة</h2></div>
                    <div class="card-body space-y-5">
                        @if ($has('newspaper'))
                            <div>
                                <label class="form-label" for="newspaper_id">الصحيفة</label>
                                <select class="form-select" id="newspaper_id" name="newspaper_id">
                                    <option value="">- - -</option>
                                    @foreach ([0 => 'ورقية', 1 => 'مواقع إلكترونية'] as $group => $groupLabel)
                                        @if (isset($newspapers[$group]))
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach ($newspapers[$group] as $paper)
                                                    <option value="{{ $paper->id }}" @selected(old('newspaper_id', $news->newspaper_id) == $paper->id)>{{ $paper->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                                @error('newspaper_id')<p class="form-error-text">{{ $message }}</p>@enderror
                            </div>
                        @endif
                        @if ($has('newspaper_number'))
                            <div>
                                <label class="form-label" for="newspaper_number">رقم العدد</label>
                                <input class="form-input" id="newspaper_number" name="newspaper_number" type="number" min="0"
                                       value="{{ old('newspaper_number', $news->newspaper_number) }}">
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($has('pdf_flags'))
                <div class="card">
                    <div class="card-header"><h2 class="card-title">في ملف الـ PDF</h2></div>
                    <div class="card-body space-y-4">
                        @foreach (['hide_in_pdf' => 'إخفاء الخبر من ملف الـ PDF', 'hide_title' => 'إخفاء العنوان', 'hide_description' => 'إخفاء الوصف', 'hide_more' => 'إخفاء رابط «اقرأ المزيد»'] as $flag => $flagLabel)
                            <label class="form-switch gap-3 w-full">
                                <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $news->{$flag}))>
                                <span class="switch-track"></span><span class="switch-thumb"></span>
                                <span class="text-sm ms-2">{{ $flagLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($editing)
                <div class="card card-body">
                    <p class="text-sm text-muted mb-3">حذف الخبر نهائياً مع ربطه بالأقسام.</p>
                    <button type="submit" form="delete-news" class="btn btn-soft-danger btn-sm gap-2">
                        <x-admin.icon name="trash" class="w-4 h-4" /> حذف الخبر
                    </button>
                </div>
            @endif
        </aside>
    </form>

    @if ($editing)
        <form id="delete-news" method="POST" action="{{ route('admin.news.destroy', $news) }}" data-confirm="حذف الخبر «{{ $news->title }}» نهائياً؟" hidden>
            @csrf @method('DELETE')
        </form>
    @endif

    @if ($has('body'))
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
            <script>
                tinymce.init({
                    selector: '#body',
                    license_key: 'gpl',
                    language: 'ar',
                    language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@26.9.21/langs7/ar.js',
                    directionality: 'rtl',
                    height: 520,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    convert_urls: false,
                    plugins: 'advlist autolink lists link image charmap preview searchreplace visualblocks code fullscreen media table wordcount directionality',
                    toolbar: 'undo redo | blocks | bold italic underline forecolor | alignright aligncenter alignleft alignjustify | bullist numlist | link image media table | rtl ltr | removeformat code fullscreen',
                    skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                    content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                });
            </script>
        @endpush
    @endif
</x-admin.layout>
