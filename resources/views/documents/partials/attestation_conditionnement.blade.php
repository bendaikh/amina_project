@php
    $a = $attestation ?? [];
    $fab = $a['fabricant'] ?? [];
    $exp = $a['exportateur'] ?? [];
    $pied = $a['pied'] ?? [];
    $lignes = $a['lignes'] ?? [];
    $txt = fn ($v) => filled($v) ? $v : '—';
    $kg = function ($v) {
        if ($v === null || $v === '') {
            return '';
        }
        $n = (float) $v;
        $dec = abs($n - round($n, 3)) < 0.0005 && abs($n - round($n)) > 0.0005 ? 3 : (abs($n - round($n)) < 0.0005 ? 0 : 3);

        return number_format($n, $dec, ',', ' ');
    };
    $day = function ($v) {
        if (!$v) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($v)->format('d / m / Y');
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };
    $pad = max(0, 7 - count($lignes));
@endphp
<style>
    .att { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 10px; }
    .att-green { background: #1e6b3a; color: #fff; }
    .att-h { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .att-h td { border: none; vertical-align: middle; padding: 0; }
    .att-logo { width: 78px; height: 52px; border: 1px dashed #888; text-align: center; color: #888; font-size: 9px; line-height: 52px; }
    .att-title { text-align: center; color: #1e6b3a; font-size: 16px; font-weight: 700; letter-spacing: .04em; margin: 0; }
    .att-title span { display: block; font-size: 13px; letter-spacing: .08em; }
    .att-meta { text-align: right; font-size: 10px; }
    .att-intro { font-size: 10px; line-height: 1.45; margin: 6px 0 8px; }
    .att-parties { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .att-parties > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; border: none; }
    .att-box { width: 100%; border-collapse: collapse; }
    .att-box th { background: #1e6b3a; color: #fff; text-align: left; font-size: 9px; padding: 4px 6px; border: 1px solid #1e6b3a; }
    .att-box td { border: 1px solid #1e6b3a; padding: 3px 6px; font-size: 9px; }
    .att-section { text-align: center; color: #1e6b3a; font-weight: 700; letter-spacing: .04em; margin: 8px 0 4px; font-size: 11px; }
    .att-goods { width: 100%; border-collapse: collapse; }
    .att-goods th { background: #1e6b3a; color: #fff; font-size: 8px; padding: 4px 3px; border: 1px solid #1e6b3a; text-align: center; }
    .att-goods td { border: 1px solid #c5d7cb; padding: 4px 3px; font-size: 8px; height: 16px; }
    .att-goods tr.alt td { background: #e7f3ea; }
    .att-goods tr.total td { font-weight: 700; background: #fff; border-color: #1e6b3a; }
    .att-legal { font-size: 9px; line-height: 1.4; margin: 8px 0; }
    .att-sign { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .att-sign td { border: none; vertical-align: top; padding: 0; }
    .att-stamp { width: 100%; border-collapse: collapse; }
    .att-stamp th { background: #1e6b3a; color: #fff; text-align: center; font-size: 9px; padding: 4px; border: 1px solid #1e6b3a; }
    .att-stamp td { border: 1px solid #1e6b3a; height: 68px; }
    .att-foot { margin-top: 16px; text-align: center; font-size: 8px; color: #333; line-height: 1.45; }
</style>
<div class="att">
    <table class="att-h">
        <tr>
            <td style="width:18%;"><div class="att-logo">LOGO</div></td>
            <td style="width:50%;">
                <p class="att-title">ATTESTATION<span>DE CONDITIONNEMENT</span></p>
            </td>
            <td class="att-meta" style="width:32%;">
                <div><strong>Attestation N°</strong> : {{ $txt($a['numero'] ?? null) }}</div>
                <div style="margin-top:4px;"><strong>Date</strong> : {{ $day($a['date'] ?? null) }}</div>
            </td>
        </tr>
    </table>

    <p class="att-intro">
        Nous soussignés, en qualité de fabricant de conserves alimentaires, attestons par la présente avoir conditionné
        {{ $a['marchandise'] ?? 'la marchandise décrite ci-dessous' }}
        pour le compte de l'exportateur ci-dessous, la marchandise suivante :
    </p>

    <table class="att-parties">
        <tr>
            <td style="padding-right:4px;">
                <table class="att-box">
                    <tr><th>FABRICANT (QUI A CONDITIONNÉ)</th></tr>
                    <tr><td>Société : {{ $txt($fab['societe'] ?? null) }}</td></tr>
                    <tr><td>Adresse : {{ $txt($fab['adresse'] ?? null) }}</td></tr>
                    <tr><td>Agrément EACCE N° : {{ $txt($fab['eacce'] ?? null) }}</td></tr>
                    <tr><td>Qualité : fabricant de conserves alimentaires</td></tr>
                </table>
            </td>
            <td style="padding-left:4px;">
                <table class="att-box">
                    <tr><th>POUR LE COMPTE DE (EXPORTATEUR)</th></tr>
                    <tr><td>Société : {{ $txt($exp['societe'] ?? null) }}</td></tr>
                    <tr><td>Adresse : {{ $txt($exp['adresse'] ?? null) }}</td></tr>
                    <tr><td>Agrément EACCE N° : {{ $txt($exp['eacce'] ?? null) }}</td></tr>
                    <tr><td>Qualité : exportateur</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="att-section">LA MARCHANDISE SUIVANTE :</p>
    <table class="att-goods">
        <thead>
            <tr>
                <th style="width:22%;">Produits</th>
                <th style="width:10%;">Calibre</th>
                <th style="width:12%;">Lot n°</th>
                <th style="width:12%;">Date production</th>
                <th style="width:14%;">Type conditionnement</th>
                <th style="width:8%;">Total EMB</th>
                <th style="width:11%;">Poids net unitaire (kg)</th>
                <th style="width:11%;">Poids net total (kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $i => $ligne)
                <tr class="{{ $i % 2 === 0 ? 'alt' : '' }}">
                    <td>{{ $ligne['produit'] }}</td>
                    <td style="text-align:center;">{{ $ligne['calibre'] }}</td>
                    <td style="text-align:center;">{{ $ligne['lot'] }}</td>
                    <td style="text-align:center;">{{ $ligne['date'] ? \Carbon\Carbon::parse($ligne['date'])->format('d/m/Y') : '' }}</td>
                    <td>{{ $ligne['conditionnement'] }}</td>
                    <td style="text-align:center;">{{ $ligne['emb'] ?: '' }}</td>
                    <td style="text-align:right;">{{ $kg($ligne['poids_unit']) }}</td>
                    <td style="text-align:right;">{{ $kg($ligne['poids_total']) }}</td>
                </tr>
            @endforeach
            @for($i = 0; $i < $pad; $i++)
                <tr class="{{ (count($lignes) + $i) % 2 === 0 ? 'alt' : '' }}">
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
            <tr class="total">
                <td>TOTAL</td>
                <td></td><td></td><td></td><td></td>
                <td style="text-align:center;">{{ ($a['total_emb'] ?? 0) ?: '' }}</td>
                <td></td>
                <td style="text-align:right;">{{ $kg($a['total_poids'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="att-box" style="margin-top:8px;">
        <tr><th colspan="2">CONTENEUR</th></tr>
        <tr>
            <td style="width:62%;">N° conteneur {{ $a['type_conteneur'] ?? '' }} : {{ $txt($a['conteneur'] ?? null) }}</td>
            <td>Plomb N° : {{ $txt($a['plomb'] ?? null) }}</td>
        </tr>
        <tr>
            <td>Total emballage (unités) : {{ ($a['total_emb'] ?? 0) ?: '—' }}</td>
            <td>Poids net total (kg) : {{ $kg($a['total_poids'] ?? null) ?: '—' }}</td>
        </tr>
    </table>

    <div class="att-legal">
        <p>Cette marchandise répond à tout point de vue aux normes en vigueur relatives à l'exportation de ce produit.</p>
        <p>Nous dégageons toute responsabilité concernant un éventuel changement de la nature du produit une fois sortie de notre unité.</p>
        <p>Le conteneur ne contient aucun produit non déclaré ou marchandise prohibée.</p>
        <p>Cette attestation est délivrée à l'intéressé pour servir et valoir ce que de droit.</p>
    </div>

    <table class="att-sign">
        <tr>
            <td style="width:58%; padding-top:8px;">
                <strong>Fait à</strong> {{ $txt($a['lieu'] ?? null) }}
                <strong>le</strong> {{ $day($a['date'] ?? null) }}
            </td>
            <td style="width:42%;">
                <table class="att-stamp">
                    <tr><th>CACHET ET SIGNATURE</th></tr>
                    <tr><td></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="att-foot">
        @if(!empty($pied['forme']) || !empty($pied['capital']))
            {{ $pied['forme'] ?: 'Société' }}@if(!empty($pied['capital'])) au capital de {{ $pied['capital'] }} DH @endif
            · Siège social : {{ $pied['adresse'] ?: '—' }}@if(!empty($pied['ville'])) – {{ $pied['ville'] }}@endif @if(!empty($pied['pays'])) – {{ $pied['pays'] }}@endif
            <br>
        @elseif(!empty($pied['societe']))
            {{ $pied['societe'] }}@if(!empty($pied['adresse'])) · {{ $pied['adresse'] }}@endif
            <br>
        @endif
        IF : {{ $pied['if'] ?: '—' }}
        &nbsp; ICE : {{ $pied['ice'] ?: '—' }}
        &nbsp; RC n° : {{ $pied['rc'] ?: '—' }}
        &nbsp; Patente : {{ $pied['patente'] ?: '—' }}
        <br>
        Tél : {{ $pied['telephone'] ?: '—' }}
        &nbsp; E-mail : {{ $pied['email'] ?: '—' }}
    </div>
</div>
