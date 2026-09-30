<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Suivi réglementaire</h1>
          <p class="text-gray-600 text-sm mt-1">
            Factures, TVA, justificatifs, docs fiscaux, import, transport, certificats, échéances, pièces jointes
          </p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher facture, fournisseur…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <label class="flex items-center gap-2 text-sm border rounded-lg px-3 py-2">
          <input type="checkbox" v-model="incomplets" @change="load" />
          Incomplets seulement
        </label>
      </div>

      <div class="space-y-4">
        <div v-if="loading" class="bg-white rounded-xl border p-10 text-center text-gray-500">Chargement…</div>
        <div v-else-if="!items.length" class="bg-white rounded-xl border p-10 text-center text-gray-500">Aucun dossier</div>

        <div v-for="item in items" :key="item.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
            <div>
              <h3 class="font-semibold text-lg text-teal-700">{{ item.numero }}</h3>
              <p class="text-sm text-gray-600">
                {{ item.fournisseur?.nom || '—' }} · Échéance {{ formatDate(item.echeance) }} · {{ formatMoney(item.total_ttc) }} {{ item.devise }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-xs text-gray-500">Progression</p>
                <p class="font-bold" :class="item.suivi_complet ? 'text-green-600' : 'text-amber-600'">{{ item.suivi_progress }}%</p>
              </div>
              <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-teal-500 rounded-full transition-all" :style="{ width: item.suivi_progress + '%' }"></div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 mb-4">
            <label
              v-for="flag in flags"
              :key="flag.key"
              class="flex items-center gap-2 text-sm border rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50"
              :class="item[flag.key] ? 'border-teal-300 bg-teal-50' : 'border-gray-200'"
            >
              <input
                type="checkbox"
                :checked="item[flag.key]"
                @change="toggleFlag(item, flag.key, $event.target.checked)"
              />
              <span>{{ flag.label }}</span>
            </label>
          </div>

          <div class="flex flex-wrap items-center gap-3 border-t pt-3">
            <label class="text-sm text-teal-600 cursor-pointer">
              + Pièce jointe
              <input type="file" class="hidden" @change="uploadPiece(item, $event)" />
            </label>
            <span v-for="p in item.pieces_jointes || []" :key="p.id" class="text-xs bg-gray-100 rounded px-2 py-1">
              {{ p.nom_fichier }}
            </span>
            <span v-if="!(item.pieces_jointes || []).length" class="text-xs text-gray-400">Aucune pièce jointe</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const FLAGS = [
  { key: 'suivi_facture', label: 'Facture' },
  { key: 'suivi_tva', label: 'TVA' },
  { key: 'suivi_justificatifs', label: 'Justificatifs' },
  { key: 'suivi_docs_fiscaux', label: 'Docs fiscaux' },
  { key: 'suivi_import', label: 'Import' },
  { key: 'suivi_transport', label: 'Transport' },
  { key: 'suivi_certificats', label: 'Certificats' },
  { key: 'suivi_echeance', label: 'Échéance' },
  { key: 'suivi_pieces_jointes', label: 'Pièces jointes' },
];

export default {
  name: 'SuiviReglementaire',
  data() {
    return {
      items: [],
      loading: false,
      search: '',
      incomplets: false,
      timer: null,
      flags: FLAGS,
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/factures-fournisseurs/suivi-reglementaire', {
          params: { search: this.search, incomplets: this.incomplets ? 1 : 0 },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async toggleFlag(item, key, value) {
      item[key] = value;
      try {
        const { data } = await axios.post(`/api/factures-fournisseurs/${item.id}/suivi`, { [key]: value });
        Object.assign(item, {
          suivi_complet: data.suivi_complet,
          suivi_progress: data.suivi_progress,
        });
      } catch (_) {
        item[key] = !value;
      }
    },
    async uploadPiece(item, e) {
      const file = e.target.files?.[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('fichier', file);
      try {
        await axios.post(`/api/factures-fournisseurs/${item.id}/pieces`, fd);
        await this.load();
      } catch (_) { /* ignore */ }
      e.target.value = '';
    },
  },
};
</script>
