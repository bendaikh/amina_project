<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="app-page-header mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Factures fournisseurs</h1>
          <p class="text-gray-600 text-sm mt-1">Création depuis commande, réception ou saisie directe · plusieurs réceptions par facture</p>
        </div>
        <button @click="openCreate()" type="button" class="app-page-header__action px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle facture
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 app-toolbar">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N° facture, fournisseur…"
          class="app-toolbar__search flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="origine" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Toutes origines</option>
          <option value="commande">Depuis commande</option>
          <option value="reception">Depuis réception</option>
          <option value="directe">Saisie directe</option>
        </select>
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous statuts</option>
          <option value="brouillon">Brouillon</option>
          <option value="validee">Validée</option>
          <option value="echeance">Échéance</option>
          <option value="payee">Payée</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N°</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Fournisseur</th>
              <th class="py-3 px-4">Origine</th>
              <th class="py-3 px-4">Réceptions</th>
              <th class="py-3 px-4">Échéance</th>
              <th class="py-3 px-4">TTC</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune facture</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_facture) }}</td>
              <td class="py-3 px-4">{{ item.fournisseur?.nom || '—' }}</td>
              <td class="py-3 px-4 capitalize">{{ item.origine }}</td>
              <td class="py-3 px-4">{{ item.receptions?.length || 0 }}</td>
              <td class="py-3 px-4">{{ formatDate(item.echeance) }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 capitalize">{{ item.statut }}</span>
              </td>
              <td class="py-3 px-4 text-right">
                <button @click="openEdit(item)" class="text-teal-600 hover:underline font-medium text-sm">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="app-modal-overlay" @click.self="showModal=false">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ editingId ? 'Facture ' + form.numero : 'Nouvelle facture fournisseur' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
            <!-- Origine -->
            <div v-if="!editingId" class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Mode de création</p>
              <div class="app-form-grid app-form-grid--3">
                <button
                  v-for="o in origines"
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

              <div v-if="form.origine === 'commande'" class="mt-4">
                <label class="block text-sm text-gray-600 mb-1">Commande / achat</label>
                <select v-model="form.achat_id" @change="onAchatChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="a in achats" :key="a.id" :value="a.id">{{ a.numero }} — {{ a.fournisseur?.nom }}</option>
                </select>
              </div>

              <div v-if="form.origine === 'reception'" class="mt-4">
                <label class="block text-sm text-gray-600 mb-1">Réceptions (plusieurs possibles)</label>
                <div class="border border-gray-300 rounded-lg p-3 max-h-40 overflow-y-auto space-y-2 bg-white">
                  <label v-for="r in allReceptions" :key="r.id" class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" :value="r.id" v-model="form.reception_ids" @change="onReceptionsChange" />
                    <span>{{ r.numero }} — {{ r.achat?.numero }} ({{ r.achat?.fournisseur?.nom || '—' }})</span>
                  </label>
                  <p v-if="!allReceptions.length" class="text-gray-500 text-sm">Aucune réception disponible</p>
                </div>
              </div>
            </div>

            <!-- En-tête -->
            <div class="achat-header-grid mb-5">
              <div>
                <label class="block text-sm text-gray-600 mb-1">Fournisseur</label>
                <select v-model="form.fournisseur_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Date facture</label>
                <input type="date" v-model="form.date_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Référence</label>
                <input v-model="form.reference" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° facture fournisseur" />
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
                <label class="block text-sm text-gray-600 mb-1">Conditions</label>
                <input v-model="form.conditions" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Net 30, etc." />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Mode de paiement</label>
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
                <label class="block text-sm text-gray-600 mb-1">Échéance</label>
                <input type="date" v-model="form.echeance" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm text-gray-600 mb-1">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="brouillon">Brouillon</option>
                  <option value="validee">Validée</option>
                  <option value="echeance">Échéance</option>
                  <option value="payee">Payée</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="block text-sm text-gray-600 mb-1">Observations</label>
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
  name: 'FacturesFournisseurs',
  data() {
    return {
      items: [],
      achats: [],
      fournisseurs: [],
      allReceptions: [],
      loading: false,
      saving: false,
      showModal: false,
      editingId: null,
      search: '',
      origine: '',
      statut: '',
      error: '',
      timer: null,
      origines: [
        { value: 'commande', label: 'Depuis commande', hint: 'Importer les lignes d’un achat' },
        { value: 'reception', label: 'Depuis réception', hint: 'Lier une ou plusieurs réceptions' },
        { value: 'directe', label: 'Saisie directe', hint: 'Créer la facture manuellement' },
      ],
      form: this.emptyForm(),
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
    this.load();
    this.loadLookups();
    if (this.$route.query.achat_id) {
      this.openCreate('commande', Number(this.$route.query.achat_id));
    }
  },
  methods: {
    emptyForm() {
      return {
        numero: '',
        date_facture: new Date().toISOString().slice(0, 10),
        fournisseur_id: null,
        achat_id: null,
        reference: '',
        devise: 'MAD',
        conditions: '',
        mode_paiement: '',
        echeance: '',
        origine: 'directe',
        statut: 'brouillon',
        observations: '',
        reception_ids: [],
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
        const { data } = await axios.get('/api/factures-fournisseurs', {
          params: { search: this.search, origine: this.origine, statut: this.statut },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const [f, a] = await Promise.all([
        axios.get('/api/fournisseurs'),
        axios.get('/api/achats', { params: { per_page: 100 } }),
      ]);
      this.fournisseurs = f.data.data || f.data;
      this.achats = a.data.data || a.data;
      // Collect receptions from achats detail list — fetch each with receptions via parallel limited calls
      const receptions = [];
      await Promise.all(
        this.achats.slice(0, 50).map(async (achat) => {
          try {
            const { data } = await axios.get(`/api/achats/${achat.id}`);
            (data.receptions || []).forEach(r => {
              receptions.push({ ...r, achat: { id: data.id, numero: data.numero, fournisseur: data.fournisseur } });
            });
          } catch (_) { /* skip */ }
        })
      );
      this.allReceptions = receptions;
    },
    openCreate(origine = 'directe', achatId = null) {
      this.editingId = null;
      this.form = this.emptyForm();
      this.form.origine = origine;
      if (achatId) {
        this.form.achat_id = achatId;
        this.onAchatChange();
      }
      this.error = '';
      this.showModal = true;
    },
    async openEdit(item) {
      const { data } = await axios.get(`/api/factures-fournisseurs/${item.id}`);
      this.editingId = data.id;
      this.form = {
        numero: data.numero,
        date_facture: data.date_facture?.substring?.(0, 10) || '',
        fournisseur_id: data.fournisseur_id,
        achat_id: data.achat_id,
        reference: data.reference || '',
        devise: data.devise || 'MAD',
        conditions: data.conditions || '',
        mode_paiement: data.mode_paiement || '',
        echeance: data.echeance?.substring?.(0, 10) || '',
        origine: data.origine || 'directe',
        statut: data.statut || 'brouillon',
        observations: data.observations || '',
        reception_ids: (data.receptions || []).map(r => r.id),
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
      if (o === 'directe') {
        this.form.achat_id = null;
        this.form.reception_ids = [];
      }
    },
    async onAchatChange() {
      if (!this.form.achat_id) return;
      const { data } = await axios.get(`/api/achats/${this.form.achat_id}`);
      this.form.fournisseur_id = data.fournisseur_id;
      this.form.devise = data.devise || this.form.devise;
      this.form.conditions = data.conditions || '';
      this.form.mode_paiement = data.mode_paiement || '';
      this.form.echeance = data.echeance?.substring?.(0, 10) || '';
      this.form.reference = data.reference_commande || '';
      this.form.lignes = (data.lignes || []).map(l => ({
        article_id: l.article_id,
        designation: l.designation || '',
        quantite: Number(l.quantite),
        unite: l.unite,
        prix: Number(l.prix),
        remise: Number(l.remise),
        tva_taux: Number(l.tva_taux),
      }));
      if (!this.form.lignes.length) {
        this.form.lignes = [{ designation: '', quantite: 1, prix: 0, remise: 0, tva_taux: 20 }];
      }
    },
    async onReceptionsChange() {
      if (!this.form.reception_ids.length) return;
      const first = this.allReceptions.find(r => r.id === this.form.reception_ids[0]);
      if (!first?.achat?.id) return;
      const { data } = await axios.get(`/api/achats/${first.achat.id}`);
      this.form.achat_id = data.id;
      this.form.fournisseur_id = data.fournisseur_id;
      this.form.devise = data.devise || this.form.devise;
      this.form.lignes = (data.lignes || []).map(l => ({
        article_id: l.article_id,
        designation: l.designation || '',
        quantite: Number(l.quantite),
        unite: l.unite,
        prix: Number(l.prix),
        remise: Number(l.remise),
        tva_taux: Number(l.tva_taux),
      }));
    },
    addLigne() {
      this.form.lignes.push({ designation: '', quantite: 1, prix: 0, remise: 0, tva_taux: 20 });
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        if (this.editingId) {
          await axios.put(`/api/factures-fournisseurs/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/factures-fournisseurs', this.form);
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
