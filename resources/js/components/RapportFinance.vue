<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Rapports</p>
          <h1>Finance</h1>
          <p>Créances, dettes, caisse, banque et TVA. Les montants en MAD sont affichés en DH.</p>
        </div>
        <div class="fin-export">
          <span>Exporter</span>
          <button v-for="fmt in ['pdf', 'xlsx', 'csv', 'print']" :key="fmt" type="button" @click="exportFile(fmt)">{{ fmt === 'print' ? 'Imprimer' : fmt.toUpperCase() }}</button>
        </div>
      </header>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--3">
          <FinDate v-model="filters.from" label="Du" />
          <FinDate v-model="filters.to" label="Au" />
          <div class="fin-field"><span>Période</span><button type="button" class="fin-btn fin-btn--primary" @click="load">Actualiser</button></div>
        </div>
      </section>

      <div v-if="report" class="fin-kpis fin-kpis--4">
        <article v-for="card in cards" :key="card.label" class="fin-kpi" :class="card.label.startsWith('Retard') ? 'fin-kpi--rose' : ''">
          <span class="fin-kpi__label">{{ card.label }}</span>
          <strong>{{ dh(card.value, card.devise) }}</strong>
        </article>
      </div>

      <div v-if="report && (hasOther(report.creances_devises) || hasOther(report.dettes_devises))" class="text-sm text-gray-600 mb-4">
        Créances par devise :
        <span v-for="(value, devise) in report.creances_devises" :key="'c' + devise" class="mr-3">{{ dh(value, devise) }}</span>
        · Dettes par devise :
        <span v-for="(value, devise) in report.dettes_devises" :key="'d' + devise" class="mr-3">{{ dh(value, devise) }}</span>
      </div>

      <div v-if="report" class="grid lg:grid-cols-2 gap-4 mb-4">
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-3">Chiffre d’affaires</h2>
          <div v-for="row in report.charts.ventes" :key="'v' + row.periode" class="flex items-center gap-2 text-sm mb-1">
            <span class="w-20 text-gray-500">{{ row.periode }}</span>
            <div class="flex-1 bg-gray-100 h-3 rounded"><div class="bg-teal-600 h-3 rounded" :style="{ width: width(row.ttc, maxSales) }"></div></div>
            <span>{{ dh(row.ttc, row.devise) }}</span>
          </div>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-3">Achats</h2>
          <div v-for="row in report.charts.achats" :key="'a' + row.periode" class="flex items-center gap-2 text-sm mb-1">
            <span class="w-20 text-gray-500">{{ row.periode }}</span>
            <div class="flex-1 bg-gray-100 h-3 rounded"><div class="bg-amber-500 h-3 rounded" :style="{ width: width(row.ttc, maxPurchases) }"></div></div>
            <span>{{ dh(row.ttc, row.devise) }}</span>
          </div>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-3">Créances et dettes</h2>
          <div v-for="row in report.charts.creances_dettes" :key="row.label" class="mb-3">
            <div class="flex justify-between text-sm"><span>{{ row.label }}</span><span>{{ dh(row.value) }}</span></div>
            <div class="bg-gray-100 h-3 rounded"><div class="h-3 rounded" :class="row.label.startsWith('Créances') ? 'bg-teal-600' : 'bg-orange-500'" :style="{ width: width(row.value, maxBalance) }"></div></div>
          </div>
          <h3 class="font-medium mt-4 mb-2">Trésorerie</h3>
          <div v-for="row in report.charts.tresorerie" :key="row.periode" class="flex justify-between text-sm border-b py-1">
            <span>{{ row.periode }}</span>
            <span class="text-teal-700">+{{ dh(row.entrees) }}</span>
            <span class="text-red-600">-{{ dh(row.sorties) }}</span>
            <span>{{ dh(row.solde) }}</span>
          </div>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-3">Modes de règlement</h2>
          <div v-for="row in report.charts.modes" :key="row.label" class="mb-2">
            <div class="flex justify-between text-sm"><span>{{ row.label }}</span><span>{{ dh(row.montant) }}</span></div>
            <div class="bg-gray-100 h-3 rounded"><div class="bg-sky-600 h-3 rounded" :style="{ width: width(row.montant, maxMode) }"></div></div>
          </div>
          <h3 class="font-medium mt-4 mb-2">TVA</h3>
          <p class="text-sm">Collectée {{ dh(report.tva.collectee) }} · Déductible {{ dh(report.tva.deductible) }} · Solde {{ dh(report.tva.due) }}</p>
          <p v-for="row in report.tva.lignes" :key="row.taux" class="text-sm flex justify-between border-b py-1">
            <span>{{ row.taux }} %</span><span>{{ dh(row.collectee) }} / {{ dh(row.deductible) }}</span>
          </p>
        </section>
      </div>

      <div v-if="report" class="grid lg:grid-cols-2 gap-4">
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Comptes bancaires</h2>
          <p v-for="c in report.comptes" :key="c.id" class="flex justify-between text-sm border-b py-1"><span>{{ c.nom }}</span><span>{{ dh(c.solde, c.devise) }}</span></p>
          <h3 class="font-medium mt-4 mb-2">Mouvements de banque</h3>
          <p v-for="(m, i) in report.banque_mouvements.slice(0, 8)" :key="i" class="text-sm border-b py-1">{{ dateFr(m.date) }} · {{ m.description }} · {{ m.credit ? '+' + dh(m.credit) : '-' + dh(m.debit) }}</p>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Caisse et historique</h2>
          <p v-for="(m, i) in report.caisse_mouvements.slice(0, 6)" :key="'c' + i" class="text-sm border-b py-1">{{ dateFr(m.date) }} · {{ m.sens }} · {{ m.partie }} · {{ dh(m.montant) }}</p>
          <h3 class="font-medium mt-4 mb-2">Derniers règlements</h3>
          <p v-for="(m, i) in report.historique.slice(0, 8)" :key="'h' + i" class="text-sm border-b py-1">{{ dateFr(m.date) }} · {{ m.numero }} · {{ m.partie }} · {{ dh(m.montant, m.devise) }}</p>
        </section>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, yearStart, today, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'RapportFinance',
  components: { FinDate },
  data() {
    return { report: null, filters: { from: yearStart(), to: today(), regroupement: 'month' } };
  },
  computed: {
    cards() {
      const k = this.report?.kpis || {};
      return [
        { label: 'Chiffre d’affaires', value: k.chiffre_affaires, devise: this.report.devise_ca || 'MAD' },
        { label: 'Achats', value: k.achats, devise: this.report.devise_achats || 'MAD' },
        { label: 'Créances', value: k.creances, devise: 'MAD' },
        { label: 'Dettes', value: k.dettes, devise: 'MAD' },
        { label: 'Caisse', value: k.caisse, devise: 'MAD' },
        { label: 'Banque', value: k.banque, devise: 'MAD' },
        { label: 'Retards clients', value: k.retard_clients, devise: 'MAD' },
        { label: 'Retards fournisseurs', value: k.retard_fournisseurs, devise: 'MAD' },
      ];
    },
    maxSales() { return Math.max(...(this.report?.charts?.ventes || []).map((r) => r.ttc), 1); },
    maxPurchases() { return Math.max(...(this.report?.charts?.achats || []).map((r) => r.ttc), 1); },
    maxBalance() { return Math.max(...(this.report?.charts?.creances_dettes || []).map((r) => r.value), 1); },
    maxMode() { return Math.max(...(this.report?.charts?.modes || []).map((r) => r.montant), 1); },
  },
  mounted() { this.load(); },
  methods: {
    dh, dateFr,
    width(value, max) { return `${Math.max(2, (Number(value) / max) * 100)}%`; },
    hasOther(map) { return map && Object.keys(map).length > 0; },
    async load() {
      const { data } = await axios.get('/api/rapports/finance', { params: this.filters });
      this.report = data;
    },
    exportFile(format) {
      const ext = format === 'xlsx' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
      downloadFile('/api/rapports/finance', { ...this.filters, format }, `rapport-finance.${ext}`);
    },
  },
};
</script>
