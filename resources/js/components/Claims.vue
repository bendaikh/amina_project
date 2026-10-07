<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="app-page-header mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Réclamations</h1>
          <p class="text-gray-600 text-sm mt-1">
            Depuis commande, BL, facture, retour, vente locale/export, article ou fiche client
          </p>
        </div>
        <button @click="openCreate()" type="button" class="app-page-header__action px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle réclamation
        </button>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-gray-500 text-sm mb-1">Total</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-gray-500 text-sm mb-1">Nouvelles</p>
          <p class="text-2xl font-bold text-red-600">{{ stats.nouvelles }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-gray-500 text-sm mb-1">En cours</p>
          <p class="text-2xl font-bold text-amber-600">{{ stats.en_cours }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-gray-500 text-sm mb-1">Résolues</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.resolues }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 app-toolbar">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N°, client, motif, document…"
          class="app-toolbar__search flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous statuts</option>
          <option v-for="s in (meta.statuts || [])" :key="s" :value="s">{{ labelStatut(s) }}</option>
        </select>
        <select v-model="priorite" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Toutes priorités</option>
          <option v-for="p in (meta.priorites || [])" :key="p" :value="p">{{ labelPriorite(p) }}</option>
        </select>
        <select v-model="origine" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Toutes origines</option>
          <option v-for="o in (meta.origines || [])" :key="o" :value="o">{{ labelOrigine(o) }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N°</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Origine</th>
              <th class="py-3 px-4">Article / Lot</th>
              <th class="py-3 px-4">Priorité</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4">Échéance</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune réclamation</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_reclamation) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">
                <span class="text-sm">{{ labelOrigine(item.origine) }}</span>
                <span v-if="item.document_origine" class="block text-xs text-gray-500">{{ item.document_origine }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="text-sm">{{ item.article_libelle || item.article?.designation || '—' }}</span>
                <span v-if="item.numero_lot" class="block text-xs text-gray-500">Lot {{ item.numero_lot }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="prioriteClass(item.priorite)">{{ labelPriorite(item.priorite) }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="statutClass(item.statut)">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4">{{ formatDate(item.date_limite) }}</td>
              <td class="py-3 px-4 text-right">
                <button @click="openEdit(item)" class="text-teal-600 hover:underline font-medium text-sm">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ editingId ? 'Réclamation ' + form.numero : 'Nouvelle réclamation' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <div v-if="!editingId" class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Source de création</p>
              <div class="app-form-grid app-form-grid--3">
                <button
                  v-for="o in originesCreate"
                  :key="o.value"
                  type="button"
                  @click="form.origine = o.value"
                  class="text-left p-3 border rounded-lg transition-colors"
                  :class="form.origine === o.value ? 'border-teal-500 bg-teal-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                >
                  <span class="font-medium block text-sm text-gray-900">{{ o.label }}</span>
                  <span class="text-xs text-gray-500 mt-0.5 block">{{ o.hint }}</span>
                </button>
              </div>
            </div>

            <div class="achat-header-grid mb-5">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Client</label>
                <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Date</label>
                <input type="date" v-model="form.date_reclamation" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Document d'origine</label>
                <input v-model="form.document_origine" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° commande, BL, facture…" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Commande liée</label>
                <select v-model="form.commande_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="cmd in commandes" :key="cmd.id" :value="cmd.id">{{ cmd.numero }} — {{ cmd.client?.nom || '' }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Article</label>
                <select v-model="form.article_id" @change="onArticleChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.designation || a.reference }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Libellé article</label>
                <input v-model="form.article_libelle" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Quantité</label>
                <input type="number" step="0.001" v-model.number="form.quantite" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">N° lot</label>
                <input v-model="form.numero_lot" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Motif</label>
                <input v-model="form.motif" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Non-conformité, retard…" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Priorité</label>
                <select v-model="form.priorite" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option v-for="p in (meta.priorites || [])" :key="p" :value="p">{{ labelPriorite(p) }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Responsable</label>
                <input v-model="form.responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Date limite</label>
                <input type="date" v-model="form.date_limite" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Action prévue</label>
                <select v-model="form.action_prevue" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="a in (meta.actions || [])" :key="a" :value="a">{{ labelAction(a) }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option v-for="s in (meta.statuts || [])" :key="s" :value="s">{{ labelStatut(s) }}</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="block text-sm text-gray-600 mb-1">Description</label>
                <textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
              <div class="achat-span-full">
                <label class="block text-sm text-gray-600 mb-1">Action corrective</label>
                <textarea v-model="form.action_corrective" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
              <div class="achat-span-full">
                <label class="block text-sm text-gray-600 mb-1">Réponse au client</label>
                <textarea v-model="form.reponse" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Date résolution</label>
                <input type="date" v-model="form.date_resolution" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Date clôture</label>
                <input type="date" v-model="form.date_cloture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
            </div>

            <div v-if="editingId" class="border-t pt-4">
              <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm text-teal-600 cursor-pointer font-medium">
                  + Pièce jointe
                  <input type="file" class="hidden" @change="uploadPiece" />
                </label>
                <span v-for="p in pieces" :key="p.id" class="text-xs bg-gray-100 rounded px-2 py-1">{{ p.nom_fichier }}</span>
                <span v-if="!pieces.length" class="text-xs text-gray-400">Aucune pièce jointe</span>
              </div>
            </div>

            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
          </div>

          <div class="app-modal__footer">
            <div class="flex justify-end gap-3 w-full">
              <button @click="showModal=false" type="button" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
              <button @click="save" type="button" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg">{{ saving ? 'Enregistrement…' : 'Enregistrer' }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Claims',
  data() {
    return {
      items: [],
      clients: [],
      articles: [],
      commandes: [],
      pieces: [],
      meta: {},
      stats: { total: 0, nouvelles: 0, en_cours: 0, resolues: 0 },
      loading: false,
      saving: false,
      showModal: false,
      editingId: null,
      search: '',
      statut: '',
      priorite: '',
      origine: '',
      error: '',
      timer: null,
      form: this.emptyForm(),
      originesCreate: [
        { value: 'commande', label: 'Commande', hint: 'Depuis une commande client' },
        { value: 'bl', label: 'Bon de livraison', hint: 'Depuis un BL' },
        { value: 'facture', label: 'Facture', hint: 'Depuis une facture' },
        { value: 'retour', label: 'Retour', hint: 'Depuis un bon de retour' },
        { value: 'vente_locale', label: 'Vente locale', hint: 'Vente marché local' },
        { value: 'vente_export', label: 'Vente export', hint: 'Vente à l’export' },
        { value: 'article', label: 'Article', hint: 'Depuis une fiche article' },
        { value: 'client', label: 'Fiche client', hint: 'Depuis le profil client' },
        { value: 'directe', label: 'Saisie directe', hint: 'Création manuelle' },
      ],
    };
  },
  mounted() {
    this.loadMeta();
    this.loadLookups();
    this.loadStats();
    this.load();
  },
  methods: {
    emptyForm() {
      return {
        numero: '',
        date_reclamation: new Date().toISOString().slice(0, 10),
        client_id: null,
        origine: 'directe',
        document_origine: '',
        commande_id: null,
        article_id: null,
        article_libelle: '',
        quantite: null,
        numero_lot: '',
        motif: '',
        description: '',
        priorite: 'normale',
        responsable: '',
        date_limite: '',
        action_prevue: null,
        action_corrective: '',
        reponse: '',
        statut: 'nouvelle',
        date_resolution: '',
        date_cloture: '',
      };
    },
    labelStatut(s) {
      return this.meta.labels?.statuts?.[s] || s;
    },
    labelOrigine(o) {
      return this.meta.labels?.origines?.[o] || o;
    },
    labelPriorite(p) {
      return this.meta.labels?.priorites?.[p] || p;
    },
    labelAction(a) {
      return this.meta.labels?.actions?.[a] || a;
    },
    statutClass(s) {
      const map = {
        nouvelle: 'bg-red-100 text-red-800',
        en_analyse: 'bg-amber-100 text-amber-800',
        action_en_cours: 'bg-orange-100 text-orange-800',
        attente_client: 'bg-blue-100 text-blue-800',
        resolue: 'bg-green-100 text-green-800',
        cloturee: 'bg-gray-100 text-gray-700',
        rejetee: 'bg-gray-200 text-gray-600',
      };
      return map[s] || 'bg-gray-100 text-gray-700';
    },
    prioriteClass(p) {
      const map = {
        basse: 'bg-gray-100 text-gray-600',
        normale: 'bg-teal-50 text-teal-800',
        haute: 'bg-amber-100 text-amber-800',
        urgente: 'bg-red-100 text-red-800',
      };
      return map[p] || 'bg-gray-100 text-gray-700';
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
      const { data } = await axios.get('/api/reclamations/meta');
      this.meta = data;
    },
    async loadStats() {
      const { data } = await axios.get('/api/reclamations/stats');
      this.stats = data;
    },
    async loadLookups() {
      const [c, a, cmd] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 200 } }),
        axios.get('/api/articles', { params: { per_page: 200 } }),
        axios.get('/api/commandes', { params: { per_page: 100 } }).catch(() => ({ data: { data: [] } })),
      ]);
      this.clients = c.data.data || c.data;
      this.articles = a.data.data || a.data;
      this.commandes = cmd.data.data || cmd.data || [];
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/reclamations', {
          params: { search: this.search, statut: this.statut, priorite: this.priorite, origine: this.origine },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    openCreate() {
      this.editingId = null;
      this.pieces = [];
      this.form = this.emptyForm();
      this.error = '';
      this.showModal = true;
    },
    async openEdit(item) {
      const { data } = await axios.get(`/api/reclamations/${item.id}`);
      this.editingId = data.id;
      this.pieces = data.pieces_jointes || [];
      this.form = {
        numero: data.numero,
        date_reclamation: data.date_reclamation?.substring?.(0, 10) || '',
        client_id: data.client_id,
        origine: data.origine || 'directe',
        document_origine: data.document_origine || '',
        commande_id: data.commande_id,
        article_id: data.article_id,
        article_libelle: data.article_libelle || '',
        quantite: data.quantite != null ? Number(data.quantite) : null,
        numero_lot: data.numero_lot || '',
        motif: data.motif || '',
        description: data.description || '',
        priorite: data.priorite || 'normale',
        responsable: data.responsable || '',
        date_limite: data.date_limite?.substring?.(0, 10) || '',
        action_prevue: data.action_prevue || null,
        action_corrective: data.action_corrective || '',
        reponse: data.reponse || '',
        statut: data.statut || 'nouvelle',
        date_resolution: data.date_resolution?.substring?.(0, 10) || '',
        date_cloture: data.date_cloture?.substring?.(0, 10) || '',
      };
      this.error = '';
      this.showModal = true;
    },
    onArticleChange() {
      const art = this.articles.find(a => a.id === this.form.article_id);
      if (art && !this.form.article_libelle) {
        this.form.article_libelle = art.designation || art.reference || '';
      }
    },
    async uploadPiece(e) {
      const file = e.target.files?.[0];
      if (!file || !this.editingId) return;
      const fd = new FormData();
      fd.append('fichier', file);
      const { data } = await axios.post(`/api/reclamations/${this.editingId}/pieces`, fd);
      this.pieces.push(data);
      e.target.value = '';
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        if (this.editingId) {
          await axios.put(`/api/reclamations/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/reclamations', this.form);
        }
        this.showModal = false;
        this.load();
        this.loadStats();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
