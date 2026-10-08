<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Finance</p>
          <h1>Lettrage</h1>
          <p>Associez une facture et un règlement, en totalité ou en partie. Chaque lettrage et chaque annulation reste dans l’historique.</p>
        </div>
        <button type="button" class="fin-btn fin-btn--primary" :disabled="!partyId || working" @click="auto">Lettrage automatique</button>
      </header>

      <div class="fin-tabs">
        <button type="button" class="fin-tab" :class="{ 'is-active': sens === 'client' }" @click="sens = 'client'; onSens()">Clients</button>
        <button type="button" class="fin-tab" :class="{ 'is-active': sens === 'fournisseur' }" @click="sens = 'fournisseur'; onSens()">Fournisseurs</button>
      </div>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--2">
          <label class="fin-field">{{ sens === 'client' ? 'Client' : 'Fournisseur' }}
            <select v-model="partyId" @change="load">
              <option :value="null">Sélectionner…</option>
              <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </label>
        </div>
      </section>

      <p v-if="message" class="mb-3 text-sm text-teal-700">{{ message }}</p>
      <p v-if="error" class="mb-3 text-sm text-red-600">{{ error }}</p>

      <div v-if="solde" class="fin-kpis fin-kpis--3">
        <article class="fin-kpi">
          <span class="fin-kpi__label">Solde du tiers</span>
          <strong>{{ dh(solde.solde, solde.devise) }}</strong>
        </article>
      </div>

      <div class="fin-split" style="margin-bottom:1rem">
        <div class="fin-card">
          <div class="fin-card__head"><h2>Factures ouvertes</h2></div>
          <table class="fin-table">
            <thead><tr class="text-left text-gray-500"><th></th><th>N°</th><th>Total</th><th>Reste</th></tr></thead>
            <tbody>
              <tr v-for="f in factures" :key="f.facture_type + f.facture_id" class="border-t">
                <td><input type="radio" name="facture" :value="keyOf(f)" v-model="selectedFacture" /></td>
                <td class="py-2">{{ f.numero }}<div class="text-xs text-gray-400">{{ dateFr(f.echeance) }}</div></td>
                <td>{{ dh(f.total, f.devise) }}</td>
                <td>{{ dh(f.reste, f.devise) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="fin-card">
          <div class="fin-card__head"><h2>Règlements</h2></div>
          <table class="fin-table">
            <thead><tr class="text-left text-gray-500"><th></th><th>N°</th><th>Montant</th><th>Reste</th></tr></thead>
            <tbody>
              <tr v-for="r in reglements" :key="r.id" class="border-t">
                <td><input type="radio" name="reglement" :value="r.id" v-model="selectedReglement" /></td>
                <td class="py-2">{{ r.numero }}<div class="text-xs text-gray-400">{{ dateFr(r.date_reglement) }}</div></td>
                <td>{{ dh(r.montant, r.devise) }}</td>
                <td>{{ dh(r.reste, r.devise) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="fin-panel" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
        <div>
          <p class="text-xs text-gray-500">Montant facture</p>
          <p class="font-semibold">{{ selectedInvoice ? dh(selectedInvoice.total, selectedInvoice.devise) : '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Montant règlement</p>
          <p class="font-semibold">{{ selectedPayment ? dh(selectedPayment.montant, selectedPayment.devise) : '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Reste facture / règlement</p>
          <p class="font-semibold">{{ selectedInvoice ? dh(selectedInvoice.reste) : '—' }} / {{ selectedPayment ? dh(selectedPayment.reste) : '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Écart</p>
          <p class="font-semibold">{{ dh(ecart) }}</p>
        </div>
        <label class="fin-field">Montant à lettrer
          <input type="number" step="0.01" min="0" v-model.number="montant" />
        </label>
        <button type="button" class="fin-btn fin-btn--primary" :disabled="working" @click="match">Lettrer</button>
      </div>

      <div class="fin-card" style="margin-bottom:1rem">
        <div class="fin-card__head"><h2>Affectations</h2></div>
        <table class="fin-table">
          <thead><tr class="text-left text-gray-500 border-t"><th class="py-2 px-4">Règlement</th><th>Facture</th><th>Lettré</th><th>Reste facture</th><th></th></tr></thead>
          <tbody>
            <template v-for="r in reglements" :key="'a' + r.id">
              <tr v-for="f in r.factures" :key="f.id" class="border-t">
                <td class="py-2 px-4">{{ r.numero }}</td>
                <td>{{ f.numero }}</td>
                <td>{{ dh(f.montant, r.devise) }}</td>
                <td>{{ dh(f.reste_facture, r.devise) }}</td>
                <td class="text-right px-4"><button type="button" class="text-red-600" @click="unmatch(f.id)">Délettrer</button></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="fin-card">
        <div class="fin-card__head"><h2>Historique</h2></div>
        <table class="fin-table">
          <thead><tr class="text-left text-gray-500 border-t"><th class="py-2 px-4">Date</th><th>Action</th><th>Facture</th><th>Règlement</th><th>Lettré</th><th>Reste</th><th>Écart</th></tr></thead>
          <tbody>
            <tr v-for="h in historique" :key="h.id" class="border-t">
              <td class="py-2 px-4">{{ dateFr(h.created_at) }}</td>
              <td class="capitalize">{{ h.action }}</td>
              <td>{{ h.facture_numero }}</td>
              <td>{{ h.reglement_numero }}</td>
              <td>{{ dh(h.montant_lettre) }}</td>
              <td>{{ dh(h.reste) }}</td>
              <td>{{ dh(h.ecart) }}</td>
            </tr>
            <tr v-if="!historique.length"><td colspan="7" class="fin-empty"><strong>Aucun lettrage</strong><span>Les actions apparaîtront ici.</span></td></tr>
          </tbody>
        </table>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, apiError } from '../finance/format';

export default {
  name: 'Lettrage',
  data() {
    return {
      sens: 'client',
      parties: [],
      partyId: null,
      factures: [],
      reglements: [],
      historique: [],
      solde: null,
      selectedFacture: null,
      selectedReglement: null,
      montant: 0,
      message: '',
      error: '',
      working: false,
    };
  },
  computed: {
    selectedInvoice() {
      return this.factures.find((f) => this.keyOf(f) === this.selectedFacture) || null;
    },
    selectedPayment() {
      return this.reglements.find((r) => r.id === this.selectedReglement) || null;
    },
    ecart() {
      if (!this.selectedInvoice || !this.selectedPayment) return 0;
      return (Number(this.selectedPayment.reste) || 0) - (Number(this.selectedInvoice.reste) || 0);
    },
  },
  watch: {
    selectedFacture() { this.suggest(); },
    selectedReglement() { this.suggest(); },
  },
  mounted() {
    this.loadParties();
  },
  methods: {
    dh, dateFr,
    keyOf(f) { return `${f.facture_type}:${f.facture_id}`; },
    suggest() {
      if (!this.selectedInvoice || !this.selectedPayment) return;
      this.montant = Math.round(Math.min(this.selectedInvoice.reste, this.selectedPayment.reste) * 100) / 100;
    },
    async loadParties() {
      const { data } = await axios.get('/api/finance/tiers', { params: { sens: this.sens } });
      this.parties = data.data || data || [];
    },
    onSens() {
      this.partyId = null;
      this.factures = [];
      this.reglements = [];
      this.historique = [];
      this.loadParties();
    },
    async load() {
      if (!this.partyId) return;
      this.error = '';
      const { data } = await axios.get('/api/finance/lettrage', {
        params: {
          sens: this.sens,
          client_id: this.sens === 'client' ? this.partyId : '',
          fournisseur_id: this.sens === 'fournisseur' ? this.partyId : '',
        },
      });
      this.factures = data.factures || [];
      this.reglements = data.reglements || [];
      this.historique = data.historique || [];
      this.solde = data.solde;
    },
    async auto() {
      this.working = true;
      this.error = '';
      try {
        const { data } = await axios.post('/api/finance/lettrage/auto', {
          sens: this.sens,
          client_id: this.sens === 'client' ? this.partyId : null,
          fournisseur_id: this.sens === 'fournisseur' ? this.partyId : null,
        });
        this.message = data.message;
        await this.load();
      } catch (e) {
        this.error = apiError(e);
      } finally {
        this.working = false;
      }
    },
    async match() {
      if (!this.selectedInvoice || !this.selectedPayment) {
        this.error = 'Sélectionnez une facture et un règlement.';
        return;
      }
      this.working = true;
      this.error = '';
      try {
        await axios.post('/api/finance/lettrage', {
          reglement_id: this.selectedPayment.id,
          facture_type: this.selectedInvoice.facture_type,
          facture_id: this.selectedInvoice.facture_id,
          montant: this.montant,
        });
        this.message = 'Lettrage enregistré.';
        await this.load();
      } catch (e) {
        this.error = apiError(e);
      } finally {
        this.working = false;
      }
    },
    async unmatch(id) {
      if (!window.confirm('Annuler ce lettrage ?')) return;
      try {
        await axios.delete(`/api/finance/lettrage/${id}`);
        await this.load();
      } catch (e) {
        this.error = apiError(e);
      }
    },
  },
};
</script>
