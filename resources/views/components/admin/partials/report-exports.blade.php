<div class="report-controlbar__exports">
    @if ($pdfUrl)
        <a href="{{ $pdfUrl }}" class="report-export">
            <i class="fa-regular fa-file-pdf"></i><span>{{ __('PDF') }}</span>
        </a>
    @endif
    @if ($exportUrl)
        <a href="{{ $exportUrl }}" class="report-export report-export--primary">
            <i class="fa-solid fa-download"></i><span>{{ $exportLabel }}</span>
        </a>
    @endif
</div>
