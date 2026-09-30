<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Inventaire</h1>
          <p class="text-gray-600 text-sm mt-1">
            Création → sélection → saisie physique → écarts → validation → historique
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvel inventaire
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="N° inventaire, libellé…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous</option>
          <option value="en_cours">En cours</option>
          <option value="valide">Validé</option>
          <option value="brouillon">Brouillon</option>
          <option value="annule">Annulé</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto mb-6">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N°</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Libellé</th>
              <th class="py-3 px-4">Emplacement</th>
              <th class="py-3 px-4">Lignes</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="7" class="py-10 text-center text-gray-500">Aucun inventaire</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_inventaire) }}</td>
              <td class="py-3 px-4">{{ item.libelle || '—' }}</td>
              <td class="py-3 px-4">{{ item.location?.nom || 'Tous' }}</td>
              <td class="py-3 px-4">{{ item.lignes?.length || 0 }}</td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4 text-right">
                <button @click="openDetail(item)" class="text-teal-600 hover:underline font-medium">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create -->
      <div v-if="showCreate" class="app-modal-overlay" @click.self="showCreate = false">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">Nouvel inventaire</h2>
              <p class="app-modal__subtitle">Créer une session de comptage et saisir les quantités physiques</p>
            </div>
            <button type="button" class="app-modal__close" @click="showCreate = false" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="app-form-grid app-form-grid--2">
              <div class="app-span-full">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Libellé</label>
                <input
                  v-model="createForm.libelle"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Inventaire mensuel…"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                <input
                  v-model="createForm.date_inventaire"
                  type="date"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Emplacement</label>
                <select
                  v-model="createForm.stock_location_id"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                >
                  <option :value="null">Tous les emplacements</option>
                  <option v-for="e in emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
              </div>
              <div class="app-span-full">
                <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                  <input type="checkbox" v-model="createForm.inclure_tous" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                  <span>
                    <span class="block text-sm font-medium text-gray-800">Inclure tous les articles en stock</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Préremplit les lignes avec le stock théorique actuel</span>
                  </span>
                </label>
              </div>
            </div>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="showCreate = false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50">Annuler</button>
            <button
              type="button"
              @click="create"
              :disabled="saving"
              class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium"
            >{{ saving ? 'Création…' : 'Créer' }}</button>
          </div>
        </div>
      </div>

      <!-- Detail / saisie -->
      <div v-if="showDetail && current" class="app-modal-overlay" @click.self="showDetail = false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">{{ current.numero }} — {{ current.libelle || 'Inventaire' }}</h2>
              <p class="app-modal__subtitle">
                {{ formatDate(current.date_inventaire) }} · {{ current.location?.nom || 'Tous emplacements' }} ·
                <span :class="badgeClass(current.statut)" class="px-2 py-0.5 rounded text-xs font-medium">{{ labelStatut(current.statut) }}</span>
              </p>
            </div>
            <button type="button" class="app-modal__close" @click="showDetail = false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <div v-if="!(current.lignes || []).length" class="py-10 text-center text-gray-500 text-sm">
              Aucune ligne d’inventaire
            </div>
            <div v-else class="app-table-scroll app-table-scroll--wide">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                  <tr>
                    <th class="py-2.5 px-3 text-left font-medium">Article</th>
                    <th class="py-2.5 px-3 text-left font-medium">Emplacement</th>
                    <th class="py-2.5 px-3 text-left font-medium">Lot</th>
                    <th class="py-2.5 px-3 text-right font-medium">Théorique</th>
                    <th class="py-2.5 px-3 text-right font-medium">Physique</th>
                    <th class="py-2.5 px-3 text-right font-medium">Écart</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="l in current.lignes" :key="l.id" class="border-t border-gray-100 hover:bg-gray-50/60">
                    <td class="py-2.5 px-3">
                      <div class="font-medium text-gray-900">{{ l.article?.code_article }}</div>
                      <div class="text-xs text-gray-500">{{ l.article?.designation }}</div>
                    </td>
                    <td class="py-2.5 px-3">{{ l.location?.nom || '—' }}</td>
                    <td class="py-2.5 px-3">{{ l.lot || '—' }}</td>
                    <td class="py-2.5 px-3 text-right tabular-nums">{{ formatQty(l.quantite_theorique) }}</td>
                    <td class="py-2.5 px-3 text-right">
                      <input
                        v-if="current.statut !== 'valide'"
                        v-model.number="l.quantite_physique"
                        type="number"
                        step="0.001"
                        class="w-28 border border-gray-300 rounded-lg px-2.5 py-1.5 text-right focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        @change="recalcEcart(l)"
                      />
                      <span v-else class="tabular-nums">{{ formatQty(l.quantite_physique) }}</span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-medium tabular-nums" :class="ecartClass(l)">
                      {{ formatEcart(l) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="app-modal__footer">
            <button type="button" @click="showDetail = false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50">Fermer</button>
            <button
              v-if="current.statut !== 'valide'"
              type="button"
              @click="saveLignes"
              :disabled="saving"
              class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium"
            >{{ saving ? 'Enregistrement…' : 'Enregistrer saisie' }}</button>
            <button
              v-if="current.statut !== 'valide'"
              type="button"
              @click="valider"
              :disabled="saving"
              class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 font-medium"
            >Valider écarts → stock</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StockInventaire',
  data() {
    return {
      items: [],
      loading: false,
      saving: false,
      search: '',
      statut: '',
      emplacements: [],
      showCreate: false,
      showDetail: false,
      current: null,
      createForm: {
        libelle: '',
        date_inventaire: new Date().toISOString().slice(0, 10),
        stock_location_id: null,
        inclure_tous: true,
      },
      debounceTimer: null,
    };
  },
  mounted() {
    this.loadMeta();
    this.load();
  },
  methods: {
    async loadMeta() {
      const { data } = await axios.get('/api/stock/inventaires/meta');
      this.emplacements = data.emplacements || [];
    },
    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.load(), 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/stock/inventaires', {
          params: { search: this.search, statut: this.statut },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    openCreate() {
      this.createForm = {
        libelle: '',
        date_inventaire: new Date().toISOString().slice(0, 10),
        stock_location_id: null,
        inclure_tous: true,
      };
      this.showCreate = true;
    },
    async create() {
      this.saving = true;
      try {
        const { data } = await axios.post('/api/stock/inventaires', this.createForm);
        this.showCreate = false;
        await this.load();
        this.current = data;
        this.showDetail = true;
      } catch (e) {
        alert(e.response?.data?.message || 'Erreur');
      } finally {
        this.saving = false;
      }
    },
    async openDetail(item) {
      const { data } = await axios.get(`/api/stock/inventaires/${item.id}`);
      this.current = data;
      this.showDetail = true;
    },
    recalcEcart(l) {
      if (l.quantite_physique === null || l.quantite_physique === '') {
        l.ecart = 0;
        return;
      }
      l.ecart = Number(l.quantite_physique) - Number(l.quantite_theorique);
    },
    async saveLignes() {
      this.saving = true;
      try {
        const { data } = await axios.put(`/api/stock/inventaires/${this.current.id}`, {
          lignes: (this.current.lignes || []).map(l => ({
            id: l.id,
            quantite_physique: l.quantite_physique,
            observations: l.observations,
          })),
        });
        this.current = data;
        await this.load();
      } catch (e) {
        alert(e.response?.data?.message || 'Erreur');
      } finally {
        this.saving = false;
      }
    },
    async valider() {
      if (!confirm('Valider l\'inventaire et appliquer les écarts au stock ?')) return;
      await this.saveLignes();
      this.saving = true;
      try {
        const { data } = await axios.post(`/api/stock/inventaires/${this.current.id}/valider`);
        this.current = data;
        await this.load();
        alert('Inventaire validé — mouvements d\'écart générés');
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
      if (q === null || q === undefined || q === '') return '—';
      return Number(q).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    formatEcart(l) {
      if (l.quantite_physique === null || l.quantite_physique === undefined || l.quantite_physique === '') return '—';
      const e = Number(l.quantite_physique) - Number(l.quantite_theorique);
      return (e > 0 ? '+' : '') + e.toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    ecartClass(l) {
      if (l.quantite_physique === null || l.quantite_physique === undefined || l.quantite_physique === '') return 'text-gray-400';
      const e = Number(l.quantite_physique) - Number(l.quantite_theorique);
      if (e > 0) return 'text-emerald-700';
      if (e < 0) return 'text-red-600';
      return 'text-gray-600';
    },
    labelStatut(s) {
      return { brouillon: 'Brouillon', en_cours: 'En cours', valide: 'Validé', annule: 'Annulé' }[s] || s;
    },
    badgeClass(s) {
      return {
        brouillon: 'bg-gray-100 text-gray-700',
        en_cours: 'bg-amber-50 text-amber-700',
        valide: 'bg-emerald-50 text-emerald-700',
        annule: 'bg-red-50 text-red-700',
      }[s] || 'bg-gray-50';
    },
  },
};
</script>
