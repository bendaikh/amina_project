<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Rapports</p>
          <h1>Stock</h1>
          <p>{{ report?.valorisation }}</p>
          <p class="fin-note">{{ report?.alerte }}</p>
        </div>
        <div class="fin-export">
          <span>Exporter</span>
          <button v-for="fmt in ['pdf', 'xlsx', 'csv', 'print']" :key="fmt" type="button" @click="exportFile(fmt)">{{ fmt === 'print' ? 'Imprimer' : fmt.toUpperCase() }}</button>
        </div>
      </header>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--4">
          <label class="fin-field">Recherche<input v-model="filters.search" placeholder="Produit, code, lot…" /></label>
          <label class="fin-field">Emplacement
            <select v-model="filters.location_id">
              <option value="">Tous</option>
              <option v-for="e in meta.emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
            </select>
          </label>
          <label class="fin-field">Produit
            <select v-model="filters.article_id">
              <option value="">Tous</option>
              <option v-for="a in meta.articles" :key="a.id" :value="a.id">{{ a.designation }}</option>
            </select>
          </label>
          <label class="fin-field">Catégorie
            <select v-model="filters.categorie">
              <option value="">Toutes</option>
              <option v-for="c in meta.categories" :key="c" :value="c">{{ c }}</option>
            </select>
          </label>
          <label class="fin-field">Situation
            <select v-model="filters.situation">
              <option value="">Toute</option>
              <option value="disponible">Disponible</option>
              <option value="faible">Stock faible</option>
              <option value="rupture">Rupture</option>
            </select>
          </label>
          <FinDate v-model="filters.from" label="Du" />
          <FinDate v-model="filters.to" label="Au" />
        </div>
        <div class="fin-bar"><span></span><button type="button" class="fin-btn fin-btn--primary" @click="load">Actualiser</button></div>
      </section>

      <div v-if="report" class="fin-kpis fin-kpis--5">
        <article class="fin-kpi"><span class="fin-kpi__label">Produits</span><strong>{{ report.kpis.produits }}</strong></article>
        <article class="fin-kpi"><span class="fin-kpi__label">Quantités</span><strong>{{ report.kpis.quantite }}</strong></article>
        <article class="fin-kpi"><span class="fin-kpi__label">Valeur</span><strong>{{ dh(report.kpis.valeur) }}</strong></article>
        <article class="fin-kpi fin-kpi--amber"><span class="fin-kpi__label">Stock faible</span><strong>{{ report.kpis.faibles }}</strong></article>
        <article class="fin-kpi fin-kpi--rose"><span class="fin-kpi__label">Ruptures</span><strong>{{ report.kpis.ruptures }}</strong></article>
      </div>

      <div v-if="report" class="grid lg:grid-cols-2 gap-4 mb-4">
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Par emplacement</h2>
          <p v-for="row in report.par_emplacement" :key="row.label" class="flex justify-between text-sm border-b py-1"><span>{{ row.label }}</span><span>{{ row.quantite }} · {{ dh(row.valeur) }}</span></p>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Par catégorie</h2>
          <p v-for="row in report.par_categorie" :key="row.label" class="flex justify-between text-sm border-b py-1"><span>{{ row.label }}</span><span>{{ row.quantite }} · {{ dh(row.valeur) }}</span></p>
          <p class="text-sm text-gray-500 mt-3">Entrées {{ report.entrees }} · Sorties {{ report.sorties }}</p>
        </section>
      </div>

      <div v-if="report" class="bg-white border rounded-xl overflow-x-auto mb-4">
        <h2 class="font-semibold p-4">Situation actuelle</h2>
        <table class="w-full text-sm">
          <thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Produit</th><th>Catégorie</th><th>Emplacement</th><th>Lot</th><th>Théorique</th><th>Disponible</th><th>Valeur</th></tr></thead>
          <tbody>
            <tr v-for="(row, i) in report.situation" :key="i" class="border-t">
              <td class="px-3 py-2">{{ row.code }} · {{ row.designation }}</td>
              <td>{{ row.categorie }}</td>
              <td>{{ row.emplacement }}</td>
              <td>{{ row.lot || '—' }}</td>
              <td>{{ row.stock_theorique }}</td>
              <td>{{ row.stock_disponible }}</td>
              <td>{{ dh(row.valeur) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="report" class="bg-white border rounded-xl overflow-x-auto">
        <h2 class="font-semibold p-4">Mouvements</h2>
        <table class="w-full text-sm">
          <thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Date</th><th>Type</th><th>Produit</th><th>Quantité</th><th>Emplacement</th><th>Document</th></tr></thead>
          <tbody>
            <tr v-for="(row, i) in report.mouvements" :key="i" class="border-t">
              <td class="px-3 py-2">{{ dateFr(row.date) }}</td>
              <td>{{ row.type }}</td>
              <td>{{ row.produit }}</td>
              <td :class="row.sens === 'sortie' ? 'text-red-600' : 'text-teal-700'">{{ row.quantite }}</td>
              <td>{{ row.emplacement || '—' }}</td>
              <td>{{ row.document || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, yearStart, today, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'RapportStock',
  components: { FinDate },
  data() {
    return {
      meta: { articles: [], categories: [], emplacements: [] },
      report: null,
      filters: { search: '', location_id: '', article_id: '', categorie: '', situation: '', from: yearStart(), to: today() },
    };
  },
  mounted() {
    axios.get('/api/rapports/meta').then(({ data }) => { this.meta = data; });
    this.load();
  },
  methods: {
    dh, dateFr,
    async load() {
      const { data } = await axios.get('/api/rapports/stock', { params: this.filters });
      this.report = data;
    },
    exportFile(format) {
      const ext = format === 'xlsx' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
      downloadFile('/api/rapports/stock', { ...this.filters, format }, `rapport-stock.${ext}`);
    },
  },
};
</script>
