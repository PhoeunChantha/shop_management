<x-mail::message>
# {{ $campaign->title }}

{{ __('Hi') }} {{ $recipientName ?: __('there') }},

{{ $campaign->message }}

@if($campaign->url)
<x-mail::button :url="$campaign->url">
{{ __('Learn more') }}
</x-mail::button>
@endif

{{ __('Thanks,') }}<br>
{{ $storeName }}

<x-slot:subcopy>
{{ __("Don't want emails like this?") }} <a href="{{ $unsubscribeUrl }}">{{ __('Unsubscribe') }}</a>
</x-slot:subcopy>
</x-mail::message>
