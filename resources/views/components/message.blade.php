@if(session()->has('success'))
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    {{ session()->get('success') }}
</div>
@endif

@if(session()->has('error'))
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    {{ session()->get('error') }}
</div>
@endif