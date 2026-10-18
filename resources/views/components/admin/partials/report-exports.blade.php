<div class="report-more" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    @if ($exportsButton ?? false)
        {{-- Labelled button: used beside the tabs when the page has no filter row. --}}
        <button type="button" class="report-tabs-bar__button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">
            <i class="fa-solid fa-file-export"></i><span>{{ __('Export') }}</span>
            <i class="fa-solid fa-chevron-down report-more__caret" :class="{ 'is-open': open }"></i>
        </button>
    @else
        <button type="button" class="report-more__toggle" :class="{ 'is-open': open }" @click="open = !open"
            :aria-expanded="open" aria-haspopup="menu" title="{{ __('Export') }}" aria-label="{{ __('Export options') }}">
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
    @endif
    <div class="report-more__menu" x-show="open" x-cloak role="menu"
        x-transition:enter="report-more-enter" x-transition:enter-start="report-more-from"
        x-transition:leave="report-more-leave" x-transition:leave-end="report-more-from">
        <p class="report-more__title">{{ __('Export') }}</p>
        @if ($pdfUrl)
            <a href="{{ $pdfUrl }}" class="report-more__item" role="menuitem" @click="open = false">
                <i class="fa-regular fa-file-pdf"></i><span>{{ __('Download PDF') }}</span>
            </a>
        @endif
        @if ($exportUrl)
            <a href="{{ $exportUrl }}" class="report-more__item" role="menuitem" @click="open = false">
                <i class="fa-regular fa-file-lines"></i><span>{{ __('Download :format', ['format' => $exportLabel]) }}</span>
            </a>
        @endif
    </div>
</div>
