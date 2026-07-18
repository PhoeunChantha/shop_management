@php
    $currentRoute = $routeName ?? request()->route()?->getName();
    $currentQuery = request()->except('page');
    $savedViews = app(\App\Services\AdminSavedViewService::class)->forScope($scope, auth()->id());
@endphp

<section class="saved-view-bar">
    <div class="saved-view-bar__presets">
        <span class="saved-view-bar__label"><i class="fa-solid fa-bookmark"></i> Saved views</span>
        @forelse ($savedViews as $view)
            <a href="{{ route($view->route_name, $view->query ?? []) }}" class="saved-view-pill" style="--view-color: {{ $view->color }};">
                <i class="fa-solid {{ $view->icon }}"></i>
                <span>{{ $view->name }}</span>
            </a>
        @empty
            <span class="saved-view-empty">No presets yet</span>
        @endforelse
    </div>

    <details class="saved-view-save">
        <summary><i class="fa-solid fa-plus"></i><span>Save current</span></summary>
        <form method="POST" action="{{ route('admin.saved-views.store') }}" class="saved-view-save__form">
            @csrf
            <input type="hidden" name="scope" value="{{ $scope }}">
            <input type="hidden" name="route_name" value="{{ $currentRoute }}">
            <input type="hidden" name="query_json" value='@json($currentQuery)'>
            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
            <input type="hidden" name="icon" value="{{ $icon ?? 'fa-filter' }}">
            <input type="hidden" name="color" value="{{ $color ?? '#0f766e' }}">
            <label>
                <span>Preset name</span>
                <input type="text" name="name" maxlength="80" placeholder="e.g. Low stock" required>
            </label>
            @can('create saved views')
                <label class="saved-view-save__check">
                    <input type="checkbox" name="is_global" value="1">
                    <span>Global</span>
                </label>
            @endcan
            <button type="submit"><i class="fa-solid fa-bookmark"></i><span>Save</span></button>
        </form>
    </details>
</section>
