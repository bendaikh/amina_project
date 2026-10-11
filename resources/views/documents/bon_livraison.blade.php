<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de livraison {{ $bl->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; margin: 16px; }
        .inv { width: 100%; border-collapse: collapse; }
        .inv th, .inv td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
        .inv-brand { font-size: 15px; font-weight: 700; color: #1a3d2b; margin: 0; }
        .inv-contact { font-size: 8px; color: #333; line-height: 1.45; }
        .inv-title { font-size: 16px; font-weight: 700; text-align: right; text-transform: uppercase; margin: 0; }
        .inv-title-en { font-size: 9px; text-align: right; color: #555; margin: 2px 0 0; text-transform: uppercase; letter-spacing: .04em; }
        .inv-head th { background: #2d5a42; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 8px; letter-spacing: .03em; }
        .inv-label { color: #555; font-size: 8px; text-transform: uppercase; }
        .inv-val { font-weight: 700; font-size: 10px; }
        .inv-party { font-size: 9px; line-height: 1.45; }
        .lines th { font-size: 7px; text-transform: uppercase; }
        .lines td { font-size: 8px; }
        .num { text-align: right; white-space: nowrap; }
        .totals td { font-weight: 700; background: #f3f4f6; }
        .note { margin-top: 8px; font-size: 9px; }
    </style>
</head>
<body>
@php
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $fmtDt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y H:i') : '—';
    $n = fn ($v) => $v === null || $v === '' ? '—' : number_format((float) $v, 3, ',', ' ');
    $statuts = ['brouillon' => 'Brouillon', 'valide' => 'Validé', 'annule' => 'Annulé'];
    $client = $bl->client;
    $adresse = trim(implode(', ', array_filter([
        $client?->adresse,
        $client?->ville,
        $client?->pays,
    ])));
@endphp

<table class="inv" style="margin-bottom:0;border-bottom:none;">
    <tr>
        <td style="width:58%;border-bottom:none;">
            <div class="inv-brand">{{ $company['nom'] ?? '—' }}</div>
            <div class="inv-contact">
                {{ $company['adresse'] ?? '' }}
                @if(!empty($company['telephone']))
                    <br>Tél. : {{ $company['telephone'] }}
                @endif
                @if(!empty($company['email']))
                    <br>E-mail : {{ $company['email'] }}
                @endif
                @if(!empty($company['ice']))
                    <br>ICE : {{ $company['ice'] }}
                @endif
            </div>
        </td>
        <td style="width:42%;border-bottom:none;vertical-align:middle;">
            <div class="inv-title">Bon de livraison</div>
            <div class="inv-title-en">Delivery note</div>
        </td>
    </tr>
</table>

<table class="inv" style="margin-top:0;">
    <thead>
        <tr class="inv-head">
            <th style="width:50%;">Émetteur</th>
            <th style="width:50%;">Client</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="inv-party">
                <strong>{{ $company['nom'] ?? '—' }}</strong><br>
                {{ $company['adresse'] ?? '—' }}
            </td>
            <td class="inv-party">
                <strong>{{ $client?->nomination ?: $client?->nom ?: '—' }}</strong><br>
                {{ $adresse ?: '—' }}
                @if($client?->telephone)
                    <br>Tél. : {{ $client->telephone }}
                @endif
                @if($client?->email)
                    <br>E-mail : {{ $client->email }}
                @endif
            </td>
        </tr>
    </tbody>
</table>

<table class="inv" style="margin-top:0;border-top:none;">
    <tr>
        <td style="width:20%;">
            <div class="inv-label">N° bon de livraison</div>
            <div class="inv-val">{{ $bl->numero }}</div>
        </td>
        <td style="width:20%;">
            <div class="inv-label">Date de création</div>
            <div class="inv-val">{{ $fmtDate($bl->date_creation) }}</div>
        </td>
        <td style="width:20%;">
            <div class="inv-label">Réf. client</div>
            <div class="inv-val">{{ $bl->reference_client ?: '—' }}</div>
        </td>
        <td style="width:20%;">
            <div class="inv-label">Date / heure livraison</div>
            <div class="inv-val">{{ $fmtDt($bl->date_heure_livraison) }}</div>
        </td>
        <td style="width:20%;">
            <div class="inv-label">Statut</div>
            <div class="inv-val">{{ $statuts[$bl->statut] ?? $bl->statut }}</div>
        </td>
    </tr>
    @if($bl->commande?->numero)
        <tr>
            <td colspan="5">
                <div class="inv-label">N° commande</div>
                <div class="inv-val">{{ $bl->commande->numero }}</div>
            </td>
        </tr>
    @endif
</table>

<table class="inv lines" style="margin-top:10px;">
    <thead>
        <tr class="inv-head">
            <th>Réf. article</th>
            <th>Article / Désignation</th>
            <th>Calibre</th>
            <th>Emballage</th>
            <th>N° de lot</th>
            <th class="num">Total colis</th>
            <th class="num">Poids net ég. unit.</th>
            <th class="num">Total poids net</th>
            <th class="num">Total poids total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($bl->lignes as $ligne)
            <tr>
                <td>{{ $ligne->ref_article ?: '—' }}</td>
                <td>{{ $ligne->designation ?: '—' }}</td>
                <td>{{ $ligne->calibre ?: '—' }}</td>
                <td>{{ $ligne->emballage ?: '—' }}</td>
                <td>{{ $ligne->numero_lot ?: '—' }}</td>
                <td class="num">{{ $n($ligne->total_colis) }}</td>
                <td class="num">{{ $n($ligne->poids_net_eg_unitaire) }}</td>
                <td class="num">{{ $n($ligne->total_poids_net) }}</td>
                <td class="num">{{ $n($ligne->total_poids_total) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">Aucune ligne</td>
            </tr>
        @endforelse
        <tr class="totals">
            <td colspan="5">Totaux</td>
            <td class="num">{{ $n($bl->total_colis) }}</td>
            <td></td>
            <td class="num">{{ $n($bl->total_poids_net) }}</td>
            <td class="num">{{ $n($bl->total_poids_total) }}</td>
        </tr>
    </tbody>
</table>

@if($bl->informations_additionnelles)
    <p class="note"><strong>Informations additionnelles :</strong> {{ $bl->informations_additionnelles }}</p>
@endif
</body>
</html>
