@props(['title' => null])
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title ? $title.' · ' : '' }}{{ __('admin.panel') }} · {{ __('admin.brand') }}</title>
<link rel="stylesheet" href="{{ asset('admin/vendor/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/vendor/tom-select/tom-select.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/app.css') }}?v={{ filemtime(public_path('admin/css/app.css')) }}">
{{-- يطبّق ثيم روبيك المحفوظ قبل رسم الصفحة (بلا وميض). اللغة والاتجاه يحددهما الخادم. --}}
<script>
  (function () {
    try {
      var s = JSON.parse(localStorage.getItem('rubick.ui') || '{}');
      var r = document.documentElement;
      r.setAttribute('data-theme', s.theme || 'blue');
      r.setAttribute('data-sidebar', s.sidebar || 'colored');
      r.setAttribute('data-layout', s.layout || 'side');
      r.setAttribute('data-frame', s.frame || 'framed');
      if (s.mode === 'dark') r.classList.add('dark');
    } catch (e) {}
  })();
</script>
