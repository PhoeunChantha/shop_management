<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
    <title>{{ config('app.name') }} Product Feed</title>
    <link>{{ route('frontend.home') }}</link>
    <description>{{ config('app.name') }} product catalog for Meta Commerce Manager / Google Merchant Center.</description>
@foreach ($items as $item)
    <item>
        <g:id>{{ $item['id'] }}</g:id>
        <title>{{ $item['title'] }}</title>
        <description>{{ $item['description'] }}</description>
        <link>{{ $item['link'] }}</link>
        <g:image_link>{{ $item['image_link'] }}</g:image_link>
        <g:availability>{{ $item['availability'] }}</g:availability>
        <g:price>{{ $item['price'] }}</g:price>
        <g:brand>{{ $item['brand'] }}</g:brand>
        <g:condition>{{ $item['condition'] }}</g:condition>
    </item>
@endforeach
</channel>
</rss>
