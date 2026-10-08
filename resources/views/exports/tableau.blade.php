<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .muted { color: #6b7280; }
        .company { margin-bottom: 10px; }
        .kpis { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .kpis td { border: 1px solid #e5e7eb; padding: 4px 6px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #0f766e; color: white; text-align: left; padding: 4px 6px; font-size: 10px; }
        table.data td { border-bottom: 1px solid #e5e7eb; padding: 3px 6px; font-size: 10px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="company">
        <strong>{{ $document['company']['nom'] ?? '' }}</strong><br>
        @if(!empty($document['company']['adresse'])){{ $document['company']['adresse'] }}<br>@endif
        @if(!empty($document['company']['telephone']) || !empty($document['company']['email']))
            {{ $document['company']['telephone'] ?? '' }} {{ $document['company']['email'] ?? '' }}<br>
        @endif
        @if(!empty($document['company']['ice']))ICE : {{ $document['company']['ice'] }} @endif
        @if(!empty($document['company']['if'])) · IF : {{ $document['company']['if'] }} @endif
        @if(!empty($document['company']['rc'])) · RC : {{ $document['company']['rc'] }} @endif
        @if(!empty($document['company']['rib']))<br>RIB : {{ $document['company']['rib'] }}@endif
        @if(!empty($document['company']['iban'])) · IBAN : {{ $document['company']['iban'] }}@endif
    </div>
    <h1>{{ $document['title'] }}</h1>
    <p class="muted">Généré le {{ $document['generated_at'] }} · Montants en dirhams (DH) sauf indication de devise</p>
    @if(!empty($document['note']))<p class="muted">{{ $document['note'] }}</p>@endif

    @if(!empty($document['kpis']))
        <table class="kpis">
            @foreach(array_chunk($document['kpis'], 4) as $chunk)
                <tr>
                    @foreach($chunk as $kpi)
                        <td><span class="muted">{{ $kpi['label'] }}</span><br><strong>{{ is_numeric($kpi['value']) ? number_format((float) $kpi['value'], 2, ',', ' ') : $kpi['value'] }}</strong></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    @foreach($document['sections'] as $section)
        <h2>{{ $section['title'] }}</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach($section['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ is_float($cell) || (is_numeric($cell) && str_contains((string) $cell, '.')) ? number_format((float) $cell, 2, ',', ' ') : $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(count($section['headers']), 1) }}">Aucune donnée</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
