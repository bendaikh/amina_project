<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Facturation locale</h1>
          <p class="text-gray-600 text-sm mt-1">
            Factures ventes locales — depuis bon de livraison ou saisie directe
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="openFromBl" class="px-5 py-2.5 border border-teal-600 text-teal-700 rounded-lg hover:bg-teal-50 font-medium">
            Depuis un BL
          </button>
          <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
            + Nouvelle facture
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="N° facture, client, BL, commande…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option value="brouillon">Brouillon</option>
          <option value="validee">Validée</option>
          <option value="payee">Payée</option>
          <option value="annulee">Annulée</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° Facture</th>
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">BL</th>
              <th class="py-3 px-4">Commande</th>
              <th class="py-3 px-4">HT</th>
              <th class="py-3 px-4">TTC</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune facture locale</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_facture) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero_bl || item.bon_livraison?.numero || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero_commande || item.commande?.numero || '—' }}</td>
              <td class="py-3 px-4">{{ formatMoney(item.total_ht) }} {{ item.devise }}</td>
              <td class="py-3 px-4 font-medium">{{ formatMoney(item.total_ttc) }} {{ item.devise }}</td>
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

      <!-- Create from BL -->
      <div v-if="showFromBl" class="app-modal-overlay" @click.self="showFromBl=false">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Facture depuis un bon de livraison</h2>
            <button type="button" class="app-modal__close" @click="showFromBl=false">&times;</button>
          </div>
          <div class="app-modal__body space-y-4">
            <div>
              <label class="text-sm text-gray-600">Bon de livraison</label>
              <select v-model="blForm.bon_livraison_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                <option :value="null">Sélectionner…</option>
                <option v-for="b in blDispo" :key="b.id" :value="b.id">
                  {{ b.numero }} — {{ b.client?.nom || 'Sans client' }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Seuls les BL non encore facturés sont listés.</p>
            </div>
            <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="showFromBl=false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
            <button type="button" @click="createFromBl" :disabled="saving || !blForm.bon_livraison_id" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Création…' : 'Créer la facture' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Detail / Edit -->
      <div v-if="showModal && form" class="app-modal-overlay" @click.self="closeDetail">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">{{ form.id ? `Facture ${form.numero}` : 'Nouvelle facture locale' }}</h2>
              <p class="app-modal__subtitle" v-if="form.numero_bl">Issu du BL {{ form.numero_bl }}</p>
            </div>
            <button type="button" class="app-modal__close" @click="closeDetail">&times;</button>
          </div>

          <div class="app-modal__body">
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">N° facture</label>
                <input :value="form.numero || 'Auto'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 font-semibold text-teal-700" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date</label>
                <input type="date" v-model="form.date_facture" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Client</label>
                <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Réf. client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">N° BL</label>
                <input v-model="form.numero_bl" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">N° commande</label>
                <input v-model="form.numero_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Devise</label>
                <input v-model="form.devise" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Échéance</label>
                <input type="date" v-model="form.echeance" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Conditions paiement</label>
                <input v-model="form.conditions_paiement" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option value="brouillon">Brouillon</option>
                  <option value="validee">Validée</option>
                  <option value="payee">Payée</option>
                  <option value="annulee">Annulée</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Observations</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="mb-3 flex items-center justify-between">
              <h3 class="font-semibold text-gray-900">Lignes</h3>
              <button type="button" @click="addLigne" class="text-sm text-teal-600 hover:underline">+ Ligne</button>
            </div>
            <div class="overflow-x-auto border border-gray-200 rounded-lg">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                  <tr>
                    <th class="py-2 px-2 text-left">Réf.</th>
                    <th class="py-2 px-2 text-left">Désignation</th>
                    <th class="py-2 px-2 text-right">Qté</th>
                    <th class="py-2 px-2 text-right">PU</th>
                    <th class="py-2 px-2 text-right">TVA %</th>
                    <th class="py-2 px-2 text-right">HT</th>
                    <th class="py-2 px-2 text-right">TTC</th>
                    <th class="py-2 px-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-t border-gray-100">
                    <td class="py-1.5 px-2">
                      <input v-model="ligne.ref_article" class="w-24 border rounded px-1.5 py-1" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input v-model="ligne.designation" class="w-full min-w-[140px] border rounded px-1.5 py-1" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.001" v-model.number="ligne.quantite" @input="recalcLigne(ligne)" class="w-20 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.01" v-model.number="ligne.prix_unitaire" @input="recalcLigne(ligne)" class="w-24 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2">
                      <input type="number" step="0.01" v-model.number="ligne.tva_taux" @input="recalcLigne(ligne)" class="w-16 border rounded px-1.5 py-1 text-right" />
                    </td>
                    <td class="py-1.5 px-2 text-right whitespace-nowrap">{{ formatMoney(ligne.montant_ht) }}</td>
                    <td class="py-1.5 px-2 text-right whitespace-nowrap font-medium">{{ formatMoney(ligne.montant_ttc) }}</td>
                    <td class="py-1.5 px-2 text-center">
                      <button type="button" @click="form.lignes.splice(idx,1)" class="text-red-500">&times;</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-6 text-sm">
              <div>Total HT : <strong>{{ formatMoney(totals.ht) }} {{ form.devise }}</strong></div>
              <div>Total TVA : <strong>{{ formatMoney(totals.tva) }} {{ form.devise }}</strong></div>
              <div>Total TTC : <strong class="text-teal-700">{{ formatMoney(totals.ttc) }} {{ form.devise }}</strong></div>
            </div>

            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
            <p v-if="message" class="text-teal-700 text-sm mt-3">{{ message }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="closeDetail" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Fermer</button>
            <button type="button" @click="save" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

function emptyLigne() {
  return {
    article_id: null,
    ref_article: '',
    designation: '',
    calibre: '',
    emballage: '',
    numero_lot: '',
    quantite: 0,
    unite: 'kg',
    prix_unitaire: 0,
    tva_taux: 20,
    montant_ht: 0,
    montant_tva: 0,
    montant_ttc: 0,
  };
}

export default {
  name: 'FacturationLocale',
  data() {
    return {
      items: [],
      clients: [],
      blDispo: [],
      loading: false,
      saving: false,
      search: '',
      statut: '',
      timer: null,
      showModal: false,
      showFromBl: false,
      form: null,
      blForm: { bon_livraison_id: null },
      error: '',
      message: '',
    };
  },
  computed: {
    totals() {
      const lignes = this.form?.lignes || [];
      return {
        ht: lignes.reduce((s, l) => s + (Number(l.montant_ht) || 0), 0),
        tva: lignes.reduce((s, l) => s + (Number(l.montant_tva) || 0), 0),
        ttc: lignes.reduce((s, l) => s + (Number(l.montant_ttc) || 0), 0),
      };
    },
  },
  mounted() {
    this.load();
    this.loadClients();
    if (this.$route.query.open) {
      this.openById(Number(this.$route.query.open));
    }
  },
  methods: {
    labelStatut(s) {
      return ({ brouillon: 'Brouillon', validee: 'Validée', payee: 'Payée', annulee: 'Annulée' })[s] || s;
    },
    badgeClass(s) {
      return ({
        brouillon: 'bg-slate-100 text-slate-700',
        validee: 'bg-blue-100 text-blue-700',
        payee: 'bg-green-100 text-green-700',
        annulee: 'bg-red-100 text-red-700',
      })[s] || 'bg-gray-100 text-gray-700';
    },
    formatDate(d) {
      return d ? String(d).slice(0, 10) : '—';
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    recalcLigne(ligne) {
      const qte = Number(ligne.quantite || 0);
      const prix = Number(ligne.prix_unitaire || 0);
      const tva = Number(ligne.tva_taux || 0);
      const ht = Math.round(qte * prix * 100) / 100;
      const mtva = Math.round(ht * (tva / 100) * 100) / 100;
      ligne.montant_ht = ht;
      ligne.montant_tva = mtva;
      ligne.montant_ttc = Math.round((ht + mtva) * 100) / 100;
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/factures-locales', {
          params: { search: this.search, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadClients() {
      const { data } = await axios.get('/api/clients', { params: { per_page: 500, statut: 'client' } });
      this.clients = data.data || data;
    },
    emptyForm() {
      return {
        id: null,
        numero: '',
        date_facture: new Date().toISOString().slice(0, 10),
        client_id: null,
        reference_client: '',
        numero_bl: '',
        numero_commande: '',
        devise: 'MAD',
        conditions_paiement: '',
        mode_paiement: '',
        echeance: '',
        statut: 'brouillon',
        observations: '',
        lignes: [emptyLigne()],
      };
    },
    openCreate() {
      this.error = '';
      this.message = '';
      this.form = this.emptyForm();
      this.showModal = true;
    },
    async openFromBl() {
      this.error = '';
      this.blForm.bon_livraison_id = null;
      try {
        const { data } = await axios.get('/api/bons-livraison', { params: { per_page: 200 } });
        const all = data.data || data;
        this.blDispo = all.filter(b => !b.facture_locale);
      } catch {
        this.blDispo = [];
      }
      this.showFromBl = true;
    },
    async createFromBl() {
      if (!this.blForm.bon_livraison_id) return;
      this.saving = true;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/factures-locales/from-bl/${this.blForm.bon_livraison_id}`);
        this.showFromBl = false;
        await this.load();
        this.openDetail(data);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur création facture';
      } finally {
        this.saving = false;
      }
    },
    async openById(id) {
      if (!id) return;
      try {
        const { data } = await axios.get(`/api/factures-locales/${id}`);
        this.openDetail(data);
      } catch {
        /* ignore */
      }
    },
    openDetail(item) {
      this.error = '';
      this.message = '';
      this.form = {
        ...item,
        date_facture: item.date_facture?.slice?.(0, 10) || item.date_facture || '',
        echeance: item.echeance?.slice?.(0, 10) || item.echeance || '',
        lignes: (item.lignes || []).map(l => ({ ...emptyLigne(), ...l })),
      };
      if (!this.form.lignes.length) this.form.lignes = [emptyLigne()];
      this.showModal = true;
    },
    closeDetail() {
      this.showModal = false;
      this.form = null;
    },
    addLigne() {
      this.form.lignes.push(emptyLigne());
    },
    async save() {
      if (!this.form) return;
      this.saving = true;
      this.error = '';
      this.message = '';
      this.form.lignes.forEach(l => this.recalcLigne(l));
      try {
        const payload = {
          date_facture: this.form.date_facture,
          client_id: this.form.client_id,
          reference_client: this.form.reference_client,
          numero_bl: this.form.numero_bl,
          numero_commande: this.form.numero_commande,
          devise: this.form.devise,
          conditions_paiement: this.form.conditions_paiement,
          mode_paiement: this.form.mode_paiement,
          echeance: this.form.echeance || null,
          statut: this.form.statut,
          observations: this.form.observations,
          lignes: this.form.lignes,
        };
        let data;
        if (this.form.id) {
          ({ data } = await axios.put(`/api/factures-locales/${this.form.id}`, payload));
        } else {
          ({ data } = await axios.post('/api/factures-locales', payload));
        }
        this.openDetail(data);
        this.message = 'Facture enregistrée';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
