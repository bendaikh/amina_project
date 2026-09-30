<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Achats</h1>
          <p class="text-gray-600 text-sm mt-1">
            Demande d'achat → Commande fournisseur → Réception → Facture → Échéance → Règlement
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvel achat
        </button>
      </div>

      <!-- Processus strip -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 overflow-x-auto">
        <div class="flex items-center gap-1 min-w-max">
          <template v-for="(step, i) in processusSteps" :key="step.key">
            <button
              type="button"
              @click="statut = statut === step.key ? '' : step.key; load()"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
              :class="statut === step.key ? 'bg-teal-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-teal-50'"
            >{{ step.label }}</button>
            <span v-if="i < processusSteps.length - 1" class="text-gray-300 px-1">→</span>
          </template>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N° achat, fournisseur, réf. commande…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="type" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les types</option>
          <option value="stockable">Stockable</option>
          <option value="non_stockable">Non stockable</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° Achat</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Fournisseur</th>
              <th class="py-3 px-4">Type</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4">Échéance</th>
              <th class="py-3 px-4">Total TTC</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="8" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="8" class="py-10 text-center text-gray-500">Aucun achat</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_achat) }}</td>
              <td class="py-3 px-4">{{ item.fournisseur?.nom || '—' }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium" :class="item.type === 'stockable' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                  {{ item.type === 'stockable' ? 'Stockable' : 'Non stockable' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4">{{ formatDate(item.echeance) }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
              <td class="py-3 px-4 text-right">
                <router-link :to="`/achats/${item.id}`" class="text-teal-600 hover:underline font-medium">Ouvrir</router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create modal -->
      <div v-if="showModal" class="app-modal-overlay" @click.self="showModal=false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Nouvel achat</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <!-- Type -->
            <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Type d'achat</p>
              <div class="app-form-grid app-form-grid--2 mb-3">
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="form.type === 'stockable' ? 'border-teal-500 bg-teal-50' : 'border-gray-200'">
                  <input type="radio" v-model="form.type" value="stockable" class="mt-1" />
                  <span>
                    <span class="font-medium block">Achats stockables</span>
                    <span class="text-xs text-gray-500">Matières, produits, emballages, cartons, fûts, palettes, consommables stockés, pièces</span>
                  </span>
                </label>
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="form.type === 'non_stockable' ? 'border-teal-500 bg-teal-50' : 'border-gray-200'">
                  <input type="radio" v-model="form.type" value="non_stockable" class="mt-1" />
                  <span>
                    <span class="font-medium block">Achats non stockables</span>
                    <span class="text-xs text-gray-500">Transport, honoraires, électricité, assurance, télécoms, services, frais bancaires, divers</span>
                  </span>
                </label>
              </div>
              <div class="app-form-grid app-form-grid--2">
                <div>
                  <label class="text-sm text-gray-600">Catégorie</label>
                  <select v-model="form.categorie" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Sélectionner…</option>
                    <option v-for="c in categoriesForType" :key="c.value" :value="c.value">{{ c.label }}</option>
                  </select>
                </div>
                <div v-if="form.type === 'non_stockable'" class="flex items-end">
                  <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    Un achat non stockable ne génère pas automatiquement d'entrée en stock.
                  </p>
                </div>
                <label v-else class="flex items-center gap-2 text-sm mt-6">
                  <input type="checkbox" v-model="form.genere_entree_stock" />
                  Générer une entrée en stock à la réception
                </label>
              </div>
            </div>

            <!-- En-tête saisie -->
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">Date</label>
                <input type="date" v-model="form.date_achat" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Fournisseur</label>
                <select v-model="form.fournisseur_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Réf. commande / facture</label>
                <input v-model="form.reference_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date facture</label>
                <input type="date" v-model="form.date_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Devise</label>
                <select v-model="form.devise" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option>MAD</option>
                  <option>EUR</option>
                  <option>USD</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Conditions</label>
                <input v-model="form.conditions" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Net 30, etc." />
              </div>
              <div>
                <label class="text-sm text-gray-600">Mode de paiement</label>
                <select v-model="form.mode_paiement" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="">—</option>
                  <option>Virement</option>
                  <option>Chèque</option>
                  <option>Espèces</option>
                  <option>Traite</option>
                  <option>Carte</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Échéance</label>
                <input type="date" v-model="form.echeance" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Acheteur</label>
                <input v-model="form.acheteur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Observation</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <!-- Lignes -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Lignes</h3>
                <button @click="addLigne" type="button" class="text-sm font-medium text-teal-600">+ Ligne</button>
              </div>

              <div class="space-y-3">
                <div
                  v-for="(ligne, idx) in form.lignes"
                  :key="idx"
                  class="app-line-card"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ligne {{ idx + 1 }}</span>
                    <button
                      v-if="form.lignes.length > 1"
                      @click="form.lignes.splice(idx,1)"
                      type="button"
                      class="text-red-500 text-sm"
                    >Supprimer</button>
                  </div>

                  <div class="app-form-grid app-form-grid--2 mb-3">
                    <div>
                      <label class="text-xs text-gray-500">Article / service</label>
                      <select v-model="ligne.article_id" @change="fillArticle(ligne)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option :value="null">—</option>
                        <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }} — {{ a.designation }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Désignation</label>
                      <input v-model="ligne.designation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                  </div>

                  <div class="achat-grid-4 mb-3">
                    <div>
                      <label class="text-xs text-gray-500">Qté</label>
                      <input type="number" step="0.001" v-model.number="ligne.quantite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Unité</label>
                      <input v-model="ligne.unite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Prix</label>
                      <input type="number" step="0.01" v-model.number="ligne.prix" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Remise %</label>
                      <input type="number" step="0.01" v-model.number="ligne.remise" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                  </div>

                  <div class="achat-grid-4">
                    <div>
                      <label class="text-xs text-gray-500">TVA %</label>
                      <input type="number" step="0.01" v-model.number="ligne.tva_taux" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">HT</label>
                      <div class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 font-medium">{{ formatMoney(ligneHt(ligne)) }}</div>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Lot</label>
                      <input v-model="ligne.lot" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                      <label class="text-xs text-gray-500">Date prévue</label>
                      <input type="date" v-model="ligne.date_prevue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="app-modal__footer app-modal__footer--between">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 achat-md-row">
              <span>HT : <strong>{{ formatMoney(totalHt) }}</strong></span>
              <span>TVA : <strong>{{ formatMoney(totalTva) }}</strong></span>
              <span>TTC : <strong class="text-teal-700">{{ formatMoney(totalTtc) }} {{ form.devise }}</strong></span>
            </div>
            <div class="flex justify-end gap-3">
              <button @click="showModal=false" type="button" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
              <button @click="save" type="button" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg">{{ saving ? 'Enregistrement…' : 'Créer' }}</button>
            </div>
            <p v-if="error" class="text-red-600 text-sm w-full">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const PROCESSUS = [
  { key: 'demande', label: "Demande d'achat" },
  { key: 'commande', label: 'Commande fournisseur' },
  { key: 'reception', label: 'Réception' },
  { key: 'facture', label: 'Facture fournisseur' },
  { key: 'echeance', label: 'Échéance' },
  { key: 'regle', label: 'Règlement' },
];

const CAT_STOCKABLE = [
  { value: 'matieres', label: 'Matières' },
  { value: 'produits', label: 'Produits' },
  { value: 'emballages', label: 'Emballages' },
  { value: 'cartons', label: 'Cartons' },
  { value: 'futs', label: 'Fûts' },
  { value: 'palettes', label: 'Palettes' },
  { value: 'consommables_stockes', label: 'Consommables stockés' },
  { value: 'pieces', label: 'Pièces' },
];

const CAT_NON_STOCKABLE = [
  { value: 'transport', label: 'Transport' },
  { value: 'honoraires', label: 'Honoraires' },
  { value: 'electricite', label: 'Électricité' },
  { value: 'assurance', label: 'Assurance' },
  { value: 'telecoms', label: 'Télécoms' },
  { value: 'services', label: 'Services' },
  { value: 'frais_bancaires', label: 'Frais bancaires' },
  { value: 'divers', label: 'Divers' },
];

export default {
  name: 'Purchases',
  data() {
    return {
      items: [],
      fournisseurs: [],
      articles: [],
      loading: false,
      saving: false,
      showModal: false,
      search: '',
      statut: '',
      type: '',
      error: '',
      timer: null,
      processusSteps: PROCESSUS,
      form: this.emptyForm(),
    };
  },
  computed: {
    categoriesForType() {
      return this.form.type === 'non_stockable' ? CAT_NON_STOCKABLE : CAT_STOCKABLE;
    },
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
  watch: {
    'form.type'(val) {
      this.form.categorie = '';
      if (val === 'non_stockable') this.form.genere_entree_stock = false;
      else this.form.genere_entree_stock = true;
    },
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    emptyForm() {
      return {
        date_achat: new Date().toISOString().slice(0, 10),
        fournisseur_id: null,
        reference_commande: '',
        date_facture: '',
        devise: 'MAD',
        conditions: '',
        mode_paiement: '',
        echeance: '',
        acheteur: '',
        observations: '',
        type: 'stockable',
        categorie: '',
        statut: 'demande',
        genere_entree_stock: true,
        lignes: [this.emptyLigne()],
      };
    },
    emptyLigne() {
      return {
        article_id: null,
        designation: '',
        quantite: 1,
        unite: 'U',
        prix: 0,
        remise: 0,
        tva_taux: 20,
        lot: '',
        date_prevue: '',
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
      return PROCESSUS.find(p => p.key === s)?.label || s;
    },
    badgeClass(s) {
      const map = {
        demande: 'bg-slate-100 text-slate-700',
        commande: 'bg-blue-100 text-blue-700',
        reception: 'bg-cyan-100 text-cyan-700',
        facture: 'bg-violet-100 text-violet-700',
        echeance: 'bg-amber-100 text-amber-700',
        regle: 'bg-green-100 text-green-700',
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
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/achats', {
          params: { search: this.search, statut: this.statut, type: this.type },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const [f, a] = await Promise.all([
        axios.get('/api/fournisseurs'),
        axios.get('/api/articles'),
      ]);
      this.fournisseurs = f.data.data || f.data;
      this.articles = a.data.data || a.data;
    },
    openCreate() {
      this.form = this.emptyForm();
      this.error = '';
      this.showModal = true;
    },
    addLigne() {
      this.form.lignes.push(this.emptyLigne());
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (!a) return;
      ligne.designation = a.designation || '';
      ligne.unite = a.unite || ligne.unite || 'U';
      ligne.prix = Number(a.prix_achat || a.prix_achat_matiere || 0);
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        const { data } = await axios.post('/api/achats', this.form);
        this.showModal = false;
        this.$router.push(`/achats/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la création';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
