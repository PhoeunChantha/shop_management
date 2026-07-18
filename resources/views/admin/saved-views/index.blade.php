<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Operations</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Saved Views') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page saved-views-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Table Presets</p>
                <h3>Saved Views</h3>
                <p class="text-secondary mb-0">Reusable filters for repeated admin workflows.</p>
            </div>
        </div>

        <div class="saved-views-grid">
            @forelse ($groups as $scope => $views)
                <section class="saved-views-panel">
                    <div class="saved-views-panel__head">
                        <div>
                            <p class="section-kicker">{{ str($scope)->headline() }}</p>
                            <h4>{{ $views->count() }} presets</h4>
                        </div>
                        <i class="fa-solid fa-bookmark"></i>
                    </div>

                    <div class="saved-views-list">
                        @foreach ($views as $view)
                            <div class="saved-views-row" style="--view-color: {{ $view->color }};">
                                <span class="saved-views-row__icon"><i class="fa-solid {{ $view->icon }}"></i></span>
                                <div class="saved-views-row__copy">
                                    <strong>{{ $view->name }}</strong>
                                    <small>{{ $view->is_global ? 'Global preset' : 'Personal preset' }} {{ $view->user ? '- '.$view->user->name : '' }}</small>
                                </div>
                                <a href="{{ route($view->route_name, $view->query ?? []) }}" class="ghost-button ghost-button--panel">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Open</span>
                                </a>
                                @if (! $view->is_global || auth()->user()?->can('delete saved views'))
                                    <form method="POST" action="{{ route('admin.saved-views.destroy', $view) }}" class="mb-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="saved-views-row__delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <x-admin.empty-state icon="fa-solid fa-bookmark" title="No saved views yet" message="Save a filtered table view from products, orders, customers, returns, or media." />
            @endforelse
        </div>
    </div>
</x-app-layout>
