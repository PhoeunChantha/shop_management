<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">{{ __('Content intelligence') }}</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('SEO Manager') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page seo-page">
        <div class="seo-scoreboard">
            <div class="seo-scoreboard__hero">
                <span><i class="fa-solid fa-magnifying-glass-chart"></i></span>
                <div>
                    <p class="section-kicker mb-1">{{ __('Search readiness') }}</p>
                    <h3>{{ number_format($stats['healthy']) }} healthy records</h3>
                    <p>{{ __('Audit products, categories, and pages without opening every edit form.') }}</p>
                </div>
            </div>
            <div class="seo-stat"><span>{{ __('Total records') }}</span><strong>{{ number_format($stats['total']) }}</strong></div>
            <div class="seo-stat seo-stat--warn"><span>{{ __('Missing metadata') }}</span><strong>{{ number_format($stats['missing']) }}</strong></div>
            <div class="seo-stat seo-stat--risk"><span>{{ __('Duplicate signals') }}</span><strong>{{ number_format($stats['duplicates']) }}</strong></div>
        </div>

        <div class="page-section-header">
            <div>
                <p class="section-kicker">{{ __('SEO audit') }}</p>
                <h3>{{ __('Metadata Workbench') }}</h3>
            </div>
            <a href="{{ route('admin.seo.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-export"></i><span>{{ __('Export CSV') }}</span>
            </a>
        </div>

        <x-filter-card :action="route('admin.seo.index')" class="seo-filter-card">
            <x-select name="type" size="sm" :value="request('type')" placeholder="{{ __('All content types') }}" :options="$types" />
            <x-select name="issue" size="sm" :value="request('issue')" placeholder="{{ __('Any SEO issue') }}" :options="$issues" />
        </x-filter-card>

        <x-admin.table-card class="seo-table-card">
            <x-slot:toolbar>
                <x-table-toolbar>
                    <x-slot:left><x-per-page-selector :current="$perPage" /></x-slot:left>
                    <x-slot:right>
                        <x-search-input name="search" placeholder="{{ __('Search title, slug or metadata...') }}" />
                    </x-slot:right>
                </x-table-toolbar>
            </x-slot:toolbar>

            <table class="premium-table seo-table">
                <thead>
                    <tr>
                        <th>{{ __('Record') }}</th>
                        <th>{{ __('Score') }}</th>
                        <th>{{ __('Issues') }}</th>
                        <th>{{ __('Quick edit') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <div class="seo-record">
                                    <span class="seo-record__type">{{ str($item['type'])->substr(0, 1)->upper() }}</span>
                                    <div class="min-w-0">
                                        <strong>{{ $item['title'] }}</strong>
                                        <small>/{{ $item['slug'] }} · {{ $item['type_label'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="seo-score seo-score--{{ $item['score'] >= 80 ? 'good' : ($item['score'] >= 55 ? 'warn' : 'bad') }}">
                                    <span style="--score: {{ $item['score'] }}"></span>
                                    <strong>{{ $item['score'] }}</strong>
                                </div>
                            </td>
                            <td>
                                <div class="seo-issue-list">
                                    @forelse ($item['issues'] as $issue)
                                        <span>{{ $issue }}</span>
                                    @empty
                                        <span class="seo-issue-list__ok">{{ __('Healthy') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="seo-edit-cell">
                                <form method="POST" action="{{ route('admin.seo.update', [$item['type'], $item['id']]) }}" class="seo-inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="seo_title" value="{{ $item['seo_title'] }}" maxlength="255"
                                        class="form-input" placeholder="{{ __('SEO title') }}">
                                    <textarea name="seo_description" maxlength="500" class="form-input" rows="2"
                                        placeholder="{{ __('SEO description') }}">{{ $item['seo_description'] }}</textarea>
                                    <button type="submit" class="ghost-button ghost-button--panel">
                                        <i class="fa-solid fa-floppy-disk"></i><span>{{ __('Save') }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ $item['edit_url'] }}" class="ghost-button ghost-button--panel">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>{{ __('Open') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-admin.empty-state icon="fa-solid fa-magnifying-glass-chart" title="{{ __('No SEO records found') }}"
                                    message="{{ __('Adjust the filters or search term to audit more products, categories, and pages.') }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <x-slot:footer>
                <x-table-footer :paginator="$items" label="{{ __('records') }}" />
            </x-slot:footer>
        </x-admin.table-card>
    </div>
</x-app-layout>
