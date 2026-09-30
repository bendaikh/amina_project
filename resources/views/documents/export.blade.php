<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $meta['titre'] }} — {{ $export->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; margin: 18px; }
        h1 { font-size: 16px; margin: 0; text-align: center; text-transform: uppercase; letter-spacing: .02em; }
        .subtitle { text-align: center; color: #555; margin: 4px 0 12px; font-size: 11px; }
        .meta { color: #555; margin-bottom: 12px; font-size: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; margin-bottom: 16px; }
        .label { color: #666; font-size: 10px; }
        .value { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #e5e7eb; font-size: 9px; text-transform: uppercase; }
        .totals { margin-top: 12px; text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #e5e7eb; }
        .box { border: 1px solid #333; margin-bottom: 0; }
        .box-head { background: #d1d5db; text-align: center; font-weight: 700; text-transform: uppercase; padding: 6px; border-bottom: 1px solid #333; }
        .box-body { padding: 8px 10px; }
        .box-row { margin: 2px 0; }
        .parties { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #333; }
        .parties > div { border-right: 1px solid #333; }
        .parties > div:last-child { border-right: none; }
        .ship-grid { display: grid; grid-template-columns: repeat(5, 1fr); border: 1px solid #333; border-top: none; }
        .ship-cell { border-right: 1px solid #ccc; padding: 6px 8px; }
        .ship-cell:last-child { border-right: none; }
        .weight-bar { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #333; border-top: none; background: #f9fafb; }
        .weight-cell { padding: 8px; border-right: 1px solid #ccc; }
        .weight-cell:last-child { border-right: none; }
        .weight-cell strong { display: block; font-size: 13px; margin-top: 2px; }
        .section-title { background: #e5e7eb; border: 1px solid #333; border-bottom: none; padding: 6px 8px; font-weight: 700; text-transform: uppercase; margin-top: 12px; }
        .detail-table { border: 1px solid #333; }
        .detail-table th, .detail-table td { font-size: 9px; }
        .tfoot td { font-weight: 700; background: #f3f4f6; }
        /* Facture commerciale — maquette */
        .inv { width: 100%; border-collapse: collapse; }
        .inv th, .inv td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; font-size: 9px; }
        .inv-head th { background: #2d5a42; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 9px; letter-spacing: .03em; }
        .inv-label { color: #555; font-size: 8px; text-transform: uppercase; }
        .inv-val { font-weight: 600; font-size: 10px; }
        .inv-brand { font-size: 16px; font-weight: 700; color: #1a3d2b; letter-spacing: .02em; margin: 0; }
        .inv-tagline { font-size: 8px; color: #555; margin: 2px 0 6px; }
        .inv-contact { font-size: 8px; color: #333; line-height: 1.45; }
        .inv-title { font-size: 18px; font-weight: 700; text-align: right; text-transform: uppercase; margin: 0; letter-spacing: .04em; color: #111; }
        .inv-title-en { font-size: 10px; text-align: right; color: #555; margin: 2px 0 0; letter-spacing: .06em; text-transform: uppercase; }
        .inv-party { font-size: 9px; line-height: 1.5; }
        .inv-party strong { font-size: 11px; }
        .inv-money { text-align: right; white-space: nowrap; }
        .inv-center { text-align: center; }
        .inv-right { text-align: right; }
        .inv-total-row td { font-weight: 700; background: #f3f4f6; }
        .inv-grand td { font-weight: 700; background: #2d5a42; color: #fff; font-size: 11px; }
        .inv-terms-label { width: 22%; font-weight: 700; background: #f0f4f1; text-transform: uppercase; font-size: 8px; }
        .inv-bank th { background: #2d5a42; color: #fff; font-size: 8px; text-transform: uppercase; }
        .inv-bank td { font-size: 9px; font-weight: 600; }
        .inv-wrap { margin: 0; }
        @@media print { body { margin: 10px; } }
    </style>
</head>
<body>
@if($type === 'packing_list')
    @php
        $expNom = $export->exp_nom_societe ?: $export->exp_nom ?: $export->exportateur?->nom_societe ?: $export->exportateur?->nom;
        $fmt = fn ($d) => $d ? (\Carbon\Carbon::parse($d)->format('d/m/Y')) : '—';
        $n = fn ($v) => number_format((float) $v, 3, ',', ' ');
    @endphp

    <h1>Liste de colisage / Packing list</h1>
    <div class="subtitle">
        {{ $expNom ?: '—' }}
        @if($expNom)
            — Producteur &amp; Exportateur
        @endif
        · {{ $export->numero }}
    </div>

    <div class="parties">
        <div>
            <div class="box-head">Exportateur</div>
            <div class="box-body">
                <div class="box-row"><span class="label">Nom :</span> {{ $export->exp_nom ?: $export->exportateur?->nom ?: '—' }}</div>
                <div class="box-row"><span class="label">Société :</span> {{ $export->exp_nom_societe ?: $export->exportateur?->nom_societe ?: $export->exp_nom ?: '—' }}</div>
                <div class="box-row"><span class="label">Réf. Foodex :</span> {{ $export->exp_ref_foodex ?: $export->exportateur?->ref_foodex ?: '—' }}</div>
                <div class="box-row"><span class="label">Adresse :</span> {{ $export->exp_adresse ?: $export->exportateur?->adresse ?: '—' }}</div>
                <div class="box-row"><span class="label">Tél. :</span> {{ $export->exp_telephone ?: $export->exportateur?->telephone ?: '—' }}</div>
                <div class="box-row"><span class="label">E-mail :</span> {{ $export->exp_email ?: $export->exportateur?->email ?: '—' }}</div>
                <div class="box-row"><span class="label">Web :</span> {{ $export->exp_web ?: $export->exportateur?->web ?: '—' }}</div>
            </div>
        </div>
        <div>
            <div class="box-head">Destinataire</div>
            <div class="box-body">
                <div class="box-row"><span class="label">Nom :</span> {{ $export->dest_nom ?: $export->client?->nom ?: '—' }}</div>
                <div class="box-row"><span class="label">Société :</span> {{ $export->dest_societe ?: $export->client?->nomination ?: $export->client?->nom ?: '—' }}</div>
                <div class="box-row"><span class="label">Adresse :</span> {{ $export->dest_adresse ?: ($export->client?->adresse_livraison ?: $export->client?->adresse) ?: '—' }}</div>
                <div class="box-row"><span class="label">N° TVA :</span> {{ $export->dest_tva ?: $export->client?->numero_tva ?: '—' }}</div>
                <div class="box-row"><span class="label">EORI :</span> {{ $export->dest_eori ?: $export->client?->eori ?: '—' }}</div>
                <div class="box-row"><span class="label">Contact :</span> {{ $export->dest_contact ?: $export->client?->contact_nom ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="ship-grid">
        <div class="ship-cell"><div class="label">Réf. client</div><div class="value">{{ $export->reference_commande_client ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">N° Booking</div><div class="value">{{ $export->booking ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">N° SWB / BL</div><div class="value">{{ $export->numero_swb_bl ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">N° conteneur</div><div class="value">{{ $export->conteneur ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">N° plomb</div><div class="value">{{ $export->plomb_scelle ?: '—' }}</div></div>
    </div>
    <div class="ship-grid">
        <div class="ship-cell"><div class="label">Navire</div><div class="value">{{ $export->navire ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">ETD</div><div class="value">{{ $fmt($export->etd) }}</div></div>
        <div class="ship-cell"><div class="label">ETA</div><div class="value">{{ $fmt($export->eta) }}</div></div>
        <div class="ship-cell"><div class="label">Port départ</div><div class="value">{{ $export->port_chargement ?: '—' }}</div></div>
        <div class="ship-cell"><div class="label">Port arrivée</div><div class="value">{{ $export->port_destination ?: '—' }}</div></div>
    </div>

    <div class="weight-bar">
        <div class="weight-cell"><div class="label">Poids brut</div><strong>{{ $n($export->total_poids_brut) }} kg</strong></div>
        <div class="weight-cell"><div class="label">Poids net</div><strong>{{ $n($export->total_poids_net) }} kg</strong></div>
        <div class="weight-cell"><div class="label">Poids net ég.</div><strong>{{ $n($export->total_poids_egoutte) }} kg</strong></div>
        <div class="weight-cell"><div class="label">Total packages</div><strong>{{ $export->total_packages }}</strong></div>
    </div>

    <div class="section-title">Détail de la liste de colisage</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th>N° article</th>
                <th>HS Code</th>
                <th>Désignation</th>
                <th>Calibre</th>
                <th>N° de lot</th>
                <th>Date prod.</th>
                <th>Nb colis</th>
                <th>Poids brut un.</th>
                <th>Total brut</th>
                <th>Poids net un.</th>
                <th>Total net</th>
                <th>Net ég. un.</th>
                <th>Total net ég.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($export->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->code_article }}</td>
                    <td>{{ $ligne->hs_code }}</td>
                    <td>{{ $ligne->designation }}</td>
                    <td>{{ $ligne->calibre }}</td>
                    <td>{{ $ligne->numero_lot }}</td>
                    <td>{{ $fmt($ligne->date_production) }}</td>
                    <td>{{ $ligne->nb_colis ?: $ligne->cartons }}</td>
                    <td>{{ $n($ligne->poids_brut_unitaire) }}</td>
                    <td>{{ $n($ligne->poids_brut) }}</td>
                    <td>{{ $n($ligne->poids_net_unitaire) }}</td>
                    <td>{{ $n($ligne->poids_net) }}</td>
                    <td>{{ $n($ligne->poids_net_egoutte_unitaire) }}</td>
                    <td>{{ $n($ligne->poids_egoutte) }}</td>
                </tr>
            @empty
                <tr><td colspan="13" style="text-align:center;color:#888;">Aucune ligne</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tfoot">
                <td colspan="6" style="text-align:right;">TOTAL</td>
                <td>{{ $export->total_packages }}</td>
                <td></td>
                <td>{{ $n($export->total_poids_brut) }}</td>
                <td></td>
                <td>{{ $n($export->total_poids_net) }}</td>
                <td></td>
                <td>{{ $n($export->total_poids_egoutte) }}</td>
            </tr>
        </tfoot>
    </table>

@elseif($type === 'facture_commerciale')
    @php
        $expNom = $export->exp_nom_societe ?: $export->exp_nom ?: $export->exportateur?->nom_societe ?: $export->exportateur?->nom;
        $fmt = fn ($d) => $d ? (\Carbon\Carbon::parse($d)->format('d/m/Y')) : '—';
        $n = fn ($v, $dec = 3) => number_format((float) $v, $dec, ',', ' ');
        $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
        $embLabel = \App\Models\Exportation::TYPES_EMBALLAGE_DOC[$export->type_emballage_doc] ?? ($export->type_emballage_doc ?: '—');
        if ($export->type_emballage_doc === 'les_deux') {
            $embLabel = 'Seaux perdus ; fûts sous réserve de retour';
        } elseif ($export->type_emballage_doc === 'sous_reserve') {
            $embLabel = 'Sous réserve de retour';
        } elseif ($export->type_emballage_doc === 'perdu') {
            $embLabel = 'Emballages perdus';
        }
        $origine = $export->origine_marchandise ?: 'Maroc';
        $destLabel = $export->destination
            ?: trim(($export->port_destination ?: '') . ($export->pays ? ', ' . $export->pays : ''))
            ?: '—';
        $incotermFull = trim(($export->incoterm ?: '') . ' ' . ($export->port_chargement ?: ''));
        $prixLabel = trim(($export->devise ?: '') . ($incotermFull ? ' — ' . $incotermFull : ''));
        $assuranceLabel = match ($export->assurance) {
            'Client' => 'À la charge du client',
            'Exportateur' => 'À la charge de l\'exportateur',
            'CIF' => 'CIF',
            'Aucune', null, '' => 'Aucune',
            default => $export->assurance,
        };
        $embGroups = [];
        $embRefs = [];
        foreach ($export->lignes as $l) {
            $ref = trim((string) ($l->reference_emballage ?: $l->conditionnement ?: ''));
            if ($ref === '') {
                $ref = 'emballage';
            }
            $qty = (int) ($l->total_emballage ?: $l->nb_colis ?: $l->cartons ?: 0);
            $embGroups[$ref] = ($embGroups[$ref] ?? 0) + $qty;
            $embRefs[$ref] = true;
        }
        $embDetail = collect($embGroups)->map(fn ($q, $r) => $q . ' ' . $r)->implode(' + ') ?: '—';
        $embRefsList = implode(', ', array_keys($embRefs)) ?: '—';
        $compte = $export->iban ?: $export->rib ?: $export->exportateur?->iban ?: $export->exportateur?->rib ?: '—';
        $totalLabel = $export->transport_applique ? 'TOTAL FACTURE (CFR)' : 'TOTAL FACTURE (FOB)';
        $totalMontant = $export->transport_applique ? $export->total_cfr : $export->total_fob;
    @endphp

    <div class="inv-wrap">
        {{-- En-tête marque + titre --}}
        <table class="inv" style="margin-bottom:0;border-bottom:none;">
            <tr>
                <td style="width:58%;border-bottom:none;">
                    <div class="inv-brand">{{ $expNom ?: '—' }}</div>
                    <div class="inv-tagline">Producteur &amp; Exportateur d'olives de table et huile d'olive</div>
                    <div class="inv-contact">
                        {{ $export->exp_adresse ?: $export->exportateur?->adresse ?: '' }}
                        @if($export->exp_telephone ?: $export->exportateur?->telephone)
                            <br>Tél. : {{ $export->exp_telephone ?: $export->exportateur?->telephone }}
                        @endif
                        @if($export->exp_email ?: $export->exportateur?->email)
                            <br>E-mail : {{ $export->exp_email ?: $export->exportateur?->email }}
                        @endif
                        @if($export->exp_web ?: $export->exportateur?->web)
                            <br>Web : {{ $export->exp_web ?: $export->exportateur?->web }}
                        @endif
                    </div>
                </td>
                <td style="width:42%;border-bottom:none;vertical-align:middle;">
                    <div class="inv-title">Facture commerciale</div>
                    <div class="inv-title-en">Commercial invoice</div>
                </td>
            </tr>
        </table>

        {{-- Exportateur / Client --}}
        <table class="inv" style="margin-top:0;">
            <thead>
                <tr class="inv-head">
                    <th style="width:50%;">Exportateur</th>
                    <th style="width:50%;">Client / Destinataire</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="inv-party">
                        <strong>{{ $expNom ?: '—' }}</strong><br>
                        {{ $export->exp_adresse ?: $export->exportateur?->adresse ?: '—' }}<br>
                        @if($export->exp_telephone ?: $export->exportateur?->telephone)
                            Tél. : {{ $export->exp_telephone ?: $export->exportateur?->telephone }}<br>
                        @endif
                        @if($export->exp_email ?: $export->exportateur?->email)
                            E-mail : {{ $export->exp_email ?: $export->exportateur?->email }}<br>
                        @endif
                        @if($export->exp_web ?: $export->exportateur?->web)
                            Web : {{ $export->exp_web ?: $export->exportateur?->web }}
                        @endif
                        @if($export->exp_ref_foodex ?: $export->exportateur?->ref_foodex)
                            <br>Réf. Foodex : {{ $export->exp_ref_foodex ?: $export->exportateur?->ref_foodex }}
                        @endif
                    </td>
                    <td class="inv-party">
                        <strong>{{ $export->dest_societe ?: $export->dest_nom ?: $export->client?->nomination ?: $export->client?->nom ?: '—' }}</strong><br>
                        {{ $export->dest_adresse ?: ($export->client?->adresse_livraison ?: $export->client?->adresse) ?: '—' }}<br>
                        N° TVA : {{ $export->dest_tva ?: $export->client?->numero_tva ?: '—' }}<br>
                        EORI : {{ $export->dest_eori ?: $export->client?->eori ?: '—' }}<br>
                        Contact : {{ $export->dest_contact ?: $export->client?->contact_nom ?: '—' }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Métadonnées 4 colonnes × 3 lignes --}}
        <table class="inv" style="margin-top:0;border-top:none;">
            <tr>
                <td style="width:25%;">
                    <div class="inv-label">N° facture</div>
                    <div class="inv-val">{{ $export->numero_facture ?: $export->numero }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">Date</div>
                    <div class="inv-val">{{ $fmt($export->date_creation) }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">Réf. client</div>
                    <div class="inv-val">{{ $export->reference_commande_client ?: '—' }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">N° commande</div>
                    <div class="inv-val">{{ $export->numero_commande ?: $export->commande?->numero ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="inv-label">N° de la liste de colisage</div>
                    <div class="inv-val">{{ $export->numero_liste_colisage ?: $export->numero }}</div>
                </td>
                <td>
                    <div class="inv-label">Booking</div>
                    <div class="inv-val">{{ $export->booking ?: '—' }}</div>
                </td>
                <td>
                    <div class="inv-label">SWB / BL</div>
                    <div class="inv-val">{{ $export->numero_swb_bl ?: '—' }}</div>
                </td>
                <td>
                    <div class="inv-label">Devise</div>
                    <div class="inv-val">{{ $export->devise ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="inv-label">Incoterm</div>
                    <div class="inv-val">{{ $incotermFull ?: '—' }}</div>
                </td>
                <td>
                    <div class="inv-label">Paiement</div>
                    <div class="inv-val">{{ $export->conditions_paiement ?: '—' }}</div>
                </td>
                <td>
                    <div class="inv-label">Origine</div>
                    <div class="inv-val">{{ $origine }}</div>
                </td>
                <td>
                    <div class="inv-label">Destination</div>
                    <div class="inv-val">{{ $destLabel }}</div>
                </td>
            </tr>
        </table>

        {{-- Articles --}}
        <table class="inv" style="margin-top:8px;">
            <thead>
                <tr class="inv-head">
                    <th>Article</th>
                    <th>HS</th>
                    <th>Désignation</th>
                    <th>Calibre</th>
                    <th>Lot</th>
                    <th>Réf. emballage</th>
                    <th class="inv-center">Colis</th>
                    <th class="inv-center">Nombre/colis</th>
                    <th class="inv-center">Total emb.</th>
                    <th class="inv-right">Poids net</th>
                    <th class="inv-right">PU</th>
                    <th class="inv-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($export->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->code_article }}</td>
                        <td>{{ $ligne->hs_code }}</td>
                        <td>{{ $ligne->designation }}</td>
                        <td>{{ $ligne->calibre }}</td>
                        <td>{{ $ligne->numero_lot }}</td>
                        <td>{{ $ligne->reference_emballage ?: $ligne->conditionnement }}</td>
                        <td class="inv-center">{{ $ligne->nb_colis ?: $ligne->cartons }}</td>
                        <td class="inv-center">{{ $ligne->nombre_par_colis ?: '—' }}</td>
                        <td class="inv-center">{{ $ligne->total_emballage ?: ($ligne->nb_colis ?: $ligne->cartons) }}</td>
                        <td class="inv-right">{{ $n($ligne->poids_net ?: $ligne->poids_egoutte, 0) }}</td>
                        <td class="inv-money">{{ $money($ligne->prix_unitaire) }}</td>
                        <td class="inv-money">{{ $money($ligne->montant) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="inv-center" style="color:#888;">Aucune ligne</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totaux colis / emballage / poids --}}
        <table class="inv" style="margin-top:0;border-top:none;">
            <tr>
                <td style="width:25%;">
                    <div class="inv-label">Total colis</div>
                    <div class="inv-val">{{ $export->total_packages }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">Total emballage</div>
                    <div class="inv-val">{{ $embDetail }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">Références emballage</div>
                    <div class="inv-val">{{ $embRefsList }}</div>
                </td>
                <td style="width:25%;">
                    <div class="inv-label">Poids net total</div>
                    <div class="inv-val">{{ $n($export->total_poids_net, 0) }} kg</div>
                </td>
            </tr>
        </table>

        {{-- Totaux monétaires --}}
        <table class="inv" style="margin-top:0;border-top:none;width:55%;margin-left:auto;">
            <tr class="inv-total-row">
                <td>Sous-total marchandises / Total facture</td>
                <td class="inv-money" style="width:35%;">{{ $money($export->total_fob) }} {{ $export->devise }}</td>
            </tr>
            @if($export->transport_applique)
                <tr>
                    <td>Transport</td>
                    <td class="inv-money">{{ $money($export->montant_transport) }} {{ $export->devise }}</td>
                </tr>
            @endif
            <tr class="inv-grand">
                <td>{{ $totalLabel }}</td>
                <td class="inv-money">{{ $money($totalMontant) }} {{ $export->devise }}</td>
            </tr>
        </table>

        {{-- Conditions --}}
        <table class="inv" style="margin-top:10px;">
            <thead>
                <tr class="inv-head">
                    <th colspan="2">Origine de la marchandise : {{ $origine }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="inv-terms-label">Paiement</td>
                    <td>{{ $export->conditions_paiement ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Prix</td>
                    <td>{{ $prixLabel ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Assurance</td>
                    <td>{{ $assuranceLabel }}</td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Emballage</td>
                    <td><strong>{{ $embLabel }}</strong></td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Transitaire destinataire</td>
                    <td>{{ $export->transitaire ?: $export->client?->transitaire ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Compagnie de navigation</td>
                    <td>{{ $export->compagnie_maritime ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="inv-terms-label">Documents</td>
                    <td>{{ $export->envoi_documents ?: '—' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Banque (fiche exportateur) --}}
        <table class="inv inv-bank" style="margin-top:10px;">
            <thead>
                <tr>
                    <th colspan="4" style="text-align:left;background:#2d5a42;color:#fff;font-size:9px;text-transform:uppercase;">
                        Fiche exportateur — Coordonnées bancaires
                    </th>
                </tr>
                <tr>
                    <th style="width:28%;">Banque</th>
                    <th style="width:24%;">Agence</th>
                    <th style="width:30%;">N° de compte</th>
                    <th style="width:18%;">SWIFT / BIC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $export->banque ?: $export->exportateur?->banque ?: '—' }}</td>
                    <td>{{ $export->agence ?: $export->exportateur?->agence ?: '—' }}</td>
                    <td>{{ $compte }}</td>
                    <td>{{ $export->swift ?: $export->exportateur?->swift ?: '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

@else
    <h1>{{ $meta['titre'] }}</h1>
    <div class="meta">
        Export {{ $export->numero }}
        · Généré le {{ now()->format('d/m/Y H:i') }}
        · Catégorie {{ $meta['categorie'] }}
    </div>

    <div class="grid">
        <div>
            <div class="label">Client</div>
            <div class="value">{{ $export->dest_nom ?: $export->client?->nom ?? '—' }}</div>
        </div>
        <div>
            <div class="label">Destination / Pays</div>
            <div class="value">{{ $export->destination ?? '—' }} / {{ $export->pays ?? '—' }}</div>
        </div>
        <div>
            <div class="label">Incoterm / Devise</div>
            <div class="value">{{ $export->incoterm ?? '—' }} / {{ $export->devise }}</div>
        </div>
        <div>
            <div class="label">Réf. commande client</div>
            <div class="value">{{ $export->reference_commande_client ?? '—' }}</div>
        </div>
        <div>
            <div class="label">Conteneur / Booking</div>
            <div class="value">{{ $export->conteneur ?? '—' }} / {{ $export->booking ?? '—' }}</div>
        </div>
        <div>
            <div class="label">Plomb / VGM</div>
            <div class="value">{{ $export->plomb_scelle ?? '—' }} / {{ $export->vgm_poids ?? '—' }}{{ $export->vgm_valide ? ' (validé)' : '' }}</div>
        </div>
        <div>
            <div class="label">Transport</div>
            <div class="value">{{ strtoupper($export->mode_transport ?? '—') }} · {{ $export->compagnie_maritime ?? $export->transporteur ?? '—' }}</div>
        </div>
        <div>
            <div class="label">ETD / ETA</div>
            <div class="value">{{ optional($export->etd)->format('d/m/Y') ?? '—' }} / {{ optional($export->eta)->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    @if(in_array($type, ['fiche_production', 'fiche_preparation', 'fiche_chargement', 'attestation_conditionnement', 'facture_srr']))
        <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">Articles</h2>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Désignation</th>
                    <th>Qté</th>
                    <th>Cartons</th>
                    <th>Palettes</th>
                    <th>Poids net</th>
                    <th>Poids brut</th>
                    @if($type === 'facture_srr')
                        <th>Prix</th>
                        <th>Montant</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($export->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->code_article }}</td>
                        <td>{{ $ligne->designation }}</td>
                        <td>{{ $ligne->quantite }}</td>
                        <td>{{ $ligne->cartons }}</td>
                        <td>{{ $ligne->palettes }}</td>
                        <td>{{ $ligne->poids_net }}</td>
                        <td>{{ $ligne->poids_brut }}</td>
                        @if($type === 'facture_srr')
                            <td>{{ $ligne->prix_unitaire }}</td>
                            <td>{{ $ligne->montant }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals">
            <div>Total articles : <strong>{{ $export->total_quantite }}</strong></div>
            <div>Cartons / Palettes : <strong>{{ $export->total_cartons }} / {{ $export->total_palettes }}</strong></div>
            <div>Poids net / brut : <strong>{{ $export->total_poids_net }} / {{ $export->total_poids_brut }}</strong></div>
            @if($type === 'facture_srr')
                <div>Valeur : <strong>{{ number_format($export->total_valeur, 2, ',', ' ') }} {{ $export->devise }}</strong></div>
            @endif
        </div>
    @endif

    @if($type === 'fiche_chauffeur')
        <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">Chauffeur / Transport</h2>
        <div class="grid">
            <div><div class="label">Chauffeur</div><div class="value">{{ $export->chauffeur ?? '—' }}</div></div>
            <div><div class="label">CIN</div><div class="value">{{ $export->chauffeur_cin ?? '—' }}</div></div>
            <div><div class="label">Transporteur</div><div class="value">{{ $export->transporteur ?? '—' }}</div></div>
            <div><div class="label">Immatriculation</div><div class="value">{{ $export->immatriculation ?? '—' }}</div></div>
        </div>
    @endif

    @if($type === 'solas_vgm')
        <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">SOLAS / VGM</h2>
        <div class="grid">
            <div><div class="label">Conteneur</div><div class="value">{{ $export->conteneur }}</div></div>
            <div><div class="label">Poids VGM</div><div class="value">{{ $export->vgm_poids }} kg</div></div>
            <div><div class="label">Plomb</div><div class="value">{{ $export->plomb_scelle }}</div></div>
            <div><div class="label">Booking / BL</div><div class="value">{{ $export->booking }} / {{ $export->numero_swb_bl }}</div></div>
        </div>
    @endif

    @if($type === 'instructions_bl' || $type === 'fiche_booking')
        <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">Expédition</h2>
        <div class="grid">
            <div><div class="label">Shipper / Consignee</div><div class="value">{{ $export->exp_nom ?: config('app.name') }} / {{ $export->dest_nom ?: $export->client?->nom }}</div></div>
            <div><div class="label">Notify</div><div class="value">{{ $export->dest_nom ?: $export->client?->nom }}</div></div>
            <div><div class="label">Ports</div><div class="value">{{ $export->port_chargement }} → {{ $export->port_destination }}</div></div>
            <div><div class="label">Navire / Voyage</div><div class="value">{{ $export->navire }} / {{ $export->voyage }}</div></div>
        </div>
    @endif

    @if(in_array($type, ['situation_dum52', 'etat_retour', 'facture_srr']) && $dossier)
        <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">Dossier emballages temporaires — Régime 52</h2>
        <div class="grid">
            <div><div class="label">DUM 52</div><div class="value">{{ $dossier->dum_52 ?? '—' }}</div></div>
            <div><div class="label">Échéance</div><div class="value">{{ optional($dossier->date_limite_reimportation)->format('d/m/Y') ?? '—' }}</div></div>
            <div><div class="label">Exportés</div><div class="value">{{ $dossier->quantite_exportee }}</div></div>
            <div><div class="label">Retournés</div><div class="value">{{ $dossier->quantite_reimporte }}</div></div>
            <div><div class="label">Solde</div><div class="value">{{ $dossier->quantite_restante }}</div></div>
            <div><div class="label">Statut</div><div class="value"><span class="badge">{{ $dossier->statut }}</span></div></div>
        </div>

        @if($type === 'etat_retour' && $dossier->retours->count())
            <h2 style="font-size:14px;margin:18px 0 8px;border-bottom:1px solid #ccc;padding-bottom:4px;">Retours enregistrés</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>DUM retour</th>
                        <th>Quantité</th>
                        <th>Type</th>
                        <th>Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dossier->retours as $retour)
                        <tr>
                            <td>{{ optional($retour->date_reimportation)->format('d/m/Y') }}</td>
                            <td>{{ $retour->dum_reimportation }}</td>
                            <td>{{ $retour->quantite }}</td>
                            <td>{{ $retour->type_emballage }}</td>
                            <td>{{ $retour->observations }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
@endif

    <p style="margin-top:28px;color:#888;font-size:9px;">
        Document généré automatiquement depuis le dossier — saisie unique, réutilisation automatique.
    </p>
</body>
</html>
