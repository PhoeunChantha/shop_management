@php
    // Map session flash keys to toastr methods. `fail` is treated as an error.
    $toasts = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error') ?? session('fail')],
        ['type' => 'warning', 'message' => session('warning')],
        ['type' => 'info', 'message' => session('info') ?? session('status')],
    ])->filter(fn ($t) => filled($t['message']))->values();
@endphp

@if ($toasts->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.toastr) {
                return;
            }
            @foreach ($toasts as $toast)
                window.toastr.{{ $toast['type'] }}(@json($toast['message']));
            @endforeach
        });
    </script>
@endif
