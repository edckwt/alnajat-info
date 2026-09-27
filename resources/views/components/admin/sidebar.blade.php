@php
    // عناصر القائمة؛ كل عنصر يظهر حسب صلاحيات العضو (config/permissions.php).
    $menu = [
        ['route' => 'admin.dashboard', 'icon' => 'home', 'label' => __('admin.nav.dashboard')],
        ['section' => __('admin.nav.content')],
        ['route' => 'admin.news.index', 'icon' => 'news', 'label' => __('admin.nav.news'), 'can' => ['news.view'],
            'active' => ['admin.news.index', 'admin.news.create', 'admin.news.edit']],
        ['route' => 'admin.news.order', 'icon' => 'sort', 'label' => __('admin.nav.news_order'), 'can' => ['news.order'],
            'active' => ['admin.news.order*']],
        ['route' => 'admin.publications.index', 'icon' => 'pdf', 'label' => __('admin.nav.publications'), 'can' => ['publications.view']],
        ['route' => 'admin.pdf-templates.index', 'icon' => 'palette', 'label' => __('admin.nav.pdf_templates'), 'can' => ['pdf_templates.view']],
        ['route' => 'admin.categories.index', 'icon' => 'folder', 'label' => __('admin.nav.categories'), 'can' => ['categories.view']],
        ['route' => 'admin.newspapers.index', 'icon' => 'paper', 'label' => __('admin.nav.newspapers'), 'can' => ['newspapers.view']],
        ['route' => 'admin.banners.index', 'icon' => 'image', 'label' => __('admin.nav.banners'), 'can' => ['banners.view']],
        ['route' => 'admin.uploads.index', 'icon' => 'upload', 'label' => __('admin.nav.uploads'), 'can' => ['uploads.view']],
        ['section' => __('admin.nav.system'), 'can' => ['users.view', 'roles.view', 'settings.general', 'settings.home', 'settings.pdf', 'settings.pdf_boxes', 'missing_links.view']],
        ['route' => 'admin.users.index', 'icon' => 'users', 'label' => __('admin.nav.users'), 'can' => ['users.view']],
        ['route' => 'admin.roles.index', 'icon' => 'user', 'label' => __('admin.nav.roles'), 'can' => ['roles.view']],
        ['route' => 'admin.settings.edit', 'icon' => 'gear', 'label' => __('admin.nav.settings'), 'can' => ['settings.general', 'settings.home', 'settings.pdf', 'settings.pdf_boxes']],
        ['route' => 'admin.missing-links.index', 'icon' => 'link', 'label' => __('admin.nav.missing_links'), 'can' => ['missing_links.view']],
    ];
@endphp
<aside class="app-sidebar" id="appSidebar">
    <div class="flex items-center gap-3 h-[var(--header-h)] px-5 shrink-0">
        <span class="w-9 h-9 rounded-xl bg-sidebar-ink/15 grid place-items-center text-sidebar-ink shrink-0">
            <x-admin.icon name="logo" class="w-5 h-5" />
        </span>
        <span class="brand-text font-extrabold text-lg text-sidebar-ink">{{ __('admin.brand') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto scroll-thin px-3 pb-6">
        <ul class="space-y-1">
            @foreach ($menu as $item)
                {{-- يظهر العنصر لمن يملك أياً من صلاحياته --}}
                @if (isset($item['can']) && ! collect($item['can'])->contains(fn ($ability) => auth()->user()?->can($ability)))
                    @continue
                @endif
                @if (isset($item['section']))
                    <li><p class="nav-section">{{ $item['section'] }}</p></li>
                    @continue
                @endif
                @php
                    $exists = Route::has($item['route']);
                    $prefix = \Illuminate\Support\Str::beforeLast($item['route'], '.');
                    $patterns = $item['active'] ?? [$item['route'], $prefix !== 'admin' ? $prefix.'.*' : $item['route']];
                    $active = $exists && request()->routeIs(...$patterns);
                @endphp
                <li>
                    <a href="{{ $exists ? route($item['route']) : '#' }}"
                       @class(['nav-link', 'is-active' => $active, 'opacity-40 pointer-events-none' => ! $exists])
                       @if (! $exists) aria-disabled="true" title="{{ __('admin.soon') }}" @endif
                       data-tooltip-pos="end">
                        <x-admin.icon :name="$item['icon']" />
                        <span class="nav-text">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="p-3 shrink-0 border-t border-sidebar-line/50">
        <button type="button" class="nav-link w-full" data-sidebar-collapse>
            <x-admin.icon name="collapse" />
            <span class="nav-text text-xs">{{ __('admin.collapse_menu') }}</span>
        </button>
    </div>
</aside>
<div class="fixed inset-0 z-[45] bg-slate-900/50 backdrop-blur-sm lg:hidden hidden" data-sidebar-backdrop id="sidebarBackdrop"></div>
