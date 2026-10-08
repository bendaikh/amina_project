<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Finance</p>
          <h1>Rapprochement bancaire</h1>
          <p>Comparez les règlements de l’ERP et les lignes du relevé, puis exportez l’écart.</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <button type="button" class="fin-btn fin-btn--primary" :disabled="!compteId" @click="auto">Rapprochement auto</button>
          <div class="fin-export">
            <span>Exporter</span>
            <button type="button" :disabled="!compteId" @click="exportFile('pdf')">PDF</button>
            <button type="button" :disabled="!compteId" @click="exportFile('xlsx')">Excel</button>
            <button type="button" :disabled="!compteId" @click="exportFile('csv')">CSV</button>
          </div>
        </div>
      </header>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--3">
          <label class="fin-field">Compte
            <select v-model="compteId" @change="load">
              <option :value="null">Sélectionner…</option>
              <option v-for="c in comptes" :key="c.id" :value="c.id">{{ c.nom_banque }} — {{ c.nom_compte }}</option>
            </select>
          </label>
          <FinDate v-model="from" label="Du" @change="load" />
          <FinDate v-model="to" label="Au" @change="load" />
        </div>
      </section>

      <p v-if="message" class="text-sm text-teal-700 mb-3">{{ message }}</p>
      <p v-if="error" class="text-sm text-red-600 mb-3">{{ error }}</p>

      <div v-if="state" class="fin-kpis fin-kpis--5">
        <article class="fin-kpi"><span class="fin-kpi__label">Solde banque</span><strong>{{ dh(state.solde_banque) }}</strong></article>
        <article class="fin-kpi"><span class="fin-kpi__label">Solde ERP</span><strong>{{ dh(state.solde_erp) }}</strong></article>
        <article class="fin-kpi" :class="state.ecart ? 'fin-kpi--amber' : ''"><span class="fin-kpi__label">Écart</span><strong>{{ dh(state.ecart) }}</strong></article>
        <article class="fin-kpi"><span class="fin-kpi__label">Rapproché</span><strong>{{ dh(state.montant_rapproche) }}</strong></article>
        <article class="fin-kpi fin-kpi--rose"><span class="fin-kpi__label">Non rapproché</span><strong>{{ dh(state.montant_non_rapproche) }}</strong></article>
      </div>

      <div v-if="state" class="fin-split" style="margin-bottom:1rem">
        <div class="fin-card">
          <div class="fin-card__head"><h2>Relevé non rapproché</h2></div>
          <table class="fin-table">
            <tbody>
              <tr v-for="m in state.mouvements_non_rapproches" :key="m.id" class="border-t">
                <td class="py-2"><input type="radio" name="banque" :value="m.id" v-model="mouvementId" /></td>
                <td>{{ dateFr(m.date_operation) }}<div class="text-xs text-gray-500">{{ m.description }}</div></td>
                <td>{{ m.credit ? dh(m.credit) : dh(m.debit) }}</td>
                <td class="text-xs">reste {{ dh(m.reste) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="fin-card">
          <div class="fin-card__head"><h2>Règlements non rapprochés</h2></div>
          <table class="fin-table">
            <tbody>
              <tr v-for="r in state.reglements_non_rapproches" :key="r.id" class="border-t">
                <td class="py-2"><input type="radio" name="erp" :value="r.id" v-model="reglementId" /></td>
                <td>{{ r.numero }}<div class="text-xs text-gray-500">{{ r.partie }} · {{ dateFr(r.date) }}</div></td>
                <td>{{ dh(r.montant) }}</td>
                <td class="text-xs">reste {{ dh(r.reste) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="state" class="fin-panel" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end">
        <label class="fin-field">Montant
          <input type="number" step="0.01" v-model.number="montant" />
        </label>
        <button type="button" class="fin-btn fin-btn--primary" @click="match">Rapprocher</button>
        <p class="fin-note" style="margin:0">Écart de sélection : {{ dh(ecartSelection) }}</p>
      </div>

      <div v-if="state" class="fin-card">
        <div class="fin-card__head"><h2>Écritures rapprochées</h2></div>
        <table class="fin-table">
          <thead><tr><th>Date</th><th>Libellé</th><th>Règlement</th><th>Montant</th><th>Méthode</th><th></th></tr></thead>
          <tbody>
            <tr v-for="l in state.lignes" :key="l.id" class="border-t">
              <td class="px-4 py-2">{{ dateFr(l.date_banque) }}</td>
              <td>{{ l.libelle }}</td>
              <td>{{ l.reglement }}</td>
              <td>{{ dh(l.montant) }}</td>
              <td class="capitalize">{{ l.methode }}</td>
              <td class="text-right px-4"><button type="button" class="text-red-600" @click="unmatch(l)">Annuler</button></td>
            </tr>
          </tbody>
        </table>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, apiError, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'RapprochementBancaire',
  components: { FinDate },
  data() {
    return {
      comptes: [],
      compteId: null,
      from: '',
      to: '',
      state: null,
      mouvementId: null,
      reglementId: null,
      montant: 0,
      message: '',
      error: '',
    };
  },
  computed: {
    ecartSelection() {
      const m = this.state?.mouvements_non_rapproches?.find((x) => x.id === this.mouvementId);
      const r = this.state?.reglements_non_rapproches?.find((x) => x.id === this.reglementId);
      if (!m || !r) return 0;
      return (Number(m.reste) || 0) - (Number(r.reste) || 0);
    },
  },
  watch: {
    mouvementId() { this.suggest(); },
    reglementId() { this.suggest(); },
  },
  mounted() {
    this.bootstrap();
  },
  methods: {
    dh, dateFr,
    suggest() {
      const m = this.state?.mouvements_non_rapproches?.find((x) => x.id === this.mouvementId);
      const r = this.state?.reglements_non_rapproches?.find((x) => x.id === this.reglementId);
      if (m && r) this.montant = Math.round(Math.min(m.reste, r.reste) * 100) / 100;
    },
    async bootstrap() {
      const { data } = await axios.get('/api/finance/banque/comptes');
      this.comptes = data;
      if (data[0]) {
        this.compteId = data[0].id;
        await this.load();
      }
    },
    async load() {
      if (!this.compteId) return;
      this.error = '';
      const { data } = await axios.get(`/api/finance/banque/comptes/${this.compteId}/rapprochement`, {
        params: { from: this.from, to: this.to },
      });
      this.state = data;
    },
    async auto() {
      try {
        const { data } = await axios.post(`/api/finance/banque/comptes/${this.compteId}/rapprochement/auto`);
        this.message = data.message;
        await this.load();
      } catch (e) {
        this.error = apiError(e);
      }
    },
    async match() {
      this.error = '';
      try {
        await axios.post(`/api/finance/banque/comptes/${this.compteId}/rapprochement`, {
          mouvement_bancaire_id: this.mouvementId,
          reglement_id: this.reglementId,
          montant: this.montant,
        });
        this.message = 'Rapprochement enregistré.';
        await this.load();
      } catch (e) {
        this.error = apiError(e);
      }
    },
    async unmatch(ligne) {
      await axios.delete(`/api/finance/banque/rapprochement/${ligne.id}`);
      await this.load();
    },
    exportFile(format) {
      downloadFile(`/api/finance/banque/comptes/${this.compteId}/rapprochement`, { format, from: this.from, to: this.to }, `rapprochement.${format === 'xlsx' ? 'xlsx' : format}`);
    },
  },
};
</script>
