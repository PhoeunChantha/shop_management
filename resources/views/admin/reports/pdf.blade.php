<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 22px 26px; color: #101828; font-size: 11px; }
        .head { border-bottom: 2px solid #101828; padding-bottom: 10px; margin-bottom: 16px; }
        .head h1 { margin: 0; font-size: 20px; letter-spacing: -0.3px; }
        .head span { color: #667085; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #101828; color: #fff; text-align: left; padding: 7px 9px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
        tbody td { padding: 6px 9px; border-bottom: 1px solid #e4e7ec; }
        tbody tr:nth-child(even) td { background: #f9fafb; }
        .empty { padding: 24px; text-align: center; color: #667085; }
    </style>
</head>
<body>
    <div class="head">
        <span>{{ __('Analytics Report') }}</span>
        <h1>{{ $title }}</h1>
        <span>{{ __('Generated') }} {{ $generatedAt }}</span>
    </div>

    @if (count($rows) > 1)
        <table>
            <thead>
                <tr>
                    @foreach ($rows[0] as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (array_slice($rows, 1) as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="empty">{{ __('No data for the selected range.') }}</p>
    @endif
</body>
</html>
