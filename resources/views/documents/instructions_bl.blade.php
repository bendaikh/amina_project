<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Instructions pour établissement du connaissement — {{ $export->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin: 16px; }
        h1 { font-size: 14px; margin: 0; text-align: center; text-transform: uppercase; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 6px 8px; vertical-align: top; }
        .sheet { border: 1px solid #222; }
        .title-row td { background: #f3f4f6; padding: 10px 8px; }
        .label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #333; margin-bottom: 4px; }
        .name { font-weight: 700; font-size: 12px; margin: 0 0 2px; }
        .line { margin: 0; line-height: 1.45; }
        .muted { color: #888; }
        .party { width: 58%; }
        .side-title { width: 42%; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: .03em; }
        .ship-label { width: 18%; background: #f3f4f6; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .ship-value { font-weight: 700; font-size: 12px; }
        .sec { background: #e5e7eb; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .hs { font-weight: 700; margin-top: 8px; }
        .hs:first-child { margin-top: 0; }
        .goods { margin: 2px 0 0; }
        .totals { margin-top: 10px; font-weight: 700; }
        .box { display: inline-block; width: 14px; height: 14px; border: 1px solid #222; margin-right: 6px; vertical-align: -2px; }
        .stamp { min-height: 120px; }
        .hint { font-size: 9px; color: #444; margin-top: 8px; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body>
@php
    $marchandises = $bl['marchandises'];
    $conteneurs = $bl['conteneurs'];
    $rowspan = max(1, count($conteneurs));
@endphp

<table class="sheet">
    <tr class="title-row">
        <td colspan="4">
            <h1>Instructions pour établissement du connaissement</h1>
        </td>
    </tr>

    <tr>
        <td class="party" colspan="2">
            <div class="label">Shipper (chargeur)</div>
            <p class="name">{{ $bl['shipper_name'] ?: '—' }}</p>
            @forelse($bl['shipper_lines'] as $line)
                <p class="line">{{ $line }}</p>
            @empty
                <p class="line muted">—</p>
            @endforelse
        </td>
        <td class="side-title" colspan="2" rowspan="3">
            Instructions pour rédaction du connaissement
        </td>
    </tr>
    <tr>
        <td class="party" colspan="2">
            <div class="label">Consignee (destinataire)</div>
            <p class="name">{{ $bl['consignee_name'] ?: '—' }}</p>
            @foreach($bl['consignee_lines'] as $line)
                <p class="line">{{ $line }}</p>
            @endforeach
            @if($bl['eori'])
                <p class="line">numero EORI : {{ $bl['eori'] }}</p>
            @endif
        </td>
    </tr>
    <tr>
        <td class="party" colspan="2">
            <div class="label">Notify (transitaire)</div>
            @forelse($bl['notify_lines'] as $line)
                <p class="line">{{ $line }}</p>
            @empty
                <p class="line muted">—</p>
            @endforelse
        </td>
    </tr>

    <tr>
        <td class="ship-label">Navire</td>
        <td class="ship-value">{{ $bl['navire'] ?: '—' }}</td>
        <td class="ship-label">Voyage</td>
        <td class="ship-value">{{ $bl['voyage'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="ship-label">N° de réservation / booking</td>
        <td class="ship-value">{{ $bl['booking'] ?: '—' }}</td>
        <td class="ship-label">Port de chargement</td>
        <td class="ship-value">{{ $bl['port_chargement'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="ship-label">Compagnie maritime</td>
        <td class="ship-value">{{ $bl['compagnie'] ?: '—' }}</td>
        <td class="ship-label">Port de déchargement</td>
        <td class="ship-value">{{ $bl['port_dechargement'] ?: '—' }}</td>
    </tr>

    <tr>
        <th class="sec" colspan="2">N° conteneur</th>
        <th class="sec" colspan="2">Désignation des marchandises</th>
    </tr>

    @if($conteneurs === [])
        <tr>
            <td colspan="2">
                <p class="line"><strong>Container N°</strong> —</p>
                <p class="line">Tare : —</p>
                <p class="line">Plomb : —</p>
            </td>
            <td colspan="2">
                @include('documents.partials.bl_marchandises', ['marchandises' => $marchandises])
            </td>
        </tr>
    @else
        @foreach($conteneurs as $index => $conteneur)
            <tr>
                <td colspan="2">
                    <p class="line"><strong>Container N°</strong> {{ $conteneur['numero'] ?: '—' }}</p>
                    <p class="line">Tare : {{ $conteneur['tare'] ?: '—' }}</p>
                    <p class="line">Plomb : {{ $conteneur['plomb'] ?: '—' }}</p>
                </td>
                @if($index === 0)
                    <td colspan="2" rowspan="{{ $rowspan }}">
                        @include('documents.partials.bl_marchandises', ['marchandises' => $marchandises])
                    </td>
                @endif
            </tr>
        @endforeach
    @endif

    <tr>
        <th class="sec">Documentation</th>
        <th class="sec" colspan="2">Facturation</th>
        <th class="sec">Cachet shipper &amp; freight forwarder</th>
    </tr>
    <tr>
        <td>
            <p class="line">Nbre BLs originaux</p>
            <p class="line" style="margin-top:10px;">Nbre copies BL</p>
            <p class="line" style="margin-top:10px;">Waybills</p>
        </td>
        <td colspan="2">
            <p class="line"><span class="box"></span><span class="box"></span> P/C — au choix</p>
            @foreach($bl['frais'] as $frais)
                <p class="line" style="margin-top:6px;"><span class="box"></span><span class="box"></span> {{ $frais }}</p>
            @endforeach
            <p class="hint">P : prepaid &nbsp;&nbsp;&nbsp; C : collect</p>
        </td>
        <td class="stamp"></td>
    </tr>
</table>
</body>
</html>
