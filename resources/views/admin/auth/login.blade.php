<!DOCTYPE html>
<html lang="ar" dir="rtl"
      data-theme="blue" data-sidebar="colored" data-layout="side" data-frame="framed">
<head>
    <x-admin.head :title="__('admin.login.title')" />
</head>
<body class="auth-body">
<div class="auth-shell">
    <aside class="auth-aside">
        <div class="relative z-10">
            <x-admin.logo size="lg" icon="w-10 h-10 rounded-2xl bg-white/15" text="font-extrabold text-xl" />
        </div>

        <div class="relative z-10 max-w-sm">
            <h2 class="text-3xl font-extrabold leading-snug">{{ __('admin.login.hero_title') }}</h2>
            <p class="text-sm opacity-80 mt-4 leading-relaxed">{{ __('admin.login.hero_sub') }}</p>
        </div>

        <p class="text-xs opacity-70 relative z-10">© {{ now()->year }} {{ __('admin.brand') }}</p>

        <svg class="absolute -bottom-20 -start-20 w-[28rem] h-[28rem] opacity-10" viewBox="0 0 200 200" aria-hidden="true">
            <circle cx="100" cy="100" r="98" fill="none" stroke="#fff" stroke-width="2"/>
            <circle cx="100" cy="100" r="70" fill="none" stroke="#fff" stroke-width="2"/>
            <circle cx="100" cy="100" r="42" fill="none" stroke="#fff" stroke-width="2"/>
        </svg>
    </aside>

    <main class="auth-panel">
        <div class="auth-tools">
            <button type="button" class="btn btn-icon btn-sm btn-ghost" data-mode-btn aria-label="{{ __('admin.dark_mode') }}">
                <span class="dark:hidden"><x-admin.icon name="moon" /></span>
                <span class="hidden dark:inline"><x-admin.icon name="sun" /></span>
            </button>
        </div>

        <div class="auth-card">
            <div class="lg:hidden mb-8">
                <x-admin.logo size="lg" :chip="true" icon="w-10 h-10 rounded-2xl bg-primary-600 text-white" text="font-extrabold text-xl" />
            </div>

            <h1 class="text-2xl font-extrabold">{{ __('admin.login.welcome') }}</h1>
            <p class="text-sm text-muted mt-2">{{ __('admin.login.subtitle') }}</p>

            @if ($errors->any())
                <div class="alert alert-danger mt-6" role="alert">
                    <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form class="mt-8 space-y-5" method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <div>
                    <label class="form-label" for="login">{{ __('admin.login.login') }}</label>
                    <div class="input-group">
                        <span class="input-group-icon"><x-admin.icon name="user" class="w-4 h-4" /></span>
                        <input class="form-input" id="login" name="login" type="text" value="{{ old('login') }}"
                               autocomplete="username" autofocus required>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="password">{{ __('admin.login.password') }}</label>
                    <div class="password-field">
                        <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-eye" data-password-toggle aria-label="{{ __('admin.login.show_password') }}" aria-pressed="false">
                            <x-admin.icon name="eye" class="eye-on w-5 h-5" />
                            <x-admin.icon name="eyeOff" class="eye-off w-5 h-5" />
                        </button>
                    </div>
                </div>

                <label class="form-check">
                    <input type="checkbox" class="form-checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span class="text-sm">{{ __('admin.login.remember') }}</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block btn-lg">{{ __('admin.login.submit') }}</button>
            </form>
        </div>
    </main>
</div>

<script src="{{ asset('admin/js/theme.js') }}"></script>
<script src="{{ asset('admin/js/ui.js') }}"></script>
</body>
</html>
