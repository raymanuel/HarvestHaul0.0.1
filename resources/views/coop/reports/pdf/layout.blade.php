<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 4px; }
        .meta strong { color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; }
        .summary { margin-top: 16px; }
        .summary td { border: none; padding: 2px 0; }
        .summary td:first-child { font-weight: bold; width: 200px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta"><strong>Cooperative:</strong> {{ $cooperativeName }}</p>
    <p class="meta"><strong>Reporting Period:</strong> {{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }}</p>
    <p class="meta"><strong>Generated:</strong> {{ now()->format('M d, Y g:i A') }}</p>

    <table class="summary">
        @foreach($summaryLines as $line)
            <tr><td>{{ $line[0] }}</td><td>{{ $line[1] }}</td></tr>
        @endforeach
    </table>

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">No records in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
