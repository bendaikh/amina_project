<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Bons de réception</h1>
          <p class="text-gray-600 text-sm mt-1">
            Réception fournisseur → contrôle qualité → entrée en stock (articles stockés uniquement)
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouveau BR
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N° BR, fournisseur, réf. commande…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option value="brouillon">Brouillon</option>
          <option value="valide">Validé</option>
          <option value="annule">Annulé</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° BR</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Fournisseur</th>
              <th class="py-3 px-4">Réf. achat / commande</th>
              <th class="py-3 px-4">Lignes</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4">Stock</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="8" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="8" class="py-10 text-center text-gray-500">Aucun bon de réception</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_reception) }}</td>
              <td class="py-3 px-4">{{ item.fournisseur?.nom || '—' }}</td>
              <td class="py-3 px-4 text-sm">
                <span v-if="item.reference_achat">{{ item.reference_achat }}</span>
                <span v-if="item.reference_commande" class="text-gray-500"> / {{ item.reference_commande }}</span>
                <span v-if="!item.reference_achat && !item.reference_commande">—</span>
              </td>
              <td class="py-3 px-4">{{ item.lignes?.length || 0 }}</td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4">
                <span v-if="item.entree_stock_generee" class="text-xs text-emerald-600 font-medium">Entrée générée</span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="py-3 px-4 text-right">
                <button @click="openDetail(item)" class="text-teal-600 hover:underline font-medium">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create / Edit modal -->
      <div v-if="showModal" class="app-modal-overlay" @click.self="showModal = false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">{{ form.id ? form.numero : 'Nouveau bon de réception' }}</h2>
              <p class="app-modal__subtitle">Date, fournisseur, quantités, lot, emplacement et contrôle qualité</p>
            </div>
            <button type="button" class="app-modal__close" @click="showModal = false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Informations générales</p>
              <div class="app-form-grid app-form-grid--3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                  <input v-model="form.date_reception" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Fournisseur</label>
                  <select v-model="form.fournisseur_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked">
                    <option :value="null">Sélectionner…</option>
                    <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Achat lié (optionnel)</label>
                  <select v-model="form.achat_id" @change="onAchatChange" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked">
                    <option :value="null">Aucun</option>
                    <option v-for="a in achats" :key="a.id" :value="a.id">{{ a.numero }} — {{ a.fournisseur?.nom }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Réf. commande</label>
                  <input v-model="form.reference_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Réf. facture</label>
                  <input v-model="form.reference_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Réf. achat</label>
                  <input v-model="form.reference_achat" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                </div>
                <div class="app-span-full">
                  <label class="block text-sm font-medium text-gray-700 mb-1.5">Observations</label>
                  <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked"></textarea>
                </div>
              </div>
            </div>

            <div class="mb-3 flex items-center justify-between gap-3">
              <div>
                <h3 class="font-semibold text-gray-800">Lignes de réception</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ form.lignes.length }} ligne(s)</p>
              </div>
              <button
                v-if="!isLocked"
                type="button"
                @click="addLigne"
                class="px-3 py-1.5 text-sm font-medium text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100"
              >+ Ajouter une ligne</button>
            </div>

            <div class="mb-5 space-y-3">
              <div v-for="(l, idx) in form.lignes" :key="idx" class="app-line-card">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ligne {{ idx + 1 }}</span>
                  <button
                    v-if="!isLocked && form.lignes.length > 1"
                    type="button"
                    @click="form.lignes.splice(idx, 1)"
                    class="text-red-600 text-sm font-medium hover:underline"
                  >Supprimer</button>
                </div>
                <div class="app-form-grid app-form-grid--3">
                  <div class="app-span-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Article</label>
                    <select v-model="l.article_id" @change="onArticleSelect(l)" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked">
                      <option :value="null">Sélectionner un article…</option>
                      <option v-for="art in articles" :key="art.id" :value="art.id">{{ art.code_article }} — {{ art.designation }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Qté commandée</label>
                    <input v-model.number="l.quantite_commandee" type="number" step="0.001" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Qté reçue</label>
                    <input v-model.number="l.quantite_recue" type="number" step="0.001" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Unité</label>
                    <input v-model="l.unite" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Lot</label>
                    <input v-model="l.lot" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date production</label>
                    <input v-model="l.date_production" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date péremption</label>
                    <input v-model="l.date_peremption" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Emplacement</label>
                    <select v-model="l.stock_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked">
                      <option :value="null">Sélectionner…</option>
                      <option v-for="e in emplacements" :key="e.id" :value="e.id">{{ e.nom }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contrôle qualité</label>
                    <select v-model="l.controle_qualite" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100" :disabled="isLocked">
                      <option value="ok">OK</option>
                      <option value="quarantaine">Quarantaine</option>
                      <option value="endommage">Endommagé</option>
                      <option value="refuse">Refusé</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="form.id" class="p-4 bg-gray-50 rounded-lg border border-gray-100">
              <h3 class="text-sm font-semibold text-gray-700 mb-2">Documents associés</h3>
              <ul v-if="pieces.length" class="text-sm space-y-1.5 mb-3">
                <li v-for="p in pieces" :key="p.id" class="text-teal-700 font-medium">{{ p.nom_fichier }}</li>
              </ul>
              <p v-else class="text-sm text-gray-500 mb-3">Aucun document</p>
              <div v-if="!isLocked" class="flex flex-wrap items-center gap-3">
                <input type="file" ref="fileInput" @change="uploadPiece" class="text-sm text-gray-600" />
                <span class="text-xs text-gray-500">Joindre un document</span>
              </div>
            </div>
          </div>

          <div class="app-modal__footer">
            <button type="button" @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50">
              {{ isLocked ? 'Fermer' : 'Annuler' }}
            </button>
            <button
              v-if="!isLocked"
              type="button"
              @click="save"
              :disabled="saving"
              class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 font-medium"
            >
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
            <button
              v-if="form.id && form.statut === 'brouillon'"
              type="button"
              @click="valider"
              :disabled="saving"
              class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 font-medium"
            >
              Valider → entrée stock
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
  name: 'StockReceptions',
  data() {
    return {
      items: [],
      loading: false,
      saving: false,
      search: '',
      statut: '',
      showModal: false,
      form: this.emptyForm(),
      pieces: [],
      fournisseurs: [],
      articles: [],
      achats: [],
      emplacements: [],
      debounceTimer: null,
    };
  },
  computed: {
    isLocked() {
      return this.form.statut === 'valide';
    },
  },
  mounted() {
    this.loadMeta();
    this.loadRefs();
    this.load();
  },
  methods: {
    emptyForm() {
      return {
        id: null,
        numero: null,
        date_reception: new Date().toISOString().slice(0, 10),
        fournisseur_id: null,
        achat_id: null,
        reference_achat: '',
        reference_commande: '',
        reference_facture: '',
        observations: '',
        statut: 'brouillon',
        lignes: [this.emptyLigne()],
      };
    },
    emptyLigne() {
      return {
        article_id: null,
        designation: '',
        quantite_commandee: 0,
        quantite_recue: 0,
        unite: 'pcs',
        lot: '',
        date_production: '',
        date_peremption: '',
        stock_location_id: null,
        controle_qualite: 'ok',
        observations: '',
      };
    },
    async loadMeta() {
      try {
        const { data } = await axios.get('/api/stock/receptions/meta');
        this.emplacements = data.emplacements || [];
      } catch (e) { /* ignore */ }
    },
    async loadRefs() {
      try {
        const [f, a, ach] = await Promise.all([
          axios.get('/api/fournisseurs'),
          axios.get('/api/articles', { params: { per_page: 500 } }),
          axios.get('/api/achats', { params: { per_page: 100, type: 'stockable' } }),
        ]);
        this.fournisseurs = Array.isArray(f.data) ? f.data : (f.data.data || []);
        this.articles = Array.isArray(a.data) ? a.data : (a.data.data || []);
        this.achats = Array.isArray(ach.data) ? ach.data : (ach.data.data || []);
      } catch (e) { /* ignore */ }
    },
    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.load(), 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/stock/receptions', {
          params: { search: this.search, statut: this.statut },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    openCreate() {
      this.form = this.emptyForm();
      if (this.emplacements.length) {
        this.form.lignes[0].stock_location_id = this.emplacements.find(e => e.code === 'depot')?.id || this.emplacements[0].id;
      }
      this.pieces = [];
      this.showModal = true;
    },
    async openDetail(item) {
      const { data } = await axios.get(`/api/stock/receptions/${item.id}`);
      this.form = {
        id: data.id,
        numero: data.numero,
        date_reception: data.date_reception?.slice?.(0, 10) || data.date_reception,
        fournisseur_id: data.fournisseur_id,
        achat_id: data.achat_id,
        reference_achat: data.reference_achat || '',
        reference_commande: data.reference_commande || '',
        reference_facture: data.reference_facture || '',
        observations: data.observations || '',
        statut: data.statut,
        lignes: (data.lignes || []).map(l => ({
          article_id: l.article_id,
          designation: l.designation || '',
          quantite_commandee: Number(l.quantite_commandee),
          quantite_recue: Number(l.quantite_recue),
          unite: l.unite || '',
          lot: l.lot || '',
          date_production: l.date_production?.slice?.(0, 10) || '',
          date_peremption: l.date_peremption?.slice?.(0, 10) || '',
          stock_location_id: l.stock_location_id,
          controle_qualite: l.controle_qualite || 'ok',
          observations: l.observations || '',
        })),
      };
      if (!this.form.lignes.length) this.form.lignes = [this.emptyLigne()];
      this.pieces = data.pieces_jointes || [];
      this.showModal = true;
    },
    addLigne() {
      const l = this.emptyLigne();
      l.stock_location_id = this.emplacements.find(e => e.code === 'depot')?.id || this.emplacements[0]?.id || null;
      this.form.lignes.push(l);
    },
    onArticleSelect(l) {
      const art = this.articles.find(a => a.id === l.article_id);
      if (art) {
        l.designation = art.designation;
        l.unite = art.unite_facturation || l.unite || 'pcs';
      }
    },
    async onAchatChange() {
      if (!this.form.achat_id) return;
      try {
        const { data } = await axios.get(`/api/achats/${this.form.achat_id}`);
        this.form.fournisseur_id = data.fournisseur_id;
        this.form.reference_achat = data.numero;
        this.form.reference_commande = data.reference_commande || '';
        this.form.reference_facture = data.reference_facture || '';
        const depot = this.emplacements.find(e => e.code === 'depot')?.id || this.emplacements[0]?.id;
        this.form.lignes = (data.lignes || []).map(al => ({
          article_id: al.article_id,
          designation: al.designation || '',
          quantite_commandee: Number(al.quantite),
          quantite_recue: Number(al.quantite),
          unite: al.unite || 'pcs',
          lot: al.lot || '',
          date_production: '',
          date_peremption: '',
          stock_location_id: depot,
          controle_qualite: 'ok',
          observations: '',
        }));
        if (!this.form.lignes.length) this.form.lignes = [this.emptyLigne()];
      } catch (e) { /* ignore */ }
    },
    async save() {
      this.saving = true;
      try {
        const payload = { ...this.form, lignes: this.form.lignes };
        if (this.form.id) {
          await axios.put(`/api/stock/receptions/${this.form.id}`, payload);
        } else {
          const { data } = await axios.post('/api/stock/receptions', payload);
          this.form.id = data.id;
          this.form.numero = data.numero;
          this.form.statut = data.statut;
        }
        await this.load();
        this.$toast?.success?.('Enregistré') || null;
      } catch (e) {
        alert(e.response?.data?.message || 'Erreur enregistrement');
      } finally {
        this.saving = false;
      }
    },
    async valider() {
      if (!confirm('Valider ce BR et générer l\'entrée en stock (qté reçue) ?')) return;
      this.saving = true;
      try {
        if (this.form.statut === 'brouillon') {
          await this.save();
        }
        const { data } = await axios.post(`/api/stock/receptions/${this.form.id}/valider`, {
          generer_entree_stock: true,
        });
        this.form.statut = data.statut;
        await this.load();
        alert('BR validé — stock disponible mis à jour');
      } catch (e) {
        alert(e.response?.data?.message || 'Erreur validation');
      } finally {
        this.saving = false;
      }
    },
    async uploadPiece(ev) {
      const file = ev.target.files?.[0];
      if (!file || !this.form.id) return;
      const fd = new FormData();
      fd.append('fichier', file);
      try {
        const { data } = await axios.post(`/api/stock/receptions/${this.form.id}/pieces`, fd);
        this.pieces.push(data);
      } catch (e) {
        alert('Échec upload');
      }
      ev.target.value = '';
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    labelStatut(s) {
      return { brouillon: 'Brouillon', valide: 'Validé', annule: 'Annulé' }[s] || s;
    },
    badgeClass(s) {
      return {
        brouillon: 'bg-gray-100 text-gray-700',
        valide: 'bg-emerald-50 text-emerald-700',
        annule: 'bg-red-50 text-red-700',
      }[s] || 'bg-gray-50 text-gray-600';
    },
  },
};
</script>
