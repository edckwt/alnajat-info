@props(['breadcrumb' => []])
@php($user = auth()->user())
<header class="app-header">
    <button type="button" class="btn btn-icon btn-ghost lg:hidden" data-sidebar-toggle aria-label="{{ __('admin.menu') }}">
        <x-admin.icon name="menu" />
    </button>

    <nav class="breadcrumb hidden sm:flex">
        <a href="{{ route('admin.dashboard') }}">{{ __('admin.panel') }}</a>
        @foreach ($breadcrumb as $label => $url)
            <span class="sep"><x-admin.icon name="chevronEnd" class="w-4 h-4 flip-rtl" /></span>
            @if ($loop->last || ! $url)
                <span class="font-semibold text-ink">{{ is_int($label) ? $url : $label }}</span>
            @else
                <a href="{{ $url }}">{{ $label }}</a>
            @endif
        @endforeach
    </nav>

    <div class="flex-1"></div>

    <a href="{{ url('/') }}" target="_blank" class="btn btn-icon btn-ghost" title="{{ __('admin.view_site') }}">
        <x-admin.icon name="site" />
    </a>

    <button type="button" class="btn btn-icon btn-ghost" data-mode-btn title="{{ __('admin.dark_mode') }}" aria-label="{{ __('admin.dark_mode') }}">
        <span class="dark:hidden"><x-admin.icon name="moon" /></span><span class="hidden dark:inline"><x-admin.icon name="sun" /></span>
    </button>

    <div class="dropdown">
        <button type="button" class="btn btn-icon btn-ghost" data-dropdown-toggle aria-expanded="false" title="{{ __('admin.theme') }}">
            <x-admin.icon name="palette" />
        </button>
        <div class="dropdown-menu end-0 !min-w-[17rem] !p-4" data-dropdown-menu hidden id="themeMenu"></div>
    </div>

    <div class="dropdown ms-1">
        <button type="button" class="flex items-center gap-2.5 rounded-full p-1 ps-2 hover:bg-surface-2 transition" data-dropdown-toggle aria-expanded="false">
            <span class="hidden sm:block text-end leading-tight">
                <span class="block text-xs font-bold text-ink">{{ $user->name }}</span>
                <span class="block text-[10px] text-muted">{{ $user->roleName() }}</span>
            </span>
            <x-admin.avatar :user="$user" class="avatar-sm ring-2 ring-primary-500/30" />
        </button>
        <div class="dropdown-menu end-0 !min-w-[15rem]" data-dropdown-menu hidden>
            <div class="flex items-center gap-3 px-3 py-3 border-b border-line mb-1.5">
                <x-admin.avatar :user="$user" class="avatar-md" />
                <div class="min-w-0">
                    <p class="text-sm font-bold text-ink truncate">{{ $user->name }}</p>
                    <p class="text-xs text-muted mt-0.5 truncate" dir="ltr">{{ $user->email ?? $user->username }}</p>
                </div>
            </div>
            <a href="{{ route('admin.profile.show') }}" class="dropdown-item"><x-admin.icon name="user" /><span>الملف الشخصي</span></a>
            <a href="{{ route('admin.profile.show', ['tab' => 'security']) }}" class="dropdown-item"><x-admin.icon name="lock" /><span>تغيير كلمة المرور</span></a>
            <div class="my-1.5 border-t border-line"></div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-item-danger w-full">
                    <x-admin.icon name="logout" /><span>{{ __('admin.logout') }}</span>
                </button>
            </form>
        </div>
    </div>
</header>
