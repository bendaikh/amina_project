@php
    $s = $sheet ?? [];
    $txt = function ($value) {
        return filled($value) ? $value : '—';
    };
    $kg = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        $n = (float) $value;
        $dec = abs($n - round($n)) < 0.0005 ? 0 : 3;

        return number_format($n, $dec, ',', ' ') . ' kg';
    };
    $when = function ($value) {
        if (!$value) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $day = function ($value) {
        if (!$value) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $tick = function (bool $on) {
        return '<span class="tick">' . ($on ? 'X' : '') . '</span>';
    };
    $typeBox = $s['type_conteneur'] ?? null;
@endphp

<p class="doc-company">{{ $txt($s['societe'] ?? null) }}</p>

@if($type === 'solas_vgm')
    <p class="doc-title">FICHE VGM</p>
    <p class="doc-sub">VERIFIED GROSS MASS — EXPORT MARITIME</p>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">1. Identification du conteneur</td></tr>
        <tr>
            <td class="lbl">Type de conteneur</td>
            <td>
                <span class="opt">{!! $tick($typeBox === '20') !!} 20'</span>
                <span class="opt">{!! $tick($typeBox === '40') !!} 40'</span>
                <span class="opt">{!! $tick($typeBox === '45') !!} 45'</span>
            </td>
        </tr>
        <tr>
            <td class="lbl">N° conteneur</td>
            <td class="val">{{ $txt($s['numero_conteneur'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">N° plomb / scellé</td>
            <td class="val">{{ $txt($s['plomb'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">2. Références d'expédition</td></tr>
        <tr>
            <td class="lbl">N° booking</td>
            <td class="val">{{ $txt($s['booking'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Compagnie maritime</td>
            <td class="val">{{ $txt($s['compagnie'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Navire / voyage</td>
            <td class="val">{{ $txt($s['navire_voyage'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Terminal</td>
            <td class="val">{{ $txt($s['terminal'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">3. Détail du poids</td></tr>
        <tr>
            <td class="lbl">Poids brut marchandise <span class="muted">(palettes incluses)</span></td>
            <td class="val">{{ $kg($s['poids_marchandise'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Tare du conteneur</td>
            <td class="val">{{ $kg($s['tare'] ?? null) }}</td>
        </tr>
        <tr class="vgm-row">
            <td class="lbl">Masse brute vérifiée (VGM)</td>
            <td class="val">{{ $kg($s['vgm'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Méthode de détermination</td>
            <td>
                <div>{!! $tick(($s['methode'] ?? null) === '1') !!} Méthode 1 — Pesée du conteneur chargé</div>
                <div style="margin-top:4px;">{!! $tick(($s['methode'] ?? null) === '2') !!} Méthode 2 — Calcul des masses</div>
            </td>
        </tr>
    </table>

    <table class="sec">
        <tr>
            <td class="lbl">Date</td>
            <td class="val">{{ $day($s['date'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec sign">
        <tr>
            <td>Cachet de la société</td>
        </tr>
    </table>
@else
    <p class="doc-title">FICHE CHAUFFEUR</p>
    <p class="doc-sub">TRANSPORT ROUTIER — EXPORT</p>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">1. Références du transport</td></tr>
        <tr>
            <td class="lbl">N° facture</td>
            <td class="val">{{ $txt($s['facture'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">N° booking</td>
            <td class="val">{{ $txt($s['booking'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Transporteur</td>
            <td class="val">{{ $txt($s['transporteur'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">2. Identité du chauffeur</td></tr>
        <tr>
            <td class="lbl">Nom et prénom</td>
            <td class="val">{{ $txt($s['chauffeur'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Téléphone</td>
            <td class="val">{{ $txt($s['telephone'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">N° CIN / pièce d'identité</td>
            <td class="val">{{ $txt($s['cin'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Immatriculation</td>
            <td class="val">{{ $txt($s['immatriculation'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">N° conteneur</td>
            <td class="val">{{ $txt($s['numero_conteneur'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Type de conteneur</td>
            <td>
                <span class="opt">{!! $tick($typeBox === '20') !!} 20'</span>
                <span class="opt">{!! $tick($typeBox === '40') !!} 40'</span>
                <span class="opt">{!! $tick($typeBox === '45') !!} 45'</span>
            </td>
        </tr>
        <tr>
            <td class="lbl">N° plomb après chargement</td>
            <td class="val">{{ $txt($s['plomb'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Poids brut déclaré</td>
            <td class="val">{{ $kg($s['poids_declare'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">3. Itinéraire et horaires</td></tr>
        <tr>
            <td class="lbl">Lieu de chargement</td>
            <td class="val">{{ $txt($s['lieu_chargement'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Destination</td>
            <td class="val">{{ $txt($s['destination'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Date et heure d'arrivée à l'usine</td>
            <td class="val">{{ $when($s['arrivee'] ?? null) }}</td>
        </tr>
        <tr>
            <td class="lbl">Date et heure de départ</td>
            <td class="val">{{ $when($s['depart'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec">
        <tr><td class="sec-h" colspan="2">4. Contrôle avant départ</td></tr>
        <tr>
            <td colspan="2">{!! $tick(!empty($s['ctrl_conteneur'])) !!} Numéro du conteneur contrôlé</td>
        </tr>
        <tr>
            <td colspan="2">{!! $tick(!empty($s['ctrl_plomb'])) !!} Plomb posé et numéro vérifié</td>
        </tr>
        <tr>
            <td colspan="2">{!! $tick(!empty($s['ctrl_documents'])) !!} Chargement et documents validés</td>
        </tr>
        <tr>
            <td class="lbl">Observations / réserves</td>
            <td class="val">{{ $txt($s['observations'] ?? null) }}</td>
        </tr>
    </table>

    <table class="sec sign">
        <tr>
            <td>Signature du chauffeur</td>
            <td>Signature et cachet usine</td>
        </tr>
    </table>
@endif

<p class="note">Document généré automatiquement — un exemplaire par conteneur. {{ $export->numero }} · {{ now()->format('d/m/Y H:i') }}</p>
