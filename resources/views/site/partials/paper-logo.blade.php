@if (blank($paperLogo) || $showPaperName)
    @if (filled($paperName))<div class="news-logo"><p>{{ $paperName }}</p></div>@endif
@else
    <div class="news-logo withImage"><img src="{{ $paperLogo }}" width="140" height="70" alt="{{ $paperName }}" title="{{ $paperName }}"></div>
@endif
