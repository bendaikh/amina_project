<template>
  <div class="fin-page">
    <header class="fin-header">
      <div>
        <p class="fin-kicker">Finance</p>
        <h1>Échéances</h1>
        <p>Factures à encaisser et factures à payer, avec le retard et la balance âgée. Chaque pièce reste dans sa devise.</p>
      </div>
      <div class="fin-export">
        <span>Exporter</span>
        <button type="button" @click="exportFile('csv')">CSV</button>
        <button type="button" @click="exportFile('xlsx')">Excel</button>
        <button type="button" @click="exportFile('pdf')">PDF</button>
      </div>
    </header>

    <div class="fin-tabs">
      <button type="button" class="fin-tab" :class="{ 'is-active': sens === 'client' }" @click="setSens('client')">Clients</button>
      <button type="button" class="fin-tab" :class="{ 'is-active': sens === 'fournisseur' }" @click="setSens('fournisseur')">Fournisseurs</button>
    </div>

    <section class="fin-kpis fin-kpis--5">
      <button v-for="card in cards" :key="card.etat" type="button" class="fin-kpi" :class="[card.tone, { 'is-active': filters.etat === card.etat }]" @click="filters.etat = filters.etat === card.etat ? '' : card.etat; load()">
        <span class="fin-kpi__label">{{ card.label }}</span>
        <strong>{{ card.value }}</strong>
      </button>
    </section>

    <section class="fin-kpis fin-kpis--5" style="margin-bottom:1rem">
      <button v-for="b in aging" :key="b.code" type="button" class="fin-kpi" :class="{ 'is-active': filters.aging === b.code, 'fin-kpi--rose': b.code === '90_plus', 'fin-kpi--amber': b.code === '61_90' }" @click="filters.aging = filters.aging === b.code ? '' : b.code; load()">
        <span class="fin-kpi__label">{{ b.label }}</span>
        <strong>{{ money(b.reste) }}</strong>
        <span class="fin-kpi__hint">{{ b.nombre }} pièce{{ b.nombre > 1 ? 's' : '' }}</span>
      </button>
    </section>

    <section class="fin-panel">
      <div class="fin-search">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
        <input v-model="filters.search" @input="debounced" type="search" placeholder="Rechercher une facture ou un tiers" />
      </div>
      <div class="fin-grid fin-grid--4">
        <label class="fin-field">{{ sens === 'client' ? 'Client' : 'Fournisseur' }}
          <select v-model="filters.party_id" @change="load">
            <option value="">Tous</option>
            <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.nom }}</option>
          </select>
        </label>
        <label class="fin-field">Échéance proche (jours)
          <input type="number" min="1" max="90" v-model.number="filters.horizon" @change="load" />
        </label>
        <FinDate v-model="filters.from" label="Du" @change="load" />
        <FinDate v-model="filters.to" label="Au" @change="load" />
      </div>
    </section>

    <section class="fin-card">
      <div style="overflow-x:auto">
        <table class="fin-table">
          <thead>
            <tr>
              <th>Facture</th>
              <th>Tiers</th>
              <th>Date</th>
              <th>Échéance</th>
              <th>Total</th>
              <th>Réglé</th>
              <th>Reste</th>
              <th>Retard</th>
              <th>Ancienneté</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="fin-empty">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="fin-empty"><strong>Aucune échéance</strong><span>Aucune facture ne correspond à ce filtre.</span></td></tr>
            <tr v-for="item in items" :key="item.facture_type + item.facture_id">
              <td class="fin-num">{{ item.numero }}</td>
              <td>{{ item.party_nom || '—' }}</td>
              <td>{{ dateFr(item.date) }}</td>
              <td>{{ dateFr(item.echeance) }}</td>
              <td>{{ dh(item.total, item.devise) }}</td>
              <td>{{ dh(item.montant_regle, item.devise) }}</td>
              <td style="font-weight:650">{{ dh(item.reste, item.devise) }}</td>
              <td>
                <span v-if="item.jours_retard > 0" class="fin-badge fin-badge--rose">{{ item.jours_retard }} j</span>
                <span v-else class="fin-badge fin-badge--ok">À jour</span>
              </td>
              <td><span class="fin-badge" :class="agingClass(item)">{{ item.aging_label }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
import axios from 'axios';
import { money, dh, dateFr, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'Echeances',
  components: { FinDate },
  data() {
    return {
      sens: 'client',
      items: [],
      aging: [],
      summary: {},
      parties: [],
      loading: false,
      timer: null,
      filters: { search: '', party_id: '', etat: '', aging: '', horizon: 7, from: '', to: '' },
    };
  },
  computed: {
    cards() {
      return [
        { etat: 'bientot', label: 'À échéance proche', value: money(this.summary.bientot), tone: '' },
        { etat: 'retard', label: 'En retard', value: money(this.summary.retard), tone: 'fin-kpi--rose' },
        { etat: 'impayee', label: 'Reste à payer', value: money(this.summary.impaye), tone: 'fin-kpi--amber' },
        { etat: 'partielle', label: 'Partielles', value: this.summary.partiel ?? 0, tone: '' },
        { etat: 'payee', label: 'Payées', value: this.summary.paye ?? 0, tone: '' },
      ];
    },
  },
  mounted() {
    this.loadParties();
    this.load();
  },
  methods: {
    dh, dateFr, money,
    async loadParties() {
      const { data } = await axios.get('/api/finance/tiers', { params: { sens: this.sens } });
      this.parties = data.data || data || [];
    },
    setSens(sens) {
      this.sens = sens;
      this.filters.party_id = '';
      this.loadParties();
      this.load();
    },
    debounced() {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.load(), 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/finance/echeances', { params: { sens: this.sens, ...this.filters } });
        this.items = data.data || [];
        this.aging = data.aging || [];
        this.summary = data.summary || {};
      } finally {
        this.loading = false;
      }
    },
    agingClass(item) {
      if (item.aging === '90_plus' || item.aging === '61_90') return 'fin-badge--rose';
      if (item.aging === '31_60' || item.aging === '1_30') return 'fin-badge--warn';
      if (item.aging === 'reglee') return 'fin-badge--ok';
      return 'fin-badge--muted';
    },
    exportFile(format) {
      downloadFile('/api/finance/echeances', { sens: this.sens, format, ...this.filters }, `echeances-${this.sens}.${format === 'xlsx' ? 'xlsx' : format}`);
    },
  },
};
</script>
