<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="app-page-header mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Notes de crédit</h1>
          <p class="text-gray-600 text-sm mt-1">
            Depuis facture, retour, réclamation, correction commerciale, erreur de prix ou annulation partielle
          </p>
        </div>
        <button @click="openCreate()" type="button" class="app-page-header__action px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle note de crédit
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 app-toolbar">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N°, client, facture d’origine…"
          class="app-toolbar__search flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="motif" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous motifs</option>
          <option v-for="m in (meta.motifs || [])" :key="m" :value="m">{{ labelMotif(m) }}</option>
        </select>
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous statuts</option>
          <option v-for="s in (meta.statuts || [])" :key="s" :value="s">{{ labelStatut(s) }}</option>
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
              <th class="py-3 px-4">Facture d’origine</th>
              <th class="py-3 px-4">Motif</th>
              <th class="py-3 px-4">HT</th>
              <th class="py-3 px-4">TTC</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune note de crédit</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_note) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">
                <span>{{ item.facture_origine || '—' }}</span>
                <span v-if="item.reclamation" class="block text-xs text-gray-500">Récl. {{ item.reclamation.numero }}</span>
              </td>
              <td class="py-3 px-4">{{ labelMotif(item.motif) }}</td>
              <td class="py-3 px-4">{{ formatMoney(item.total_ht) }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 rounded text-xs font-medium" :class="statutClass(item.statut)">{{ labelStatut(item.statut) }}</span>
              </td>
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
            <h2 class="app-modal__title">{{ editingId ? 'Note de crédit ' + form.numero : 'Nouvelle note de crédit' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <div v-if="!editingId" class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Mode de création</p>
              <div class="app-form-grid app-form-grid--3">
                <button
                  v-for="o in originesCreate"
                  :key="o.value"
                  type="button"
                  @click="setOrigine(o.value)"
                  class="text-left p-3 border rounded-lg transition-colors"
                  :class="form.origine === o.value ? 'border-teal-500 bg-teal-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                >
                  <span class="font-medium block text-sm text-gray-900">{{ o.label }}</span>
                  <span class="text-xs text-gray-500 mt-0.5 block">{{ o.hint }}</span>
                </button>
              </div>

              <div v-if="form.origine === 'reclamation'" class="mt-4">
                <label class="block text-sm text-gray-600 mb-1">Réclamation</label>
                <select v-model="form.reclamation_id" @change="onReclamationChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="r in reclamations" :key="r.id" :value="r.id">{{ r.numero }} — {{ r.client?.nom || '' }}</option>
                </select>
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
                <input type="date" v-model="form.date_note" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Facture d’origine</label>
                <input v-model="form.facture_origine" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° facture" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Retour lié</label>
                <input v-model="form.retour_ref" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° retour" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Commande liée</label>
                <select v-model="form.commande_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="cmd in commandes" :key="cmd.id" :value="cmd.id">{{ cmd.numero }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Motif</label>
                <select v-model="form.motif" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="m in (meta.motifs || [])" :key="m" :value="m">{{ labelMotif(m) }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Devise</label>
                <select v-model="form.devise" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option>MAD</option>
                  <option>EUR</option>
                  <option>USD</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option v-for="s in (meta.statuts || [])" :key="s" :value="s">{{ labelStatut(s) }}</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="block text-sm text-gray-600 mb-1">Observations</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Articles</h3>
                <button @click="addLigne" type="button" class="text-sm font-medium text-teal-600">+ Ligne</button>
              </div>

              <div class="space-y-3">
                <div v-for="(ligne, idx) in form.lignes" :key="idx" class="app-line-card">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ligne {{ idx + 1 }}</span>
                    <button
                      v-if="form.lignes.length > 1"
                      @click="form.lignes.splice(idx, 1)"
                      type="button"
                      class="text-red-500 text-sm"
                    >Supprimer</button>
                  </div>

                  <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Désignation</label>
                    <input v-model="ligne.designation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Désignation" />
                  </div>

                  <div class="achat-grid-4 mb-3">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">Qté</label>
                      <input type="number" step="0.001" v-model.number="ligne.quantite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">Prix</label>
                      <input type="number" step="0.01" v-model.number="ligne.prix" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">Remise %</label>
                      <input type="number" step="0.01" v-model.number="ligne.remise" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">TVA %</label>
                      <input type="number" step="0.01" v-model.number="ligne.tva_taux" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                  </div>

                  <div class="achat-grid-4">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">HT</label>
                      <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium">{{ formatMoney(ligneHt(ligne)) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">TVA</label>
                      <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium">{{ formatMoney(ligneTva(ligne)) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">TTC</label>
                      <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium text-teal-700">{{ formatMoney(ligneHt(ligne) + ligneTva(ligne)) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="editingId" class="border-t pt-4 mt-5">
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

          <div class="app-modal__footer app-modal__footer--between">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 achat-md-row">
              <span>HT : <strong>{{ formatMoney(totalHt) }}</strong></span>
              <span>TVA : <strong>{{ formatMoney(totalTva) }}</strong></span>
              <span>TTC : <strong class="text-teal-700">{{ formatMoney(totalTtc) }} {{ form.devise }}</strong></span>
            </div>
            <div class="flex justify-end gap-3">
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
  name: 'NotesCredit',
  data() {
    return {
      items: [],
      clients: [],
      commandes: [],
      reclamations: [],
      pieces: [],
      meta: {},
      loading: false,
      saving: false,
      showModal: false,
      editingId: null,
      search: '',
      motif: '',
      statut: '',
      origine: '',
      error: '',
      timer: null,
      form: this.emptyForm(),
      originesCreate: [
        { value: 'facture', label: 'Depuis facture', hint: 'Corriger une facture client' },
        { value: 'retour', label: 'Depuis retour', hint: 'Suite à un bon de retour' },
        { value: 'reclamation', label: 'Depuis réclamation', hint: 'Suite à une réclamation' },
        { value: 'correction_commerciale', label: 'Correction commerciale', hint: 'Ajustement commercial' },
        { value: 'erreur_prix', label: 'Erreur de prix', hint: 'Correction tarifaire' },
        { value: 'annulation_partielle', label: 'Annulation partielle', hint: 'Annuler une partie' },
        { value: 'directe', label: 'Saisie directe', hint: 'Création manuelle' },
      ],
    };
  },
  computed: {
    totalHt() {
      return this.form.lignes.reduce((s, l) => s + this.ligneHt(l), 0);
    },
    totalTva() {
      return this.form.lignes.reduce((s, l) => s + this.ligneTva(l), 0);
    },
    totalTtc() {
      return this.totalHt + this.totalTva;
    },
  },
  mounted() {
    this.loadMeta();
    this.loadLookups();
    this.load();
  },
  methods: {
    emptyForm() {
      return {
        numero: '',
        date_note: new Date().toISOString().slice(0, 10),
        client_id: null,
        origine: 'directe',
        facture_origine: '',
        reclamation_id: null,
        commande_id: null,
        retour_ref: '',
        motif: null,
        devise: 'MAD',
        observations: '',
        statut: 'brouillon',
        lignes: [{ designation: '', quantite: 1, prix: 0, remise: 0, tva_taux: 20 }],
      };
    },
    ligneHt(l) {
      const brut = (Number(l.quantite) || 0) * (Number(l.prix) || 0);
      return Math.round(brut * (1 - (Number(l.remise) || 0) / 100) * 100) / 100;
    },
    ligneTva(l) {
      return Math.round(this.ligneHt(l) * ((Number(l.tva_taux) || 0) / 100) * 100) / 100;
    },
    labelStatut(s) {
      return this.meta.labels?.statuts?.[s] || s || '—';
    },
    labelMotif(m) {
      return this.meta.labels?.motifs?.[m] || m || '—';
    },
    labelOrigine(o) {
      return this.meta.labels?.origines?.[o] || o || '—';
    },
    statutClass(s) {
      const map = {
        brouillon: 'bg-gray-100 text-gray-700',
        validee: 'bg-teal-100 text-teal-800',
        appliquee: 'bg-green-100 text-green-800',
        annulee: 'bg-red-100 text-red-800',
      };
      return map[s] || 'bg-gray-100 text-gray-700';
    },
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
    async loadMeta() {
      const { data } = await axios.get('/api/notes-credit/meta');
      this.meta = data;
    },
    async loadLookups() {
      const [c, cmd, r] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 200 } }),
        axios.get('/api/commandes', { params: { per_page: 100 } }).catch(() => ({ data: { data: [] } })),
        axios.get('/api/reclamations', { params: { per_page: 100 } }).catch(() => ({ data: { data: [] } })),
      ]);
      this.clients = c.data.data || c.data;
      this.commandes = cmd.data.data || cmd.data || [];
      this.reclamations = r.data.data || r.data || [];
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/notes-credit', {
          params: { search: this.search, motif: this.motif, statut: this.statut, origine: this.origine },
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
      const { data } = await axios.get(`/api/notes-credit/${item.id}`);
      this.editingId = data.id;
      this.pieces = data.pieces_jointes || [];
      this.form = {
        numero: data.numero,
        date_note: data.date_note?.substring?.(0, 10) || '',
        client_id: data.client_id,
        origine: data.origine || 'directe',
        facture_origine: data.facture_origine || '',
        reclamation_id: data.reclamation_id,
        commande_id: data.commande_id,
        retour_ref: data.retour_ref || '',
        motif: data.motif || null,
        devise: data.devise || 'MAD',
        observations: data.observations || '',
        statut: data.statut || 'brouillon',
        lignes: (data.lignes || []).map(l => ({
          designation: l.designation || '',
          quantite: Number(l.quantite),
          prix: Number(l.prix),
          remise: Number(l.remise),
          tva_taux: Number(l.tva_taux),
          article_id: l.article_id,
          unite: l.unite,
        })),
      };
      if (!this.form.lignes.length) {
        this.form.lignes = [{ designation: '', quantite: 1, prix: 0, remise: 0, tva_taux: 20 }];
      }
      this.error = '';
      this.showModal = true;
    },
    setOrigine(o) {
      this.form.origine = o;
      if (o !== 'reclamation') this.form.reclamation_id = null;
    },
    onReclamationChange() {
      const r = this.reclamations.find(x => x.id === this.form.reclamation_id);
      if (!r) return;
      this.form.client_id = r.client_id;
      this.form.commande_id = r.commande_id || this.form.commande_id;
      this.form.motif = this.form.motif || 'non_conformite';
      if (r.article_libelle || r.article?.designation) {
        this.form.lignes = [{
          designation: r.article_libelle || r.article?.designation || '',
          quantite: Number(r.quantite) || 1,
          prix: 0,
          remise: 0,
          tva_taux: 20,
          article_id: r.article_id,
        }];
      }
    },
    addLigne() {
      this.form.lignes.push({ designation: '', quantite: 1, prix: 0, remise: 0, tva_taux: 20 });
    },
    async uploadPiece(e) {
      const file = e.target.files?.[0];
      if (!file || !this.editingId) return;
      const fd = new FormData();
      fd.append('fichier', file);
      const { data } = await axios.post(`/api/notes-credit/${this.editingId}/pieces`, fd);
      this.pieces.push(data);
      e.target.value = '';
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        if (this.editingId) {
          await axios.put(`/api/notes-credit/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/notes-credit', this.form);
        }
        this.showModal = false;
        this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de l\'enregistrement';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
