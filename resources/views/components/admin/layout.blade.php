@props(['title' => null, 'breadcrumb' => []])
<!DOCTYPE html>
<html lang="ar" dir="rtl"
      data-theme="blue" data-sidebar="colored" data-layout="side" data-frame="framed">
<head>
    <x-admin.head :title="$title" />
    @stack('head')
</head>
<body>
    <x-admin.sidebar />

    <main class="app-main" id="appMain">
        <x-admin.header :breadcrumb="$breadcrumb" />

        <div class="app-content space-y-6">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <script src="{{ asset('admin/js/theme.js') }}"></script>
    <script src="{{ asset('admin/js/ui.js') }}"></script>
    <script src="{{ asset('admin/vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('admin/vendor/flatpickr/ar.js') }}"></script>
    <script src="{{ asset('admin/vendor/tom-select/tom-select.complete.min.js') }}"></script>
    <script src="{{ asset('admin/js/plugins.js') }}?v={{ filemtime(public_path('admin/js/plugins.js')) }}"></script>
    <script src="{{ asset('admin/js/image-editor.js') }}?v={{ filemtime(public_path('admin/js/image-editor.js')) }}"></script>
    <script src="{{ asset('admin/js/admin.js') }}?v={{ filemtime(public_path('admin/js/admin.js')) }}"></script>
    @stack('scripts')
</body>
</html>
