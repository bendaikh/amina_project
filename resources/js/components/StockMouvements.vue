<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Mouvements de stock</h1>
          <p class="text-gray-600 text-sm mt-1">
            Entrées, sorties, transferts, ajustements, réservations — traçabilité complète
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouveau mouvement
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3 flex-wrap">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Article, lot, document…"
          class="flex-1 min-w-[180px] border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="type" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les types</option>
          <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
        </select>
        <input v-model="dateFrom" type="date" @change="load" class="border border-gray-300 rounded-lg px-3 py-2" />
        <input v-model="dateTo" type="date" @change="load" class="border border-gray-300 rounded-lg px-3 py-2" />
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Type</th>
              <th class="py-3 px-4">Article</th>
              <th class="py-3 px-4">Qté</th>
              <th class="py-3 px-4">Lot</th>
              <th class="py-3 px-4">Emplacement</th>
              <th class="py-3 px-4">Document</th>
              <th class="py-3 px-4">Utilisateur</th>
              <th class="py-3 px-4">Commentaire</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucun mouvement</td></tr>
            <tr v-for="m in items" :key="m.id" class="border-b border-gray-50 hover:bg-gray-50 text-sm">
              <td class="py-3 px-4">{{ formatDate(m.date_mouvement) }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-800">{{ m.type_label || typeLabels[m.type] || m.type }}</span>
              </td>
              <td class="py-3 px-4">
                <div class="font-medium">{{ m.article?.code_article }}</div>
                <div class="text-xs text-gray-500">{{ m.article?.designation }}</div>
              </td>
              <td class="py-3 px-4 font-semibold">{{ formatQty(m.quantite) }} {{ m.unite || '' }}</td>
              <td class="py-3 px-4">{{ m.lot || '—' }}</td>
              <td class="py-3 px-4">
                <template v-if="m.type === 'transfert'">
                  {{ m.from_location?.nom || '?' }} → {{ m.to_location?.nom || '?' }}
                </template>
                <template v-else>{{ m.location?.nom || '—' }}</template>
              </td>
              <td class="py-3 px-4">{{ m.document_ref || '—' }}</td>
              <td class="py-3 px-4">{{ m.user?.name || '—' }}</td>
              <td class="py-3 px-4 text-gray-500 max-w-[180px] truncate">{{ cleanComment(m.commentaire) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">Nouveau mouvement</h2>
              <p class="app-modal__subtitle">Entrée, sortie, transfert, ajustement ou réservation</p>
            </div>
            <button type="button" class="app-modal__close" @click="showModal = false" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="app-form-grid app-form-grid--2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                  <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                <input v-model="form.date_mouvement" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
              </div>
              <div class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Article</label>
                <select v-model="form.article_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                  <option :value="null">Sélectionner un article…</option>
                  <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }} — {{ a.designation }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Quantité</label>
                <input v-model.number="form.quantite" type="number" step="0.001" min="0.001" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Unité</label>
                <input v-model="form.unite" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
              </div>
              <div v-if="form.type === 'ajustement'" class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sens</label>
                <select v-model="form.signe" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                  <option value="+">Entrée (+)</option>
                  <option value="-">Sortie (−)</option>
                </select>
              </div>
              <div class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Lot</label>
                <input v-model="form.lot" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" placeholder="Optionnel" />
              </div>
              <template v-if="form.type === 'transfert'">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">De</label>
                  <select v-model="form.from_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option v-for="e in emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Vers</label>
                  <select v-model="form.to_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option v-for="e in emplacements" :key="'t'+e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                </div>
              </template>
              <div v-else class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Emplacement</label>
                <select v-model="form.stock_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="e in emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
              </div>
              <div class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Réf. document</label>
                <input v-model="form.document_ref" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" placeholder="Optionnel" />
              </div>
              <div class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Commentaire</label>
                <textarea v-model="form.commentaire" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent" placeholder="Notes…"></textarea>
              </div>
            </div>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50">Annuler</button>
            <button type="button" @click="save" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium">
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockMouvements',
  data() {
    return {
      items: [],
      loading: false,
      saving: false,
      search: '',
      type: '',
      dateFrom: '',
      dateTo: '',
      typeLabels: {},
      emplacements: [],
      articles: [],
      showModal: false,
      form: {},
      debounceTimer: null,
    };
  },
  mounted() {
    this.loadMeta();
    this.loadArticles();
    this.load();
  },
  methods: {
    async loadMeta() {
      const { data } = await axios.get('/api/stock/mouvements/meta');
      this.typeLabels = data.type_labels || {};
      this.emplacements = data.emplacements || [];
    },
    async loadArticles() {
      const { data } = await axios.get('/api/articles', { params: { per_page: 500 } });
      this.articles = Array.isArray(data) ? data : (data.data || []);
    },
    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.load(), 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/stock/mouvements', {
          params: {
            search: this.search,
            type: this.type,
            date_from: this.dateFrom || undefined,
            date_to: this.dateTo || undefined,
          },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    openCreate() {
      const depot = this.emplacements.find(e => e.code === 'depot')?.id || this.emplacements[0]?.id;
      this.form = {
        type: 'ajustement',
        date_mouvement: new Date().toISOString().slice(0, 10),
        article_id: null,
        quantite: 1,
        unite: 'pcs',
        lot: '',
        stock_location_id: depot,
        from_location_id: depot,
        to_location_id: this.emplacements.find(e => e.code === 'gros')?.id || depot,
        document_ref: '',
        commentaire: '',
        signe: '+',
      };
      this.showModal = true;
    },
    async save() {
      if (!this.form.article_id || !this.form.quantite) {
        alert('Article et quantité requis');
        return;
      }
      this.saving = true;
      try {
        await axios.post('/api/stock/mouvements', this.form);
        this.showModal = false;
        await this.load();
      } catch (e) {
        alert(e.response?.data?.message || 'Erreur');
      } finally {
        this.saving = false;
      }
    },
    formatDate(d) {
      return d ? String(d).slice(0, 10) : '—';
    },
    formatQty(q) {
      return Number(q).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    cleanComment(c) {
      if (!c) return '—';
      return String(c).replace(/^SIGNE:-\|/, '') || '—';
    },
  },
};
</script>
