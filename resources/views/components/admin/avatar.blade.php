{{-- صورة العضو، أو أول حرفين من اسمه على لون ثابت له. <x-admin.avatar :user="$user" class="avatar-sm" /> --}}
@props(['user'])
@php
    $url = $user?->avatarUrl();
    $tones = ['bg-primary-600', 'bg-success-600', 'bg-warning-600', 'bg-danger-600', 'bg-info-600'];
    $tone = $tones[crc32((string) ($user?->id ?? 0)) % count($tones)];
@endphp
<span {{ $attributes->merge(['class' => 'avatar '.($url ? '' : $tone.' text-white font-bold')]) }} data-avatar>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $user->name }}">
    @else
        <span aria-hidden="true">{{ $user?->initials() }}</span>
    @endif
</span>
