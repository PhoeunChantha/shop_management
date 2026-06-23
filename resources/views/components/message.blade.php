@if(session()->has('success'))
<div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-5 shadow-sm">
    <i class="fa-solid fa-circle-check text-green-500"></i>
    <span class="text-sm font-medium">{{ session()->get('success') }}</span>
</div>
@endif

@if(session()->has('error') || session()->has('fail'))
<div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-5 shadow-sm">
    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
    <span class="text-sm font-medium">{{ session()->get('error') ?? session()->get('fail') }}</span>
</div>
@endif
