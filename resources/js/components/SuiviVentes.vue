<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-[1600px] mx-auto">
      <div class="app-page-header mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Suivi des ventes</h1>
          <p class="text-gray-600 text-sm mt-1">
            Tableau de suivi : quantités, facturation, règlement, réclamations, notes de crédit et documents manquants
          </p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 app-toolbar">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher commande, client, commercial…"
          class="app-toolbar__search flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="type" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous types</option>
          <option value="local">Local</option>
          <option value="export">Export</option>
        </select>
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous états</option>
          <option v-for="s in (meta.statuts || [])" :key="s" :value="s">{{ labelStatut(s) }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full min-w-[1200px]">
          <thead>
            <tr class="border-b border-gray-200 text-left text-xs text-gray-600 uppercase tracking-wide">
              <th class="py-3 px-3">Commande</th>
              <th class="py-3 px-3">Date</th>
              <th class="py-3 px-3">Client</th>
              <th class="py-3 px-3">Type</th>
              <th class="py-3 px-3">Commercial</th>
              <th class="py-3 px-3">Montant</th>
              <th class="py-3 px-2 text-center" colspan="6">Quantités</th>
              <th class="py-3 px-3">Facturation</th>
              <th class="py-3 px-3">Règlement</th>
              <th class="py-3 px-3">Réclamation</th>
              <th class="py-3 px-3">Note de crédit</th>
              <th class="py-3 px-3">État</th>
              <th class="py-3 px-3">Docs manquants</th>
            </tr>
            <tr class="border-b border-gray-100 text-left text-[11px] text-gray-500 bg-gray-50">
              <th class="py-2 px-3" colspan="6"></th>
              <th class="py-2 px-2 text-center font-medium">Cmd</th>
              <th class="py-2 px-2 text-center font-medium">Prod</th>
              <th class="py-2 px-2 text-center font-medium">Prép</th>
              <th class="py-2 px-2 text-center font-medium">Liv</th>
              <th class="py-2 px-2 text-center font-medium">Exp</th>
              <th class="py-2 px-2 text-center font-medium">Rest</th>
              <th class="py-2 px-3" colspan="6"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="18" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="18" class="py-10 text-center text-gray-500">Aucune commande à suivre</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50 align-top">
              <td class="py-3 px-3 font-semibold text-teal-700 whitespace-nowrap">{{ item.numero }}</td>
              <td class="py-3 px-3 whitespace-nowrap">{{ formatDate(item.date_commande) }}</td>
              <td class="py-3 px-3">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded text-xs font-medium" :class="item.type === 'export' ? 'bg-indigo-50 text-indigo-700' : 'bg-teal-50 text-teal-700'">
                  {{ item.type === 'export' ? 'Export' : 'Local' }}
                </span>
              </td>
              <td class="py-3 px-3">{{ item.commercial || '—' }}</td>
              <td class="py-3 px-3 whitespace-nowrap font-medium">{{ formatMoney(item.montant) }} {{ item.devise }}</td>
              <td class="py-3 px-2 text-center text-sm">{{ formatQty(item.quantites.commandee) }}</td>
              <td class="py-3 px-2 text-center text-sm">{{ formatQty(item.quantites.produite) }}</td>
              <td class="py-3 px-2 text-center text-sm">{{ formatQty(item.quantites.preparee) }}</td>
              <td class="py-3 px-2 text-center text-sm">{{ formatQty(item.quantites.livree) }}</td>
              <td class="py-3 px-2 text-center text-sm">{{ formatQty(item.quantites.expediee) }}</td>
              <td class="py-3 px-2 text-center text-sm font-medium" :class="item.quantites.restante > 0 ? 'text-amber-700' : 'text-green-700'">
                {{ formatQty(item.quantites.restante) }}
              </td>
              <td class="py-3 px-3">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="facturationClass(item.facturation.code)">{{ item.facturation.label }}</span>
              </td>
              <td class="py-3 px-3">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="reglementClass(item.reglement.code)">{{ item.reglement.label }}</span>
              </td>
              <td class="py-3 px-3 text-sm" :class="item.reclamation.ouverts ? 'text-red-700 font-medium' : 'text-gray-600'">
                {{ item.reclamation.label }}
              </td>
              <td class="py-3 px-3 text-sm text-gray-700">{{ item.note_credit.label }}</td>
              <td class="py-3 px-3">
                <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">{{ labelEtat(item) }}</span>
              </td>
              <td class="py-3 px-3">
                <template v-if="item.docs_manquants?.length">
                  <span
                    v-for="d in item.docs_manquants"
                    :key="d"
                    class="inline-block mr-1 mb-1 px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 border border-amber-100"
                  >{{ d }}</span>
                </template>
                <span v-else class="text-xs text-green-700">Complet</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="type === 'export' || !type" class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Circuit export</h3>
        <div class="flex flex-wrap gap-2 text-xs">
          <span
            v-for="(label, key) in (meta.etats_export || {})"
            :key="key"
            class="px-2.5 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-700"
          >{{ label }}</span>
        </div>
        <p class="text-xs text-gray-500 mt-2">Brouillon → Confirmée → Préparation → Production → Prêt au chargement → Chargé → Expédié → Arrivé → Clôturé</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SuiviVentes',
  data() {
    return {
      items: [],
      meta: {},
      loading: false,
      search: '',
      type: '',
      statut: '',
      timer: null,
    };
  },
  mounted() {
    this.loadMeta();
    this.load();
  },
  methods: {
    labelStatut(s) {
      return this.meta.labels?.statuts?.[s] || s;
    },
    labelEtat(item) {
      if (item.type === 'export' && this.meta.etats_export?.[item.etat_export]) {
        return this.meta.etats_export[item.etat_export];
      }
      return this.labelStatut(item.statut);
    },
    facturationClass(code) {
      return {
        non: 'bg-red-50 text-red-700',
        partielle: 'bg-amber-50 text-amber-800',
        complete: 'bg-green-50 text-green-700',
      }[code] || 'bg-gray-100 text-gray-700';
    },
    reglementClass(code) {
      return {
        na: 'bg-gray-100 text-gray-500',
        impaye: 'bg-red-50 text-red-700',
        partiel: 'bg-amber-50 text-amber-800',
        solde: 'bg-green-50 text-green-700',
      }[code] || 'bg-gray-100 text-gray-700';
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatQty(v) {
      const n = Number(v || 0);
      if (!n) return '0';
      return n.toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async loadMeta() {
      try {
        const { data } = await axios.get('/api/suivi-ventes/meta');
        this.meta = data;
      } catch (_) {
        this.meta = {};
      }
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/suivi-ventes', {
          params: { search: this.search, type: this.type, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
      } catch (_) {
        this.items = [];
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
