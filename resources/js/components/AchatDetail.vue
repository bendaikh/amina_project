<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8" v-if="achat">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <router-link to="/achats" class="text-sm text-teal-600 hover:underline">← Achats</router-link>
          <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ achat.numero }}</h1>
          <p class="text-gray-600">{{ achat.fournisseur?.nom || 'Sans fournisseur' }} · {{ labelType(achat.type) }}</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <select v-model="newStatut" @change="changeStatut" class="border rounded-lg px-3 py-2 text-sm">
            <option v-for="s in processus" :key="s.key" :value="s.key">{{ s.label }}</option>
          </select>
          <button @click="save" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm">Enregistrer</button>
        </div>
      </div>

      <!-- Processus -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 overflow-x-auto">
        <div class="flex items-center gap-1 min-w-max">
          <template v-for="(step, i) in processus" :key="step.key">
            <span
              class="px-3 py-1.5 rounded-lg text-xs font-medium"
              :class="stepIndex(achat.statut) >= i ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-500'"
            >{{ step.label }}</span>
            <span v-if="i < processus.length - 1" class="text-gray-300 px-1">→</span>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Total HT</p><p class="text-xl font-bold">{{ formatMoney(achat.total_ht) }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">TVA</p><p class="text-xl font-bold">{{ formatMoney(achat.total_tva) }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Total TTC</p><p class="text-xl font-bold">{{ formatMoney(achat.total_ttc) }} {{ achat.devise }}</p></div>
        <div class="bg-white border rounded-xl p-4"><p class="text-xs text-gray-500">Échéance</p><p class="text-xl font-bold">{{ formatDate(achat.echeance) }}</p></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <section class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold text-lg mb-4">Saisie</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <Field label="Date" v-model="form.date_achat" type="date" />
              <div>
                <label class="text-xs text-gray-500">Fournisseur</label>
                <select v-model="form.fournisseur_id" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
                </select>
              </div>
              <Field label="Réf. commande" v-model="form.reference_commande" />
              <Field label="Réf. facture" v-model="form.reference_facture" />
              <Field label="Date facture" v-model="form.date_facture" type="date" />
              <div>
                <label class="text-xs text-gray-500">Devise</label>
                <select v-model="form.devise" class="w-full border rounded-lg px-3 py-2">
                  <option>MAD</option><option>EUR</option><option>USD</option>
                </select>
              </div>
              <Field label="Conditions" v-model="form.conditions" />
              <div>
                <label class="text-xs text-gray-500">Mode de paiement</label>
                <select v-model="form.mode_paiement" class="w-full border rounded-lg px-3 py-2">
                  <option value="">—</option>
                  <option>Virement</option><option>Chèque</option><option>Espèces</option><option>Traite</option><option>Carte</option>
                </select>
              </div>
              <Field label="Échéance" v-model="form.echeance" type="date" />
              <Field label="Acheteur" v-model="form.acheteur" />
              <div>
                <label class="text-xs text-gray-500">Type</label>
                <select v-model="form.type" class="w-full border rounded-lg px-3 py-2">
                  <option value="stockable">Stockable</option>
                  <option value="non_stockable">Non stockable</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Catégorie</label>
                <select v-model="form.categorie" class="w-full border rounded-lg px-3 py-2">
                  <option value="">—</option>
                  <option v-for="c in categoriesForType" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Observation</label>
                <textarea v-model="form.observations" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
              <p v-if="form.type === 'non_stockable'" class="md:col-span-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                Un achat non stockable ne génère pas automatiquement d'entrée en stock.
              </p>
              <label v-else class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="form.genere_entree_stock" /> Générer entrée en stock à la réception
              </label>
            </div>
          </section>

          <section class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-semibold text-lg">Lignes</h2>
              <button @click="addLigne" type="button" class="text-sm text-teal-600">+ Ligne</button>
            </div>
            <div class="app-table-scroll app-table-scroll--wide">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Article</th>
                    <th>Désignation</th>
                    <th>Qté</th>
                    <th>Unité</th>
                    <th>Prix</th>
                    <th>Remise</th>
                    <th>TVA</th>
                    <th>HT</th>
                    <th>Lot</th>
                    <th>Date prévue</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-b border-gray-50">
                    <td class="py-1 pr-1">
                      <select v-model="ligne.article_id" @change="fillArticle(ligne)" class="border rounded px-1 py-1 w-36 text-xs">
                        <option :value="null">—</option>
                        <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                      </select>
                    </td>
                    <td><input v-model="ligne.designation" class="border rounded px-1 py-1 w-28 text-xs" /></td>
                    <td><input type="number" step="0.001" v-model.number="ligne.quantite" class="border rounded px-1 py-1 w-16 text-xs" /></td>
                    <td><input v-model="ligne.unite" class="border rounded px-1 py-1 w-12 text-xs" /></td>
                    <td><input type="number" step="0.01" v-model.number="ligne.prix" class="border rounded px-1 py-1 w-20 text-xs" /></td>
                    <td><input type="number" step="0.01" v-model.number="ligne.remise" class="border rounded px-1 py-1 w-14 text-xs" /></td>
                    <td><input type="number" step="0.01" v-model.number="ligne.tva_taux" class="border rounded px-1 py-1 w-14 text-xs" /></td>
                    <td class="text-xs font-medium">{{ formatMoney(ligneHt(ligne)) }}</td>
                    <td><input v-model="ligne.lot" class="border rounded px-1 py-1 w-16 text-xs" /></td>
                    <td><input type="date" v-model="ligne.date_prevue" class="border rounded px-1 py-1 w-28 text-xs" /></td>
                    <td><button @click="form.lignes.splice(idx,1)" class="text-red-500">×</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="space-y-6">
          <section class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold">Réceptions</h2>
              <div class="flex items-center gap-3">
                <router-link to="/stock/receptions" class="text-xs text-gray-500 hover:text-teal-600">Voir stock</router-link>
                <button @click="addReception" :disabled="savingReception" class="text-sm text-teal-600">+ Réception</button>
              </div>
            </div>
            <div v-if="!achat.receptions?.length" class="text-sm text-gray-500">Aucune réception</div>
            <ul class="space-y-2">
              <li v-for="r in achat.receptions" :key="r.id" class="text-sm border rounded-lg p-3">
                <p class="font-medium text-teal-700">{{ r.numero }}</p>
                <p class="text-gray-500">{{ formatDate(r.date_reception) }}</p>
                <p v-if="r.entree_stock_generee" class="text-xs text-emerald-600 mt-1">Entrée stock générée</p>
                <p v-else-if="achat.type === 'non_stockable'" class="text-xs text-amber-600 mt-1">Pas d'entrée stock (non stockable)</p>
                <p v-else class="text-xs text-amber-600 mt-1">BR créé — valider dans Stock si besoin</p>
              </li>
            </ul>
          </section>

          <section class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold">Factures</h2>
              <router-link :to="{ path: '/achats/factures', query: { achat_id: achat.id } }" class="text-sm text-teal-600">Créer facture</router-link>
            </div>
            <div v-if="!achat.factures?.length" class="text-sm text-gray-500">Aucune facture liée</div>
            <ul class="space-y-2">
              <li v-for="f in achat.factures" :key="f.id" class="text-sm border rounded-lg p-3">
                <p class="font-medium">{{ f.numero }}</p>
                <p class="text-gray-500">{{ formatMoney(f.total_ttc) }} {{ f.devise }} · {{ f.statut }}</p>
              </li>
            </ul>
          </section>

          <section class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-3">Pièces jointes</h2>
            <input type="file" @change="uploadPiece" class="text-sm w-full mb-3" />
            <ul class="space-y-1">
              <li v-for="p in achat.pieces_jointes || []" :key="p.id" class="text-sm text-gray-700 truncate">
                {{ p.nom_fichier }}
              </li>
            </ul>
            <p v-if="!(achat.pieces_jointes || []).length" class="text-sm text-gray-500">Aucun fichier</p>
          </section>
        </div>
      </div>

      <p v-if="error" class="text-red-600 text-sm mt-4">{{ error }}</p>
      <p v-if="message" class="text-teal-600 text-sm mt-4">{{ message }}</p>
    </div>
  </div>
  <div v-else class="p-10 text-center text-gray-500">Chargement…</div>
</template>

<script>
import axios from 'axios';
import { h } from 'vue';

const Field = {
  props: { label: String, modelValue: [String, Number], type: { type: String, default: 'text' } },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('div', [
      h('label', { class: 'text-xs text-gray-500' }, props.label),
      h('input', {
        type: props.type,
        class: 'w-full border rounded-lg px-3 py-2',
        value: props.modelValue ?? '',
        onInput: (e) => emit('update:modelValue', e.target.value),
      }),
    ]);
  },
};

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
  name: 'AchatDetail',
  components: { Field },
  data() {
    return {
      achat: null,
      form: {},
      fournisseurs: [],
      articles: [],
      newStatut: 'demande',
      saving: false,
      savingReception: false,
      error: '',
      message: '',
      processus: PROCESSUS,
    };
  },
  computed: {
    categoriesForType() {
      return this.form.type === 'non_stockable' ? CAT_NON_STOCKABLE : CAT_STOCKABLE;
    },
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    stepIndex(s) {
      return PROCESSUS.findIndex(p => p.key === s);
    },
    labelType(t) {
      return t === 'non_stockable' ? 'Non stockable' : 'Stockable';
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    ligneHt(l) {
      const brut = (Number(l.quantite) || 0) * (Number(l.prix) || 0);
      return Math.round(brut * (1 - (Number(l.remise) || 0) / 100) * 100) / 100;
    },
    async load() {
      const { data } = await axios.get(`/api/achats/${this.$route.params.id}`);
      this.achat = data;
      this.newStatut = data.statut;
      this.form = {
        date_achat: data.date_achat?.substring?.(0, 10) || data.date_achat || '',
        fournisseur_id: data.fournisseur_id,
        reference_commande: data.reference_commande || '',
        reference_facture: data.reference_facture || '',
        date_facture: data.date_facture?.substring?.(0, 10) || '',
        devise: data.devise || 'MAD',
        conditions: data.conditions || '',
        mode_paiement: data.mode_paiement || '',
        echeance: data.echeance?.substring?.(0, 10) || '',
        acheteur: data.acheteur || '',
        observations: data.observations || '',
        type: data.type || 'stockable',
        categorie: data.categorie || '',
        genere_entree_stock: !!data.genere_entree_stock,
        lignes: (data.lignes || []).map(l => ({
          article_id: l.article_id,
          designation: l.designation || '',
          quantite: Number(l.quantite),
          unite: l.unite || 'U',
          prix: Number(l.prix),
          remise: Number(l.remise),
          tva_taux: Number(l.tva_taux),
          lot: l.lot || '',
          date_prevue: l.date_prevue?.substring?.(0, 10) || '',
        })),
      };
      if (!this.form.lignes.length) {
        this.form.lignes = [{ article_id: null, designation: '', quantite: 1, unite: 'U', prix: 0, remise: 0, tva_taux: 20, lot: '', date_prevue: '' }];
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
    addLigne() {
      this.form.lignes.push({ article_id: null, designation: '', quantite: 1, unite: 'U', prix: 0, remise: 0, tva_taux: 20, lot: '', date_prevue: '' });
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
      this.message = '';
      try {
        const { data } = await axios.put(`/api/achats/${this.achat.id}`, this.form);
        this.achat = data;
        this.message = 'Enregistré';
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur';
      } finally {
        this.saving = false;
      }
    },
    async changeStatut() {
      try {
        await axios.post(`/api/achats/${this.achat.id}/statut`, { statut: this.newStatut });
        this.achat.statut = this.newStatut;
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur statut';
      }
    },
    async addReception() {
      this.savingReception = true;
      try {
        await axios.post(`/api/achats/${this.achat.id}/receptions`, {
          date_reception: new Date().toISOString().slice(0, 10),
        });
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur réception';
      } finally {
        this.savingReception = false;
      }
    },
    async uploadPiece(e) {
      const file = e.target.files?.[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('fichier', file);
      try {
        await axios.post(`/api/achats/${this.achat.id}/pieces`, fd);
        await this.load();
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur upload';
      }
      e.target.value = '';
    },
  },
};
</script>
