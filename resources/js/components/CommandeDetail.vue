<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8" v-if="commande">
    <div class="max-w-7xl mx-auto no-print">
      <div class="mb-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <router-link to="/ventes/commandes" class="text-sm text-teal-600 hover:underline">← Commandes</router-link>
          <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ commande.numero }}</h1>
          <p class="text-gray-600">
            {{ commande.client?.nom || 'Sans client' }} ·
            {{ commande.type === 'export' ? 'Export' : 'Local' }}
            <span v-if="commande.incoterm"> · {{ commande.incoterm }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <select v-model="newStatut" @change="changeStatut" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option v-for="s in processus" :key="s.key" :value="s.key">{{ s.label }}</option>
          </select>
          <button @click="printCommande" type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimer
          </button>
          <button
            v-if="!commande.bon_livraison"
            @click="transformerEnBl"
            :disabled="creatingBl"
            type="button"
            class="px-4 py-2 border border-teal-600 text-teal-700 rounded-lg text-sm bg-white hover:bg-teal-50 disabled:opacity-50"
          >
            {{ creatingBl ? 'Transformation…' : 'Transformer en BL' }}
          </button>
          <router-link
            v-else
            :to="'/ventes/locales/bons-livraison'"
            class="px-4 py-2 border border-emerald-600 text-emerald-700 rounded-lg text-sm bg-white hover:bg-emerald-50"
          >
            Voir BL {{ commande.bon_livraison.numero }}
          </router-link>
          <button @click="save" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700 disabled:opacity-50">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 overflow-x-auto">
        <div class="flex items-center gap-1 min-w-max">
          <template v-for="(step, i) in processus" :key="step.key">
            <span
              class="px-3 py-1.5 rounded-lg text-xs font-medium"
              :class="stepIndex(commande.statut) >= i ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-500'"
            >{{ step.label }}</span>
            <span v-if="i < processus.length - 1" class="text-gray-300 px-1">→</span>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Total HT</p><p class="text-lg font-bold">{{ formatMoney(commande.total_ht) }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">TVA</p><p class="text-lg font-bold">{{ formatMoney(commande.total_tva) }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Total TTC</p><p class="text-lg font-bold text-teal-700">{{ formatMoney(commande.total_ttc) }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Marge estimée</p><p class="text-lg font-bold">{{ formatMoney(commande.marge_estimee) }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Livrée</p><p class="text-lg font-bold">{{ commande.quantite_livree || 0 }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Restante</p><p class="text-lg font-bold">{{ commande.quantite_restante || 0 }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Facturée</p><p class="text-lg font-bold">{{ formatMoney(form.montant_facture) }}</p></div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm"><p class="text-xs text-gray-500">Solde</p><p class="text-lg font-bold">{{ formatMoney((form.montant_facture || 0) - (form.montant_regle || 0)) }}</p></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-lg mb-4">Informations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-gray-500">Date</label>
                <input type="date" v-model="form.date_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Client</label>
                <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option :value="null">—</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Référence client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Commercial</label>
                <input v-model="form.commercial" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Type</label>
                <input :value="form.type === 'export' ? 'Export' : 'Local'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Devise</label>
                <input :value="form.devise" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Paiement</label>
                <input :value="form.mode_paiement || '—'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Date souhaitée</label>
                <input type="date" v-model="form.date_souhaitee" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Priorité</label>
                <select v-model="form.priorite" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option value="basse">Basse</option>
                  <option value="normale">Normale</option>
                  <option value="haute">Haute</option>
                  <option value="urgente">Urgente</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Facturée</label>
                <input type="number" step="0.01" v-model.number="form.montant_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Réglée</label>
                <input type="number" step="0.01" v-model.number="form.montant_regle" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Adresse</label>
                <textarea v-model="form.adresse" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
              <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Observation</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>
          </section>

          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
              <h2 class="font-semibold text-lg">Lignes</h2>
              <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                  <button
                    type="button"
                    @click="showColumnPicker = !showColumnPicker"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 inline-flex items-center gap-2"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14" />
                    </svg>
                    Colonnes
                  </button>
                  <div
                    v-if="showColumnPicker"
                    class="absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-lg z-20 p-3"
                    @click.stop
                  >
                    <div class="flex items-center justify-between mb-2">
                      <p class="text-sm font-semibold text-gray-800">Paramètres colonnes</p>
                      <button type="button" class="text-xs text-teal-600" @click="resetColumns">Réinit.</button>
                    </div>
                    <div class="max-h-64 overflow-y-auto space-y-1">
                      <label
                        v-for="col in allColumns"
                        :key="col.key"
                        class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm"
                      >
                        <input type="checkbox" v-model="visibleColumns" :value="col.key" class="rounded text-teal-600" />
                        <span>{{ col.label }}</span>
                      </label>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Ces colonnes s’appliquent à l’affichage et à l’impression.</p>
                  </div>
                </div>
                <button @click="addLigne" type="button" class="px-3 py-1.5 text-sm font-medium text-teal-600 hover:bg-teal-50 rounded-lg">+ Ligne</button>
              </div>
            </div>

            <div class="overflow-x-auto border border-gray-100 rounded-lg">
              <table class="w-full text-sm min-w-max">
                <thead class="bg-gray-50">
                  <tr class="text-left text-gray-500 border-b border-gray-100">
                    <th
                      v-for="col in activeColumns"
                      :key="col.key"
                      class="py-2.5 px-3 whitespace-nowrap text-xs font-medium uppercase tracking-wide"
                    >{{ col.label }}</th>
                    <th class="py-2.5 px-3 w-10"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!form.lignes?.length">
                    <td :colspan="activeColumns.length + 1" class="py-8 text-center text-gray-400">Aucune ligne</td>
                  </tr>
                  <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-b border-gray-50 hover:bg-gray-50/60">
                    <td
                      v-for="col in activeColumns"
                      :key="col.key"
                      class="py-2 px-3 whitespace-nowrap align-middle"
                    >
                      <template v-if="col.key === 'article'">
                        <select v-model="ligne.article_id" @change="fillArticle(ligne)" class="border border-gray-300 rounded px-2 py-1 w-24 text-xs bg-white">
                          <option :value="null">—</option>
                          <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                        </select>
                      </template>
                      <template v-else-if="col.key === 'date_production'">
                        <input type="date" v-model="ligne.date_production" class="border border-gray-300 rounded px-2 py-1 w-32 text-xs" />
                      </template>
                      <template v-else-if="col.editable">
                        <input
                          v-if="col.input === 'number'"
                          type="number"
                          :step="col.step || '0.01'"
                          v-model.number="ligne[col.field]"
                          class="border border-gray-300 rounded px-2 py-1 w-20 text-xs"
                        />
                        <input
                          v-else
                          v-model="ligne[col.field]"
                          class="border border-gray-300 rounded px-2 py-1 w-28 text-xs"
                        />
                      </template>
                      <template v-else>
                        <span class="text-xs text-gray-800">{{ cellValue(ligne, col) }}</span>
                      </template>
                    </td>
                    <td class="py-2 px-3 text-right">
                      <button type="button" @click="form.lignes.splice(idx,1)" class="text-red-500 hover:text-red-700 text-lg leading-none" title="Supprimer">×</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="space-y-6">
          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <h2 class="font-semibold">Logistique</h2>
              <div class="flex gap-2">
                <router-link to="/ventes/commandes/logistique" class="text-xs text-gray-500 hover:text-teal-600">Voir tout</router-link>
                <button @click="createLivraison" :disabled="creatingLiv" class="text-sm text-teal-600">+ Livraison</button>
              </div>
            </div>
            <div v-if="!commande.livraisons?.length" class="text-sm text-gray-500">Aucune livraison</div>
            <ul class="space-y-2">
              <li v-for="l in commande.livraisons" :key="l.id" class="text-sm border border-gray-100 rounded-lg p-3">
                <p class="font-medium text-teal-700">{{ l.numero }}</p>
                <p class="text-gray-500">{{ l.transporteur || '—' }} · {{ labelLiv(l.statut) }}</p>
              </li>
            </ul>
          </section>

          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold mb-3">Pièces jointes</h2>
            <input type="file" @change="uploadPiece" class="text-sm w-full mb-3" />
            <ul class="space-y-1">
              <li v-for="p in commande.pieces_jointes || []" :key="p.id" class="text-sm text-gray-700 truncate">
                {{ p.nom_fichier }}
              </li>
            </ul>
            <p v-if="!(commande.pieces_jointes || []).length" class="text-sm text-gray-500">Aucun fichier</p>
          </section>
        </div>
      </div>

      <p v-if="error" class="text-red-600 text-sm mt-4">{{ error }}</p>
      <p v-if="message" class="text-teal-600 text-sm mt-4">{{ message }}</p>
    </div>

    <!-- Print layout (only visible when printing) -->
    <div class="print-only">
      <div class="print-header">
        <h1>Commande {{ commande.numero }}</h1>
        <p>
          {{ clientName }} · {{ form.type === 'export' ? 'Export' : 'Local' }}
          <span v-if="form.incoterm"> · {{ form.incoterm }}</span>
          · {{ form.devise }}
        </p>
        <div class="print-meta">
          <div><strong>Date :</strong> {{ form.date_commande || '—' }}</div>
          <div><strong>Réf. client :</strong> {{ form.reference_client || '—' }}</div>
          <div><strong>Commercial :</strong> {{ form.commercial || '—' }}</div>
          <div><strong>Date souhaitée :</strong> {{ form.date_souhaitee || '—' }}</div>
          <div><strong>Paiement :</strong> {{ form.mode_paiement || '—' }}</div>
        </div>
      </div>

      <table class="print-table">
        <thead>
          <tr>
            <th v-for="col in activeColumns" :key="'p-'+col.key">{{ col.label }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(ligne, idx) in form.lignes" :key="'pl-'+idx">
            <td v-for="col in activeColumns" :key="'pc-'+col.key">{{ cellValue(ligne, col) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="print-totals">
        <div>HT : <strong>{{ formatMoney(totalHt) }}</strong></div>
        <div>TVA : <strong>{{ formatMoney(totalTva) }}</strong></div>
        <div>TTC : <strong>{{ formatMoney(totalTtc) }} {{ form.devise }}</strong></div>
      </div>
      <p v-if="form.observations" class="print-obs"><strong>Observation :</strong> {{ form.observations }}</p>
    </div>
  </div>
  <div v-else class="p-10 text-center text-gray-500">Chargement…</div>
</template>

<script>
import axios from 'axios';

const PROCESSUS = [
  { key: 'brouillon', label: 'Brouillon' },
  { key: 'en_attente', label: 'En attente' },
  { key: 'confirmee', label: 'Confirmée' },
  { key: 'en_preparation', label: 'En préparation' },
  { key: 'en_production', label: 'En production' },
  { key: 'partiellement_livree', label: 'Partiellement livrée' },
  { key: 'livree', label: 'Livrée' },
  { key: 'cloturee', label: 'Clôturée' },
];

const LIV_LABELS = {
  a_preparer: 'À préparer', en_preparation: 'En préparation', pret: 'Prêt',
  charge: 'Chargé', livre: 'Livré', expedie: 'Expédié', annule: 'Annulé',
};

const ALL_COLUMNS = [
  { key: 'article', label: 'Article', field: 'article_id', editable: true },
  { key: 'designation', label: 'Désignation', field: 'designation', editable: true },
  { key: 'calibre', label: 'Calibre', field: 'calibre' },
  { key: 'type_emballage_primaire', label: 'Emb. primaire', field: 'type_emballage_primaire' },
  { key: 'reference_emballage', label: 'Réf. emballage', field: 'reference_emballage' },
  { key: 'type_emballage_secondaire', label: 'Emb. secondaire', field: 'type_emballage_secondaire' },
  { key: 'unites_par_colis', label: 'Unités/colis', field: 'unites_par_colis' },
  { key: 'colis_par_palette', label: 'Colis/palette', field: 'colis_par_palette' },
  { key: 'nombre_total_par_palette', label: 'Total/palette', field: 'nombre_total_par_palette' },
  { key: 'poids_net_egoutte', label: 'Poids net ég.', field: 'poids_net_egoutte' },
  { key: 'quantite', label: 'Qté', field: 'quantite', editable: true, input: 'number', step: '0.001' },
  { key: 'unite', label: 'Unité', field: 'unite', editable: true },
  { key: 'prix', label: 'Prix', field: 'prix', editable: true, input: 'number', step: '0.01' },
  { key: 'date_production', label: 'Date de production', field: 'date_production', editable: true, input: 'date' },
  { key: 'lot', label: 'N° de lot', field: 'lot', editable: true },
  { key: 'tva_taux', label: 'TVA %', field: 'tva_taux', editable: true, input: 'number', step: '0.01' },
  { key: 'ht', label: 'HT', field: 'ht', computed: true },
  { key: 'disponible', label: 'Disponible', field: 'quantite_disponible' },
  { key: 'reservee', label: 'Réservée', field: 'quantite_reservee' },
  { key: 'a_produire', label: 'À produire', field: 'quantite_a_produire' },
  { key: 'livree', label: 'Livrée', field: 'quantite_livree' },
];

const DEFAULT_COLUMNS = [
  'article', 'designation', 'calibre', 'reference_emballage',
  'quantite', 'unite', 'prix', 'date_production', 'lot', 'tva_taux', 'ht',
];

const COLUMNS_STORAGE_KEY = 'commande_detail_visible_columns';

export default {
  name: 'CommandeDetail',
  data() {
    return {
      commande: null,
      clients: [],
      articles: [],
      form: {},
      newStatut: '',
      saving: false,
      creatingLiv: false,
      creatingBl: false,
      error: '',
      message: '',
      processus: PROCESSUS,
      incoterms: ['EXW', 'FCA', 'FOB', 'CIF', 'CFR', 'DAP', 'DDP', 'CPT', 'CIP'],
      allColumns: ALL_COLUMNS,
      visibleColumns: [...DEFAULT_COLUMNS],
      showColumnPicker: false,
    };
  },
  computed: {
    activeColumns() {
      return this.allColumns.filter(c => this.visibleColumns.includes(c.key));
    },
    clientName() {
      const c = this.clients.find(x => x.id === this.form.client_id);
      return c?.nom || this.commande?.client?.nom || '—';
    },
    totalHt() {
      return (this.form.lignes || []).reduce((s, l) => s + this.ligneHt(l), 0);
    },
    totalTva() {
      return (this.form.lignes || []).reduce((s, l) => s + this.ligneTva(l), 0);
    },
    totalTtc() {
      return this.totalHt + this.totalTva;
    },
  },
  watch: {
    visibleColumns: {
      deep: true,
      handler(val) {
        try {
          localStorage.setItem(COLUMNS_STORAGE_KEY, JSON.stringify(val));
        } catch { /* ignore */ }
      },
    },
  },
  mounted() {
    this.loadColumnPrefs();
    this.load();
    this.loadLookups();
    document.addEventListener('click', this.closeColumnPicker);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeColumnPicker);
  },
  methods: {
    loadColumnPrefs() {
      try {
        const raw = localStorage.getItem(COLUMNS_STORAGE_KEY);
        if (!raw) return;
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) {
          this.visibleColumns = parsed.filter(k => ALL_COLUMNS.some(c => c.key === k));
        }
      } catch { /* ignore */ }
    },
    resetColumns() {
      this.visibleColumns = [...DEFAULT_COLUMNS];
    },
    closeColumnPicker(e) {
      if (!this.showColumnPicker) return;
      if (e?.target?.closest?.('.relative')) return;
      this.showColumnPicker = false;
    },
    stepIndex(s) {
      const i = PROCESSUS.findIndex(p => p.key === s);
      return i < 0 ? 0 : i;
    },
    labelLiv(s) { return LIV_LABELS[s] || s; },
    articleLabel(id) {
      const a = this.articles.find(x => x.id === id);
      return a ? a.code_article : '—';
    },
    ligneHt(l) {
      const brut = (Number(l.quantite) || 0) * (Number(l.prix) || 0);
      return Math.round(brut * (1 - (Number(l.remise) || 0) / 100) * 100) / 100;
    },
    ligneTva(l) {
      return Math.round(this.ligneHt(l) * ((Number(l.tva_taux) || 0) / 100) * 100) / 100;
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    cellValue(ligne, col) {
      if (col.key === 'article') return this.articleLabel(ligne.article_id);
      if (col.key === 'ht' || col.computed) return this.formatMoney(this.ligneHt(ligne));
      if (col.key === 'prix') return this.formatMoney(ligne.prix);
      const val = ligne[col.field];
      if (val === null || val === undefined || val === '') return '—';
      return val;
    },
    printCommande() {
      this.showColumnPicker = false;
      this.$nextTick(() => window.print());
    },
    async load() {
      const { data } = await axios.get(`/api/commandes/${this.$route.params.id}`);
      this.commande = data;
      this.newStatut = data.statut;
      this.form = {
        date_commande: data.date_commande?.slice?.(0, 10) || data.date_commande,
        client_id: data.client_id,
        reference_client: data.reference_client || '',
        commercial: data.commercial || '',
        type: data.type || 'local',
        devise: data.devise || 'MAD',
        mode_paiement: data.mode_paiement || '',
        incoterm: data.incoterm || '',
        destination: data.destination || '',
        adresse: data.adresse || '',
        date_souhaitee: data.date_souhaitee?.slice?.(0, 10) || data.date_souhaitee || '',
        priorite: data.priorite || 'normale',
        observations: data.observations || '',
        montant_facture: Number(data.montant_facture || 0),
        montant_regle: Number(data.montant_regle || 0),
        lignes: (data.lignes || []).map(l => ({
          article_id: l.article_id,
          designation: l.designation || '',
          calibre: l.calibre || '',
          type_emballage_primaire: l.type_emballage_primaire || '',
          reference_emballage: l.reference_emballage || '',
          type_emballage_secondaire: l.type_emballage_secondaire || '',
          unites_par_colis: l.unites_par_colis,
          colis_par_palette: l.colis_par_palette,
          nombre_total_par_palette: l.nombre_total_par_palette,
          poids_net_egoutte: l.poids_net_egoutte,
          quantite: Number(l.quantite),
          unite: l.unite || '',
          prix: Number(l.prix),
          remise: Number(l.remise || 0),
          tva_taux: Number(l.tva_taux ?? 20),
          date_production: l.date_production ? String(l.date_production).slice(0, 10) : '',
          lot: l.lot || '',
          cout_unitaire: Number(l.cout_unitaire || 0),
          quantite_disponible: Number(l.quantite_disponible || 0),
          quantite_reservee: Number(l.quantite_reservee || 0),
          quantite_a_produire: Number(l.quantite_a_produire || 0),
          quantite_livree: Number(l.quantite_livree || 0),
        })),
      };
      if (!this.form.lignes.length) this.addLigne();
    },
    async loadLookups() {
      const [c, a] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 500 } }),
        axios.get('/api/articles', { params: { per_page: 500 } }),
      ]);
      this.clients = c.data.data || c.data;
      this.articles = a.data.data || a.data;
    },
    addLigne() {
      this.form.lignes.push({
        article_id: null, designation: '', calibre: '', type_emballage_primaire: '',
        reference_emballage: '', type_emballage_secondaire: '',
        unites_par_colis: null, colis_par_palette: null, nombre_total_par_palette: null,
        poids_net_egoutte: null, quantite: 1, unite: 'kg', prix: 0,
        remise: 0, tva_taux: 20, date_production: '', lot: '',
        cout_unitaire: 0, quantite_disponible: 0,
        quantite_reservee: 0, quantite_a_produire: 0, quantite_livree: 0,
      });
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (!a) return;
      ligne.designation = a.designation || '';
      ligne.calibre = a.calibre || '';
      ligne.type_emballage_primaire = a.type_emballage_primaire || '';
      ligne.reference_emballage = a.type_palette || '';
      ligne.type_emballage_secondaire = a.type_emballage_secondaire || '';
      ligne.unites_par_colis = a.unites_par_colis ?? null;
      ligne.colis_par_palette = a.colis_par_palette ?? null;
      ligne.nombre_total_par_palette = a.nombre_total_par_palette ?? null;
      ligne.poids_net_egoutte = a.poids_net_egoutte ?? null;
      ligne.date_production = a.date_production ? String(a.date_production).slice(0, 10) : (ligne.date_production || '');
      ligne.lot = a.lot || ligne.lot || '';
      ligne.unite = a.unite_facturation || ligne.unite || 'kg';
      ligne.prix = Number(a.prix_vente || 0);
      ligne.cout_unitaire = Number(a.prix_achat || a.prix_achat_matiere || 0);
      ligne.tva_taux = Number(a.taux_tva ?? ligne.tva_taux ?? 20);
    },
    async save() {
      this.saving = true;
      this.error = '';
      this.message = '';
      try {
        const { data } = await axios.put(`/api/commandes/${this.commande.id}`, this.form);
        this.commande = data;
        this.message = 'Commande enregistrée';
        this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
    async changeStatut() {
      try {
        await axios.post(`/api/commandes/${this.commande.id}/statut`, { statut: this.newStatut });
        this.commande.statut = this.newStatut;
        this.message = 'Statut mis à jour';
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur statut';
      }
    },
    async createLivraison() {
      this.creatingLiv = true;
      try {
        await axios.post('/api/livraisons', {
          commande_id: this.commande.id,
          type_livraison: this.commande.type === 'export' ? 'export' : 'locale',
          adresse: this.commande.adresse,
          quantite_a_livrer: this.commande.quantite_restante || 0,
          date_prevue: this.commande.date_souhaitee?.slice?.(0, 10) || null,
          statut: 'a_preparer',
        });
        this.message = 'Livraison créée';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur création livraison';
      } finally {
        this.creatingLiv = false;
      }
    },
    async transformerEnBl() {
      if (!confirm('Transformer cette commande en bon de livraison ? Elle disparaîtra de la liste des commandes.')) {
        return;
      }
      this.creatingBl = true;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/bons-livraison/from-commande/${this.commande.id}`, {
          date_heure_livraison: this.form.date_souhaitee ? `${this.form.date_souhaitee}T00:00` : null,
          informations_additionnelles: this.form.observations || null,
        });
        this.message = `Bon de livraison ${data.numero} créé`;
        this.$router.push('/ventes/locales/bons-livraison');
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur transformation en BL';
      } finally {
        this.creatingBl = false;
      }
    },
    async uploadPiece(e) {
      const file = e.target.files?.[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('fichier', file);
      try {
        await axios.post(`/api/commandes/${this.commande.id}/pieces`, fd);
        this.message = 'Fichier ajouté';
        await this.load();
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur upload';
      }
      e.target.value = '';
    },
  },
};
</script>

<style scoped>
.print-only {
  display: none;
}

@media print {
  .no-print {
    display: none !important;
  }

  .print-only {
    display: block !important;
    color: #111;
    font-family: Georgia, 'Times New Roman', serif;
    padding: 12px;
  }

  .print-header h1 {
    margin: 0 0 4px;
    font-size: 22px;
  }

  .print-header p {
    margin: 0 0 12px;
    color: #444;
  }

  .print-meta {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px 16px;
    margin-bottom: 18px;
    font-size: 12px;
  }

  .print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
  }

  .print-table th,
  .print-table td {
    border: 1px solid #ccc;
    padding: 6px 8px;
    text-align: left;
  }

  .print-table th {
    background: #f3f4f6;
    font-weight: 600;
  }

  .print-totals {
    margin-top: 16px;
    display: flex;
    gap: 24px;
    font-size: 13px;
    justify-content: flex-end;
  }

  .print-obs {
    margin-top: 16px;
    font-size: 12px;
  }
}
</style>
