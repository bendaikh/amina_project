@if($marchandises['groupes'] === [])
    <p class="line muted">—</p>
@else
    @foreach($marchandises['groupes'] as $groupe)
        <p class="hs">HS CODE {{ $groupe['hs_code'] }}</p>
        @foreach($groupe['lignes'] as $ligne)
            <p class="goods">{{ $ligne['libelle'] }} &nbsp; POIDS BRUT : {{ $ligne['poids'] }} KG</p>
        @endforeach
    @endforeach
@endif
<p class="totals">TOTAL : {{ $marchandises['total_colis_label'] }} COLIS / PACKAGES</p>
<p class="totals">POIDS BRUT TOTAL : {{ $marchandises['poids_brut_label'] }} KG</p>
