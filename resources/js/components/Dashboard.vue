<template>
  <div class="dash-wrap">
    <header class="dash-header">
      <div class="dash-header-text">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Tableau de bord</h1>
        <p class="text-sm text-gray-500 mt-1">Vue consolidée · Commercial, finance, stock, production et export</p>
      </div>
      <div class="dash-search">
        <input
          v-model="q"
          @input="search"
          type="search"
          placeholder="Recherche : DUM, export, client…"
          class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
        />
        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <div v-if="results.length" class="absolute z-20 mt-1 w-full bg-white border border-gray-100 rounded-lg shadow-lg max-h-72 overflow-y-auto">
          <button
            v-for="(r, i) in results"
            :key="i"
            type="button"
            @click="go(r)"
            class="w-full text-left px-4 py-2 hover:bg-teal-50 border-b border-gray-50 last:border-0"
          >
            <span class="text-xs uppercase text-gray-400">{{ r.type }}</span>
            <div class="font-medium text-sm">{{ r.label }}</div>
            <div class="text-xs text-gray-500">{{ r.meta }}</div>
          </button>
        </div>
      </div>
    </header>

    <section class="dash-filters">
      <div class="dash-period-row">
        <button
          v-for="p in periodes"
          :key="p.value"
          type="button"
          class="dash-period-btn"
          :class="{ 'is-active': filters.period === p.value }"
          @click="setPeriod(p.value)"
        >{{ p.label }}</button>
      </div>
      <div v-if="filters.period === 'perso'" class="dash-period-row">
        <input v-model="filters.from" type="date" @change="load" />
        <input v-model="filters.to" type="date" @change="load" />
      </div>
      <div class="dash-filter-grid">
        <select v-model="filters.client_id" @change="load">
          <option value="">Client</option>
          <option v-for="c in meta.clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
        </select>
        <select v-model="filters.fournisseur_id" @change="load">
          <option value="">Fournisseur</option>
          <option v-for="f in meta.fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
        </select>
        <select v-model="filters.commercial" @change="load">
          <option value="">Commercial</option>
          <option v-for="c in meta.commerciaux" :key="c" :value="c">{{ c }}</option>
        </select>
        <select v-model="filters.pays" @change="load">
          <option value="">Pays</option>
          <option v-for="p in meta.pays" :key="p" :value="p">{{ p }}</option>
        </select>
        <select v-model="filters.type_vente" @change="load">
          <option v-for="t in meta.types_vente" :key="t.value" :value="t.value">{{ t.label }}</option>
        </select>
        <select v-model="filters.article_id" @change="load">
          <option value="">Article</option>
          <option v-for="a in meta.articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
        </select>
        <select v-model="filters.statut" @change="load">
          <option value="">Statut export</option>
          <option v-for="s in meta.statuts_export" :key="s" :value="s">{{ s }}</option>
        </select>
        <select v-model="filters.devise" @change="load">
          <option value="">Devise</option>
          <option v-for="d in meta.devises" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>
    </section>

    <div class="dash-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="dash-tab"
        :class="{ 'is-active': activeTab === tab.id }"
        @click="activeTab = tab.id"
      >{{ tab.label }}</button>
    </div>

    <div v-if="loading" class="text-center text-gray-500 py-16">Chargement…</div>

    <template v-else>
      <!-- Vue d'ensemble -->
      <template v-if="activeTab === 'vue'">
        <section class="dash-section">
          <h2 class="dash-section-title">Indicateurs clés</h2>
          <div class="kpi-grid kpi-dense">
            <div v-for="card in overviewCards" :key="card.title" class="kpi-card" :class="{ 'is-soon': card.soon }" :style="{ borderLeftColor: card.color }">
              <p class="kpi-title">{{ card.title }}</p>
              <p class="kpi-value">{{ card.soon ? '—' : card.value }}</p>
            </div>
          </div>
        </section>

        <div class="dash-overview-grid">
          <section class="dash-section" style="margin-bottom:0">
            <h2 class="dash-section-title">Alertes prioritaires</h2>
            <div class="dash-alert-grid">
              <button
                v-for="a in alertesPrioritaires"
                :key="a.key"
                type="button"
                class="dash-alert"
                :class="alerteClass(a)"
                @click="a.disponible !== false && a.path && $router.push(a.path)"
              >
                <div class="dash-alert-label">
                  <span>{{ a.label }}</span>
                  <span v-if="a.disponible === false" class="text-[10px] uppercase text-gray-400">À venir</span>
                </div>
                <div class="dash-alert-count">{{ a.disponible === false ? '—' : a.count }}</div>
              </button>
            </div>
          </section>

          <section class="dash-panel">
            <h3>Répartition export</h3>
            <div class="space-y-3">
              <div v-for="(total, statut) in statuts" :key="statut" class="flex items-center gap-3">
                <span class="w-28 text-xs text-gray-600 capitalize truncate">{{ statut }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden min-w-0">
                  <div class="h-2 bg-teal-500 rounded-full" :style="{ width: barWidth(total) + '%' }"></div>
                </div>
                <span class="w-8 text-sm font-semibold text-right">{{ total }}</span>
              </div>
              <div v-if="!Object.keys(statuts).length" class="text-gray-400 text-sm py-6 text-center">Aucune donnée</div>
            </div>
            <router-link to="/ventes/export/colisage" class="inline-block mt-4 text-teal-600 text-sm font-medium hover:underline">
              Voir les exportations →
            </router-link>
          </section>
        </div>
      </template>

      <!-- Alertes -->
      <section v-if="activeTab === 'alertes'" class="dash-section">
        <h2 class="dash-section-title">Toutes les alertes</h2>
        <div class="dash-alert-grid">
          <button
            v-for="a in alertes"
            :key="a.key"
            type="button"
            class="dash-alert"
            :class="alerteClass(a)"
            @click="a.disponible !== false && a.path && $router.push(a.path)"
          >
            <div class="dash-alert-label">
              <span>{{ a.label }}</span>
              <span v-if="a.disponible === false" class="text-[10px] uppercase text-gray-400">À venir</span>
            </div>
            <div class="dash-alert-count">{{ a.disponible === false ? '—' : a.count }}</div>
          </button>
        </div>
      </section>

      <!-- Commercial -->
      <template v-if="activeTab === 'commercial'">
        <section class="dash-section">
          <h2 class="dash-section-title">Indicateurs commerciaux</h2>
          <div class="kpi-grid">
            <div v-for="card in commercialCards" :key="card.title" class="kpi-card" :class="{ 'is-soon': card.soon }" :style="{ borderLeftColor: card.color }">
              <p class="kpi-title">{{ card.title }}</p>
              <p class="kpi-value">{{ card.soon ? '—' : card.value }}</p>
            </div>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="dash-panel">
              <h3>Ventes par client</h3>
              <div v-if="!commercial.par_client?.length" class="text-sm text-gray-400 py-4 text-center">Aucune donnée</div>
              <div v-for="row in commercial.par_client" :key="'c-'+row.label" class="dash-rank-row">
                <span class="truncate text-gray-700">{{ row.label }}</span>
                <span class="font-semibold shrink-0">{{ fmtMoney(row.total) }}</span>
              </div>
            </div>
            <div class="dash-panel">
              <h3>Ventes par commercial</h3>
              <div v-if="!commercial.par_commercial?.length" class="text-sm text-gray-400 py-4 text-center">Aucune donnée</div>
              <div v-for="row in commercial.par_commercial" :key="'m-'+row.label" class="dash-rank-row">
                <span class="truncate text-gray-700">{{ row.label }}</span>
                <span class="font-semibold shrink-0">{{ fmtMoney(row.total) }}</span>
              </div>
            </div>
            <div class="dash-panel">
              <h3>Ventes par pays</h3>
              <div v-if="!commercial.par_pays?.length" class="text-sm text-gray-400 py-4 text-center">Aucune donnée</div>
              <div v-for="row in commercial.par_pays" :key="'p-'+row.label" class="dash-rank-row">
                <span class="truncate text-gray-700">{{ row.label }}</span>
                <span class="font-semibold shrink-0">{{ fmtMoney(row.total) }}</span>
              </div>
            </div>
          </div>
        </section>
      </template>

      <!-- Finance -->
      <section v-if="activeTab === 'financier'" class="dash-section">
        <h2 class="dash-section-title">Indicateurs financiers</h2>
        <div class="kpi-grid">
          <div v-for="card in financierCards" :key="card.title" class="kpi-card" :class="{ 'is-soon': card.soon }" :style="{ borderLeftColor: card.color }">
            <p class="kpi-title">{{ card.title }}</p>
            <p class="kpi-value">{{ card.soon ? '—' : card.value }}</p>
          </div>
        </div>
      </section>

      <!-- Stock -->
      <section v-if="activeTab === 'stock'" class="dash-section">
        <h2 class="dash-section-title">Indicateurs stock</h2>
        <div class="kpi-grid">
          <div v-for="card in stockCards" :key="card.title" class="kpi-card" :class="{ 'is-soon': card.soon }" :style="{ borderLeftColor: card.color }">
            <p class="kpi-title">{{ card.title }}</p>
            <p class="kpi-value">{{ card.soon ? '—' : card.value }}</p>
          </div>
        </div>
      </section>

      <!-- Production -->
      <section v-if="activeTab === 'production'" class="dash-section">
        <h2 class="dash-section-title">Indicateurs production</h2>
        <p class="dash-section-note">Basé sur le pipeline des exportations (module production dédié à venir).</p>
        <div class="kpi-grid">
          <div v-for="card in productionCards" :key="card.title" class="kpi-card" :class="{ 'is-soon': card.soon }" :style="{ borderLeftColor: card.color }">
            <p class="kpi-title">{{ card.title }}</p>
            <p class="kpi-value">{{ card.soon ? '—' : card.value }}</p>
          </div>
        </div>
      </section>

      <!-- Export -->
      <template v-if="activeTab === 'export'">
        <section class="dash-section">
          <h2 class="dash-section-title">Indicateurs export</h2>
          <div class="kpi-grid">
            <div v-for="card in exportCards" :key="card.title" class="kpi-card" :style="{ borderLeftColor: card.color }">
              <p class="kpi-title">{{ card.title }}</p>
              <p class="kpi-value">{{ card.value }}</p>
            </div>
          </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
          <section class="dash-panel">
            <h3>Alertes DUM 52</h3>
            <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
              <div class="p-3 bg-amber-50 rounded-lg">Partiellement soldées : <strong>{{ alertesDum.partiellement_soldee ?? 0 }}</strong></div>
              <div class="p-3 bg-red-50 rounded-lg">Fûts non retournés : <strong>{{ alertesDum.futs_non_retournes ?? 0 }}</strong></div>
              <div class="p-3 bg-rose-50 rounded-lg">En dépassement : <strong>{{ alertesDum.en_depassement ?? 0 }}</strong></div>
              <div class="p-3 bg-green-50 rounded-lg">DUM soldées : <strong>{{ alertesDum.dum_soldee ?? 0 }}</strong></div>
            </div>
            <div v-if="!dumProches.length" class="text-gray-400 text-sm py-6 text-center">Aucune échéance proche</div>
            <ul class="space-y-2">
              <li
                v-for="d in dumProches"
                :key="d.id"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 border border-gray-100 rounded-lg text-sm"
              >
                <div class="min-w-0">
                  <div class="font-medium truncate">{{ d.dum_52 || 'Sans N° DUM' }} — {{ d.client }}</div>
                  <div class="text-gray-500">{{ d.export }} · Solde {{ d.restante }} · {{ d.statut }}</div>
                </div>
                <div class="sm:text-right shrink-0">
                  <div :class="d.jours_restants < 0 ? 'text-red-600 font-bold' : 'text-amber-600'">{{ d.echeance }}</div>
                  <div class="text-xs text-gray-400">{{ d.alerte }}</div>
                </div>
              </li>
            </ul>
            <router-link to="/ventes/export/emballages" class="inline-block mt-4 text-teal-600 text-sm font-medium hover:underline">
              Voir tous les dossiers →
            </router-link>
          </section>

          <section class="dash-panel">
            <h3>Répartition des exportations</h3>
            <div class="space-y-3">
              <div v-for="(total, statut) in statuts" :key="statut" class="flex items-center gap-3">
                <span class="w-28 text-xs text-gray-600 capitalize truncate">{{ statut }}</span>
                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden min-w-0">
                  <div class="h-2 bg-teal-500 rounded-full" :style="{ width: barWidth(total) + '%' }"></div>
                </div>
                <span class="w-8 text-sm font-semibold text-right">{{ total }}</span>
              </div>
              <div v-if="!Object.keys(statuts).length" class="text-gray-400 text-sm py-8 text-center">Aucune donnée</div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
              <router-link to="/ventes/export/colisage" class="px-4 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700">
                Nouvelle exportation
              </router-link>
              <router-link to="/articles" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white hover:bg-gray-50">
                Articles
              </router-link>
            </div>
          </section>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Dashboard',
  data() {
    return {
      loading: true,
      activeTab: 'vue',
      tabs: [
        { id: 'vue', label: 'Vue d’ensemble' },
        { id: 'commercial', label: 'Commercial' },
        { id: 'financier', label: 'Finance' },
        { id: 'stock', label: 'Stock' },
        { id: 'production', label: 'Production' },
        { id: 'export', label: 'Export' },
        { id: 'alertes', label: 'Alertes' },
      ],
      filters: {
        period: 'mois',
        from: '',
        to: '',
        client_id: '',
        fournisseur_id: '',
        commercial: '',
        pays: '',
        type_vente: '',
        article_id: '',
        statut: '',
        devise: '',
      },
      meta: {
        clients: [],
        fournisseurs: [],
        articles: [],
        commerciaux: [],
        pays: [],
        devises: [],
        statuts_export: [],
        types_vente: [{ value: '', label: 'Type de vente' }],
        periodes: [],
      },
      commercial: {},
      financier: {},
      stock: {},
      production: {},
      exportData: {},
      alertes: [],
      alertesDum: {},
      dumProches: [],
      statuts: {},
      q: '',
      results: [],
      timer: null,
    };
  },
  computed: {
    periodes() {
      return this.meta.periodes?.length
        ? this.meta.periodes
        : [
            { value: 'aujourdhui', label: "Aujourd'hui" },
            { value: 'semaine', label: 'Semaine' },
            { value: 'mois', label: 'Mois' },
            { value: 'trimestre', label: 'Trimestre' },
            { value: 'annee', label: 'Année' },
            { value: 'perso', label: 'Période personnalisée' },
          ];
    },
    overviewCards() {
      const c = this.commercial || {};
      const f = this.financier || {};
      const s = this.stock || {};
      const e = this.exportData || {};
      return [
        { title: 'CA mois', value: this.fmtMoney(c.ca_mois), color: '#0d9488' },
        { title: 'CA année', value: this.fmtMoney(c.ca_annee), color: '#14b8a6' },
        { title: 'Exports en cours', value: c.commandes_en_cours ?? 0, color: '#3b82f6' },
        { title: 'En retard', value: c.commandes_en_retard ?? 0, color: '#ef4444' },
        { title: 'Dettes fournisseurs', value: this.fmtMoney(f.dettes_fournisseurs), color: '#f97316' },
        { title: 'Stock disponible', value: this.fmtQty(s.disponible), color: '#22c55e' },
        { title: 'Docs manquants', value: e.documents_manquants ?? 0, color: '#e11d48' },
        { title: 'DUM à échéance', value: e.echeances_douanieres_proches ?? 0, color: '#f59e0b' },
        { title: 'Emballages à retourner', value: e.emballages_a_retourner ?? 0, color: '#f43f5e' },
        { title: 'Conteneurs en cours', value: e.conteneurs_en_cours ?? 0, color: '#0ea5e9' },
      ];
    },
    alertesPrioritaires() {
      return (this.alertes || [])
        .filter((a) => a.disponible !== false && Number(a.count) > 0)
        .concat((this.alertes || []).filter((a) => a.disponible !== false && Number(a.count) === 0))
        .slice(0, 8);
    },
    commercialCards() {
      const c = this.commercial || {};
      return [
        { title: "CA aujourd'hui", value: this.fmtMoney(c.ca_jour), color: '#0d9488' },
        { title: 'CA mois', value: this.fmtMoney(c.ca_mois), color: '#14b8a6' },
        { title: 'CA année', value: this.fmtMoney(c.ca_annee), color: '#2dd4bf' },
        { title: 'Ventes export', value: this.fmtMoney(c.ventes_export), color: '#3b82f6' },
        { title: 'Ventes locales', soon: true, color: '#94a3b8' },
        { title: 'Commandes en cours', value: c.commandes_en_cours ?? 0, color: '#f59e0b' },
        { title: 'Commandes en retard', value: c.commandes_en_retard ?? 0, color: '#ef4444' },
        { title: 'Partiellement livrées', soon: true, color: '#94a3b8' },
        { title: 'BL non facturés', soon: true, color: '#94a3b8' },
        { title: 'Factures non réglées', soon: true, color: '#94a3b8' },
      ];
    },
    financierCards() {
      const f = this.financier || {};
      return [
        { title: 'Créances clients', soon: true, color: '#94a3b8' },
        { title: 'Dettes fournisseurs', value: this.fmtMoney(f.dettes_fournisseurs), color: '#f97316' },
        { title: 'Règlements à recevoir', soon: true, color: '#94a3b8' },
        { title: 'Règlements à effectuer', value: this.fmtMoney(f.reglements_a_effectuer), color: '#ea580c' },
        { title: 'Factures échues', value: `${f.factures_echues ?? 0} · ${this.fmtMoney(f.factures_echues_montant)}`, color: '#ef4444' },
        { title: 'Partiellement réglées', soon: true, color: '#94a3b8' },
        { title: 'Notes de crédit', soon: true, color: '#94a3b8' },
        { title: 'Solde bancaire', soon: true, color: '#94a3b8' },
        { title: 'Encaissements', soon: true, color: '#94a3b8' },
        { title: 'Décaissements', soon: true, color: '#94a3b8' },
        { title: 'Trésorerie prévisionnelle', soon: true, color: '#94a3b8' },
      ];
    },
    stockCards() {
      const s = this.stock || {};
      return [
        { title: 'Stock total', value: this.fmtQty(s.total), color: '#0f766e' },
        { title: 'Disponible', value: this.fmtQty(s.disponible), color: '#14b8a6' },
        { title: 'Réservé', value: this.fmtQty(s.reserve), color: '#f59e0b' },
        { title: 'Sous seuil', soon: true, color: '#94a3b8' },
        { title: 'En rupture', value: s.en_rupture ?? 0, color: '#ef4444' },
        { title: 'Rotation lente', soon: true, color: '#94a3b8' },
        { title: 'Entrées', value: this.fmtQty(s.entrees), color: '#22c55e' },
        { title: 'Sorties', value: this.fmtQty(s.sorties), color: '#f97316' },
        { title: 'Retours', value: this.fmtQty(s.retours), color: '#8b5cf6' },
        { title: 'Écarts inventaire', value: this.fmtQty(s.ecarts_inventaire), color: '#e11d48' },
        { title: 'Quarantaine', value: this.fmtQty(s.quarantaine), color: '#a855f7' },
        { title: 'Endommagé', value: this.fmtQty(s.endommage), color: '#dc2626' },
        { title: 'En transit', value: this.fmtQty(s.transit), color: '#3b82f6' },
      ];
    },
    productionCards() {
      const p = this.production || {};
      return [
        { title: 'Ordres à planifier', value: p.ordres_a_planifier ?? 0, color: '#3b82f6' },
        { title: 'Ordres en cours', value: p.ordres_en_cours ?? 0, color: '#f59e0b' },
        { title: 'Ordres en retard', value: p.ordres_en_retard ?? 0, color: '#ef4444' },
        { title: 'Qté à produire', value: this.fmtQty(p.qte_a_produire), color: '#0ea5e9' },
        { title: 'Qté produite', value: this.fmtQty(p.qte_produite), color: '#22c55e' },
        { title: 'Bloquées (stock)', soon: true, color: '#94a3b8' },
        { title: 'Ordres terminés', value: p.ordres_termines ?? 0, color: '#64748b' },
      ];
    },
    exportCards() {
      const e = this.exportData || {};
      return [
        { title: 'En préparation', value: e.en_preparation ?? 0, color: '#3b82f6' },
        { title: 'Prêtes', value: e.pretes ?? 0, color: '#f59e0b' },
        { title: 'Chargées', value: e.chargees ?? 0, color: '#f97316' },
        { title: 'Expédiées', value: e.expediees ?? 0, color: '#14b8a6' },
        { title: 'Conteneurs en cours', value: e.conteneurs_en_cours ?? 0, color: '#0ea5e9' },
        { title: 'Documents manquants', value: e.documents_manquants ?? 0, color: '#e11d48' },
        { title: 'DUM 52 non soldées', value: e.dum_52_non_soldees ?? 0, color: '#f43f5e' },
        { title: 'Emballages à retourner', value: e.emballages_a_retourner ?? 0, color: '#ef4444' },
        { title: 'Échéances douanières', value: e.echeances_douanieres_proches ?? 0, color: '#f59e0b' },
        { title: 'Arrivées', value: e.arrivees ?? 0, color: '#22c55e' },
        { title: 'Clôturées', value: e.cloturees ?? 0, color: '#64748b' },
        { title: 'Qté à réimporter', value: this.fmtQty(e.qte_a_reimporter), color: '#f43f5e' },
      ];
    },
  },
  mounted() { this.load(); },
  methods: {
    setPeriod(value) {
      this.filters.period = value;
      if (value !== 'perso') this.load();
    },
    async load() {
      this.loading = true;
      try {
        const params = { ...this.filters };
        Object.keys(params).forEach((k) => {
          if (params[k] === '' || params[k] === null) delete params[k];
        });
        const { data } = await axios.get('/api/dashboard/kpis', { params });
        this.commercial = data.commercial || {};
        this.financier = data.financier || {};
        this.stock = data.stock || {};
        this.production = data.production || {};
        this.exportData = data.export || data.kpis || {};
        this.alertes = data.alertes || [];
        this.alertesDum = data.alertes_dum || {};
        this.dumProches = data.dum_proches || [];
        this.statuts = data.statuts || {};
        if (data.filters_meta) this.meta = { ...this.meta, ...data.filters_meta };
      } catch (e) {
        console.error(e);
      } finally {
        this.loading = false;
      }
    },
    alerteClass(a) {
      if (a.disponible === false) return 'is-soon';
      if (a.severity === 'red') return 'is-red';
      if (a.severity === 'amber') return 'is-amber';
      if (a.severity === 'muted') return 'is-muted';
      return '';
    },
    fmtMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
    },
    fmtQty(v) {
      return Number(v || 0).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    maxStatut() {
      const vals = Object.values(this.statuts).map(Number);
      return Math.max(1, ...(vals.length ? vals : [1]));
    },
    barWidth(total) {
      return Math.round((Number(total) / this.maxStatut()) * 100);
    },
    search() {
      clearTimeout(this.timer);
      this.timer = setTimeout(async () => {
        if (this.q.length < 2) { this.results = []; return; }
        try {
          const { data } = await axios.get('/api/search', { params: { q: this.q } });
          this.results = data.results || [];
        } catch {
          this.results = [];
        }
      }, 250);
    },
    go(r) {
      this.results = [];
      this.q = '';
      if (r.path) this.$router.push(r.path);
    },
  },
};
</script>
