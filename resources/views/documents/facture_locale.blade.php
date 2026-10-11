<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; margin: 16px; }
        .inv { width: 100%; border-collapse: collapse; }
        .inv th, .inv td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
        .inv-brand { font-size: 15px; font-weight: 700; color: #1a3d2b; margin: 0; }
        .inv-tagline { font-size: 8px; color: #555; margin: 2px 0 6px; }
        .inv-contact { font-size: 8px; color: #333; line-height: 1.45; }
        .inv-title { font-size: 18px; font-weight: 700; text-align: right; text-transform: uppercase; margin: 0; }
        .inv-title-en { font-size: 11px; text-align: right; color: #555; margin: 2px 0 0; }
        .inv-head th { background: #2d5a42; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 8px; letter-spacing: .03em; }
        .inv-label { color: #555; font-size: 8px; text-transform: uppercase; }
        .inv-val { font-weight: 700; font-size: 10px; }
        .inv-party { font-size: 9px; line-height: 1.45; }
        .lines th { font-size: 7px; text-transform: uppercase; }
        .lines td { font-size: 8px; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .inv-total-row td { font-weight: 700; background: #f3f4f6; }
        .inv-grand td { font-weight: 700; background: #2d5a42; color: #fff; font-size: 11px; }
        .inv-terms-label { width: 22%; font-weight: 700; background: #f0f4f1; text-transform: uppercase; font-size: 8px; }
        .letters { margin-top: 8px; font-size: 9px; }
        .sign td { height: 70px; vertical-align: top; font-size: 9px; font-weight: 700; }
    </style>
</head>
<body>
@php
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $n = fn ($v) => number_format((float) $v, 3, ',', ' ');
    $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $client = $facture->client;
    $adresse = trim(implode(', ', array_filter([
        $client?->adresse_livraison ?: $client?->adresse,
        $client?->ville,
        $client?->pays,
    ])));
    $devise = $facture->devise ?: 'MAD';
    $legal = array_filter([
        !empty($company['ice']) ? 'ICE : ' . $company['ice'] : null,
        !empty($company['if']) ? 'IF : ' . $company['if'] : null,
        !empty($company['rc']) ? 'RC : ' . $company['rc'] : null,
        !empty($company['patente']) ? 'Patente : ' . $company['patente'] : null,
    ]);
@endphp

<table class="inv" style="margin-bottom:0;border-bottom:none;">
    <tr>
        <td style="width:58%;border-bottom:none;">
            <div class="inv-brand">{{ $company['nom'] ?? '—' }}</div>
            <div class="inv-tagline">Facture de vente locale</div>
            <div class="inv-contact">
                {{ $company['adresse'] ?? '' }}
                @if(!empty($company['telephone']))
                    <br>Tél. : {{ $company['telephone'] }}
                @endif
                @if(!empty($company['email']))
                    <br>E-mail : {{ $company['email'] }}
                @endif
                @if(!empty($company['web']))
                    <br>Web : {{ $company['web'] }}
                @endif
                @if($legal)
                    <br>{{ implode(' · ', $legal) }}
                @endif
            </div>
        </td>
        <td style="width:42%;border-bottom:none;vertical-align:middle;">
            <div class="inv-title">Facture</div>
            <div class="inv-title-en">{{ $facture->numero }}</div>
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
                @if(!empty($company['telephone']))
                    <br>Tél. : {{ $company['telephone'] }}
                @endif
                @if(!empty($company['email']))
                    <br>E-mail : {{ $company['email'] }}
                @endif
                @if(!empty($company['ice']))
                    <br>ICE : {{ $company['ice'] }}
                @endif
                @if(!empty($company['if']))
                    <br>IF : {{ $company['if'] }}
                @endif
                @if(!empty($company['rc']))
                    <br>RC : {{ $company['rc'] }}
                @endif
            </td>
            <td class="inv-party">
                <strong>{{ $client?->nomination ?: $client?->nom ?: '—' }}</strong><br>
                {{ $adresse ?: '—' }}
                @if($client?->ice_cin)
                    <br>ICE : {{ $client->ice_cin }}
                @endif
                @if($client?->numero_tva)
                    <br>N° TVA : {{ $client->numero_tva }}
                @endif
                @if($client?->numero_rc)
                    <br>RC : {{ $client->numero_rc }}
                @endif
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
        <td style="width:25%;">
            <div class="inv-label">N° facture</div>
            <div class="inv-val">{{ $facture->numero }}</div>
        </td>
        <td style="width:25%;">
            <div class="inv-label">Date</div>
            <div class="inv-val">{{ $fmtDate($facture->date_facture) }}</div>
        </td>
        <td style="width:25%;">
            <div class="inv-label">Échéance</div>
            <div class="inv-val">{{ $fmtDate($facture->echeance) }}</div>
        </td>
        <td style="width:25%;">
            <div class="inv-label">Devise</div>
            <div class="inv-val">{{ $devise }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="inv-label">Réf. client</div>
            <div class="inv-val">{{ $facture->reference_client ?: '—' }}</div>
        </td>
        <td>
            <div class="inv-label">N° commande</div>
            <div class="inv-val">{{ $facture->numero_commande ?: $facture->commande?->numero ?: '—' }}</div>
        </td>
        <td>
            <div class="inv-label">N° BL</div>
            <div class="inv-val">{{ $facture->numero_bl ?: $facture->bonLivraison?->numero ?: '—' }}</div>
        </td>
        <td>
            <div class="inv-label">Paiement</div>
            <div class="inv-val">{{ $facture->mode_paiement ?: $facture->conditions_paiement ?: '—' }}</div>
        </td>
    </tr>
</table>

<table class="inv lines" style="margin-top:10px;">
    <thead>
        <tr class="inv-head">
            <th>Réf.</th>
            <th>Désignation</th>
            <th>Calibre</th>
            <th>Emballage</th>
            <th>Lot</th>
            <th class="num">Qté</th>
            <th class="num">PU HT</th>
            <th class="center">TVA</th>
            <th class="num">Montant HT</th>
            <th class="num">Montant TTC</th>
        </tr>
    </thead>
    <tbody>
        @forelse($facture->lignes as $ligne)
            <tr>
                <td>{{ $ligne->ref_article ?: '—' }}</td>
                <td>{{ $ligne->designation ?: '—' }}</td>
                <td>{{ $ligne->calibre ?: '—' }}</td>
                <td>{{ $ligne->emballage ?: '—' }}</td>
                <td>{{ $ligne->numero_lot ?: '—' }}</td>
                <td class="num">{{ $n($ligne->quantite) }} {{ $ligne->unite }}</td>
                <td class="num">{{ $money($ligne->prix_unitaire) }}</td>
                <td class="center">{{ rtrim(rtrim(number_format((float) $ligne->tva_taux, 2, ',', ' '), '0'), ',') }} %</td>
                <td class="num">{{ $money($ligne->montant_ht) }}</td>
                <td class="num">{{ $money($ligne->montant_ttc) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10">Aucune ligne</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="inv" style="margin-top:0;border-top:none;width:48%;margin-left:auto;">
    <tr class="inv-total-row">
        <td>Total HT</td>
        <td class="num" style="width:40%;">{{ $money($facture->total_ht) }} {{ $devise }}</td>
    </tr>
    <tr>
        <td>Total TVA</td>
        <td class="num">{{ $money($facture->total_tva) }} {{ $devise }}</td>
    </tr>
    <tr class="inv-grand">
        <td>Total TTC</td>
        <td class="num">{{ $money($facture->total_ttc) }} {{ $devise }}</td>
    </tr>
</table>

<p class="letters"><strong>Arrêtée la présente facture à la somme de :</strong> {{ $montantLettres }}.</p>

<table class="inv" style="margin-top:8px;">
    <thead>
        <tr class="inv-head">
            <th colspan="2">Conditions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="inv-terms-label">Paiement</td>
            <td>{{ $facture->conditions_paiement ?: '—' }}</td>
        </tr>
        <tr>
            <td class="inv-terms-label">Mode</td>
            <td>{{ $facture->mode_paiement ?: '—' }}</td>
        </tr>
        @if($facture->observations)
            <tr>
                <td class="inv-terms-label">Observations</td>
                <td>{{ $facture->observations }}</td>
            </tr>
        @endif
    </tbody>
</table>

@if(!empty($company['banque']) || !empty($company['iban']) || !empty($company['rib']))
    <table class="inv" style="margin-top:10px;">
        <thead>
            <tr class="inv-head">
                <th colspan="4" style="text-align:left;">Coordonnées bancaires</th>
            </tr>
            <tr class="inv-head">
                <th style="width:28%;">Banque</th>
                <th style="width:22%;">Agence</th>
                <th style="width:32%;">N° de compte</th>
                <th style="width:18%;">SWIFT / BIC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $company['banque'] ?? '—' }}</td>
                <td>{{ $company['agence'] ?? '—' }}</td>
                <td>{{ $company['iban'] ?? $company['rib'] ?? '—' }}</td>
                <td>{{ $company['swift'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
@endif

<table class="inv sign" style="margin-top:12px;">
    <tr>
        <td style="width:50%;">Cachet et signature de l'émetteur</td>
        <td style="width:50%;">Cachet et signature du client</td>
    </tr>
</table>
</body>
</html>
