<x-admin.layout :title="__('admin.nav.dashboard')" :breadcrumb="[__('admin.nav.dashboard') => null]">
    <div class="grid grid-cols-12 gap-6">
        @foreach ($stats as $stat)
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
                <div class="card card-body">
                    <span class="stat-icon {{ $stat['tone'] }} mb-5">
                        <x-admin.icon :name="$stat['icon']" class="w-6 h-6" />
                    </span>
                    <p class="text-3xl font-extrabold tracking-tight">{{ number_format($stat['value']) }}</p>
                    <p class="text-sm text-muted mt-1">{{ $stat['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="font-bold text-lg">{{ __('admin.dashboard.latest_news') }}</h2>
        </div>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr class="border-b border-line">
                        <th>{{ __('admin.fields.title') }}</th>
                        <th>{{ __('admin.fields.published_date') }}</th>
                        <th>{{ __('admin.fields.categories') }}</th>
                        <th>{{ __('admin.fields.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestNews as $news)
                        <tr>
                            <td class="font-semibold">{{ $news->title }}</td>
                            <td class="text-muted">{{ $news->published_date?->format('Y-m-d') }}</td>
                            <td class="text-muted">{{ $news->categories->pluck('name')->join('، ') }}</td>
                            <td>
                                @if ($news->is_active)
                                    <span class="badge badge-success">{{ __('admin.status.published') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('admin.status.hidden') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
