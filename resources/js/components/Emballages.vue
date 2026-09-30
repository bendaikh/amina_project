<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Emballages temporaires — Régime 52</h1>
        <p class="text-gray-600">Suivi DUM 52, retours de fûts et soldes douaniers — totaux par client</p>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Ouverts</p><p class="text-2xl font-bold">{{ countStatut('ouvert') }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Partiellement soldés</p><p class="text-2xl font-bold text-amber-600">{{ countStatut('partiellement_solde') }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">En dépassement</p><p class="text-2xl font-bold text-red-600">{{ countStatut('en_depassement') }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Soldés</p><p class="text-2xl font-bold text-green-600">{{ countStatut('solde') }}</p></div>
      </div>

      <div v-if="parClient.length" class="bg-white rounded-xl border shadow-sm p-4 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Suivi emballages par client</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b text-left text-gray-600">
                <th class="py-2 px-3">Client</th>
                <th class="py-2 px-3">Dossiers</th>
                <th class="py-2 px-3">Total expédiés</th>
                <th class="py-2 px-3">Retournés</th>
                <th class="py-2 px-3">Restant à retourner</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in parClient" :key="row.client_id" class="border-b hover:bg-gray-50 cursor-pointer"
                @click="filterClient(row.client_id)">
                <td class="py-2 px-3 font-medium">{{ row.client?.nom || '—' }}</td>
                <td class="py-2 px-3">{{ row.dossiers }}</td>
                <td class="py-2 px-3">{{ row.total_expedie }}</td>
                <td class="py-2 px-3">{{ row.total_retourne }}</td>
                <td class="py-2 px-3 font-bold" :class="row.total_restant > 0 ? 'text-amber-700' : 'text-green-700'">
                  {{ row.total_restant }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded-xl border shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input v-model="search" @input="debouncedLoad" placeholder="DUM, client, conteneur, référence fût…"
          class="flex-1 border rounded-lg px-4 py-2" />
        <select v-model="clientId" @change="load" class="border rounded-lg px-3 py-2">
          <option value="">Tous les clients</option>
          <option v-for="row in parClient" :key="'c'+row.client_id" :value="row.client_id">{{ row.client?.nom }}</option>
        </select>
        <select v-model="statut" @change="load" class="border rounded-lg px-3 py-2">
          <option value="">Tous statuts</option>
          <option value="ouvert">Ouvert</option>
          <option value="partiellement_solde">Partiellement soldé</option>
          <option value="solde">Soldé</option>
          <option value="en_depassement">En dépassement</option>
          <option value="anomalie">Anomalie</option>
        </select>
        <label class="flex items-center gap-2 text-sm whitespace-nowrap">
          <input type="checkbox" v-model="alertesOnly" @change="load" /> Alertes ≤ 90 j
        </label>
      </div>

      <div class="bg-white rounded-xl border shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b text-left text-gray-600">
              <th class="py-3 px-4">DUM 52</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Export</th>
              <th class="py-3 px-4">Exportés</th>
              <th class="py-3 px-4">Retournés</th>
              <th class="py-3 px-4">Solde</th>
              <th class="py-3 px-4">Échéance</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-8 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-8 text-center text-gray-500">Aucun dossier régime 52</td></tr>
            <tr v-for="d in items" :key="d.id" class="border-b hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold">{{ d.dum_52 || '—' }}</td>
              <td class="py-3 px-4">{{ d.client?.nom || '—' }}</td>
              <td class="py-3 px-4">
                <router-link v-if="d.exportation" :to="`/exportations/${d.exportation.id}`" class="text-teal-600 hover:underline">
                  {{ d.exportation.numero }}
                </router-link>
                <span v-else>—</span>
              </td>
              <td class="py-3 px-4">{{ d.quantite_exportee }}</td>
              <td class="py-3 px-4">{{ d.quantite_reimporte }}</td>
              <td class="py-3 px-4 font-bold">{{ d.quantite_restante }}</td>
              <td class="py-3 px-4">
                <span :class="echeanceClass(d)">{{ formatDate(d.date_limite_reimportation) }}</span>
                <span v-if="d.alerte" class="ml-1 text-xs text-red-600">({{ d.alerte }})</span>
              </td>
              <td class="py-3 px-4"><span class="px-2 py-1 rounded text-xs bg-gray-100">{{ d.statut }}</span></td>
              <td class="py-3 px-4 text-right">
                <button @click="openDetail(d)" class="text-teal-600 font-medium">Retour</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="selected" class="app-modal-overlay" @click.self="selected=null">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">Enregistrer un retour</h2>
              <p class="text-sm text-gray-600">{{ selected.dum_52 }} · Solde {{ selected.quantite_restante }}</p>
            </div>
            <button type="button" class="app-modal__close" @click="selected=null" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="space-y-3">
              <div>
                <label class="text-xs text-gray-500">Date réimportation</label>
                <input type="date" v-model="retour.date_reimportation" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">DUM de réimportation</label>
                <input v-model="retour.dum_reimportation" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Quantité</label>
                <input type="number" step="0.001" v-model.number="retour.quantite" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Type emballage</label>
                <input v-model="retour.type_emballage" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Observations</label>
                <textarea v-model="retour.observations" class="w-full border rounded-lg px-3 py-2" rows="2"></textarea>
              </div>
            </div>
            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>

            <div v-if="selected.retours?.length" class="mt-6 border-t pt-4">
              <p class="text-sm font-medium mb-2">Retours existants</p>
              <ul class="text-sm space-y-1">
                <li v-for="r in selected.retours" :key="r.id">
                  {{ formatDate(r.date_reimportation) }} — {{ r.quantite }} ({{ r.dum_reimportation || 'sans DUM' }})
                </li>
              </ul>
            </div>
          </div>
          <div class="app-modal__footer">
            <button @click="selected=null" class="px-4 py-2 border rounded-lg">Annuler</button>
            <button @click="saveRetour" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg">Enregistrer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Emballages',
  data() {
    return {
      items: [],
      parClient: [],
      loading: false,
      saving: false,
      search: '',
      statut: '',
      clientId: '',
      alertesOnly: false,
      selected: null,
      error: '',
      timer: null,
      retour: { date_reimportation: '', dum_reimportation: '', quantite: 0, type_emballage: '', observations: '' },
    };
  },
  mounted() { this.load(); this.loadParClient(); },
  methods: {
    countStatut(s) {
      return this.items.filter(i => i.statut === s).length;
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).substring(0, 10);
    },
    echeanceClass(d) {
      if (d.alerte === 'depassee' || d.statut === 'en_depassement') return 'text-red-600 font-semibold';
      if (d.alerte === '7_jours' || d.alerte === '30_jours') return 'text-amber-600 font-semibold';
      return '';
    },
    filterClient(id) {
      this.clientId = id || '';
      this.load();
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async loadParClient() {
      try {
        const { data } = await axios.get('/api/emballages/par-client');
        this.parClient = data || [];
      } catch (e) {
        this.parClient = [];
      }
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/emballages', {
          params: {
            search: this.search,
            statut: this.statut,
            client_id: this.clientId || undefined,
            alertes_only: this.alertesOnly ? 1 : 0,
            per_page: 100,
          },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async openDetail(d) {
      const { data } = await axios.get(`/api/emballages/${d.id}`);
      this.selected = data;
      this.retour = {
        date_reimportation: new Date().toISOString().substring(0, 10),
        dum_reimportation: '',
        quantite: data.quantite_restante || 0,
        type_emballage: data.type_emballage || data.type_fut || '',
        observations: '',
      };
      this.error = '';
    },
    async saveRetour() {
      this.saving = true;
      this.error = '';
      try {
        await axios.post(`/api/emballages/${this.selected.id}/retours`, this.retour);
        this.selected = null;
        await this.load();
        await this.loadParClient();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
