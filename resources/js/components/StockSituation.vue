<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Situation stock</h1>
        <p class="text-gray-600 text-sm mt-1">
          Stock disponible = Stock théorique − Stock réservé
        </p>
      </div>

      <!-- Resume cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div v-for="card in resumeCards" :key="card.key" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ card.label }}</p>
          <p class="text-xl font-bold mt-1" :class="card.accent || 'text-gray-900'">{{ formatQty(resume[card.key]) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher article…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="locationId" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les emplacements</option>
          <option v-for="e in emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 text-left text-gray-600">
              <th class="py-3 px-3">Article</th>
              <th class="py-3 px-3">Emplacement</th>
              <th class="py-3 px-3">Lot</th>
              <th class="py-3 px-3 text-right">Initial</th>
              <th class="py-3 px-3 text-right">Entrées</th>
              <th class="py-3 px-3 text-right">Sorties</th>
              <th class="py-3 px-3 text-right">Théorique</th>
              <th class="py-3 px-3 text-right">Réservé</th>
              <th class="py-3 px-3 text-right">Disponible</th>
              <th class="py-3 px-3 text-right">Quarantaine</th>
              <th class="py-3 px-3 text-right">Endommagé</th>
              <th class="py-3 px-3 text-right">Transit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="12" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="12" class="py-10 text-center text-gray-500">Aucun stock</td></tr>
            <tr v-for="row in items" :key="row.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-3">
                <div class="font-medium text-teal-800">{{ row.article?.code_article }}</div>
                <div class="text-xs text-gray-500">{{ row.article?.designation }}</div>
              </td>
              <td class="py-3 px-3">{{ row.location?.nom || '—' }}</td>
              <td class="py-3 px-3">{{ row.lot || '—' }}</td>
              <td class="py-3 px-3 text-right">{{ formatQty(row.stock_initial) }}</td>
              <td class="py-3 px-3 text-right text-emerald-700">{{ formatQty(row.entrees) }}</td>
              <td class="py-3 px-3 text-right text-amber-700">{{ formatQty(row.sorties) }}</td>
              <td class="py-3 px-3 text-right font-medium">{{ formatQty(row.stock_theorique) }}</td>
              <td class="py-3 px-3 text-right">{{ formatQty(row.stock_reserve) }}</td>
              <td class="py-3 px-3 text-right font-bold text-teal-700">{{ formatQty(row.stock_disponible) }}</td>
              <td class="py-3 px-3 text-right">{{ formatQty(row.quarantaine) }}</td>
              <td class="py-3 px-3 text-right">{{ formatQty(row.endommage) }}</td>
              <td class="py-3 px-3 text-right">{{ formatQty(row.transit) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockSituation',
  data() {
    return {
      items: [],
      loading: false,
      search: '',
      locationId: '',
      emplacements: [],
      resume: {},
      debounceTimer: null,
      resumeCards: [
        { key: 'stock_initial', label: 'Initial' },
        { key: 'entrees', label: 'Entrées', accent: 'text-emerald-700' },
        { key: 'sorties', label: 'Sorties', accent: 'text-amber-700' },
        { key: 'stock_theorique', label: 'Théorique' },
        { key: 'stock_disponible', label: 'Disponible', accent: 'text-teal-700' },
        { key: 'stock_reserve', label: 'Réservé' },
        { key: 'quarantaine', label: 'Quarantaine' },
        { key: 'endommage', label: 'Endommagé' },
        { key: 'transit', label: 'Transit' },
      ],
    };
  },
  mounted() {
    this.loadMeta();
    this.loadResume();
    this.load();
  },
  methods: {
    async loadMeta() {
      const { data } = await axios.get('/api/stock/situation/meta');
      this.emplacements = data.emplacements || [];
    },
    async loadResume() {
      const { data } = await axios.get('/api/stock/situation/resume');
      this.resume = data;
    },
    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.load(), 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/stock/situation', {
          params: {
            search: this.search,
            stock_location_id: this.locationId || undefined,
          },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    formatQty(q) {
      const n = Number(q || 0);
      return n.toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
  },
};
</script>
