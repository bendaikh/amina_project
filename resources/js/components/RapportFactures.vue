<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Rapports</p>
          <h1>{{ kind === 'achats' ? 'Achats' : 'Ventes' }}</h1>
          <p>Totaux HT, TVA et TTC calculés sur les factures enregistrées. Les taux de TVA viennent des lignes.</p>
        </div>
        <div class="fin-export">
          <span>Exporter</span>
          <button v-for="fmt in ['pdf', 'xlsx', 'csv', 'print']" :key="fmt" type="button" @click="exportFile(fmt)">{{ fmt === 'print' ? 'Imprimer' : fmt.toUpperCase() }}</button>
        </div>
      </header>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--4">
        <FinDate v-model="filters.from" label="Du" />
        <FinDate v-model="filters.to" label="Au" />
        <label v-if="kind === 'ventes'" class="fin-field">Client
          <select v-model="filters.client_id">
            <option value="">Tous</option>
            <option v-for="c in meta.clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
          </select>
        </label>
        <label v-else class="fin-field">Fournisseur
          <select v-model="filters.fournisseur_id">
            <option value="">Tous</option>
            <option v-for="c in meta.fournisseurs" :key="c.id" :value="c.id">{{ c.nom }}</option>
          </select>
        </label>
        <label class="fin-field">Produit
          <select v-model="filters.article_id">
            <option value="">Tous</option>
            <option v-for="a in meta.articles" :key="a.id" :value="a.id">{{ a.designation }}</option>
          </select>
        </label>
        <label class="fin-field">Catégorie
          <select v-model="filters.categorie">
            <option value="">Toutes</option>
            <option v-for="c in meta.categories" :key="c" :value="c">{{ c }}</option>
          </select>
        </label>
        <label class="fin-field">Statut
        <select v-model="filters.statut">
          <option value="">Tous les statuts</option>
          <option v-if="kind === 'ventes'" value="validee">Validée</option>
          <option v-if="kind === 'ventes'" value="payee">Payée</option>
          <option v-if="kind === 'ventes'" value="annulee">Annulée</option>
          <option v-if="kind === 'achats'" value="validee">Validée</option>
          <option v-if="kind === 'achats'" value="echeance">Échéance</option>
          <option v-if="kind === 'achats'" value="payee">Payée</option>
        </select>
        </label>
        <label class="fin-field">Règlement
        <select v-model="filters.paiement">
          <option value="">Tous</option>
          <option value="payee">Payé</option>
          <option value="partielle">Partiel</option>
          <option value="impayee">Impayé</option>
        </select>
        </label>
        <label class="fin-field">Mode
        <select v-model="filters.mode_paiement">
          <option value="">Tous</option>
          <option v-for="m in meta.modes" :key="m.code" :value="m.code">{{ m.label }}</option>
        </select>
        </label>
        <label class="fin-field">Devise
        <select v-model="filters.devise">
          <option value="">Toutes</option>
          <option v-for="d in meta.devises" :key="d" :value="d">{{ d === 'MAD' ? 'MAD (DH)' : d }}</option>
        </select>
        </label>
        <label class="fin-field">Période
        <select v-model="filters.regroupement">
          <option value="day">Par jour</option>
          <option value="month">Par mois</option>
          <option value="year">Par année</option>
        </select>
        </label>
        </div>
        <div class="fin-bar">
          <span></span>
          <button type="button" class="fin-btn fin-btn--primary" @click="load">Actualiser</button>
        </div>
      </section>

      <div v-if="report" class="fin-kpis fin-kpis--4">
        <article v-for="card in kpiCards" :key="card.label" class="fin-kpi">
          <span class="fin-kpi__label">{{ card.label }}</span>
          <strong>{{ card.text }}</strong>
        </article>
      </div>

      <div v-if="report" class="grid lg:grid-cols-2 gap-4">
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Évolution</h2>
          <div v-for="row in report.series" :key="row.periode" class="flex items-center gap-2 text-sm mb-1">
            <span class="w-24 text-gray-500">{{ row.periode }}</span>
            <div class="flex-1 bg-gray-100 rounded h-3"><div class="bg-teal-600 h-3 rounded" :style="{ width: bar(row.ttc) }"></div></div>
            <span class="w-32 text-right">{{ dh(row.ttc, row.devise) }}</span>
          </div>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">{{ kind === 'achats' ? 'Par fournisseur' : 'Par client' }}</h2>
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="row in (kind === 'achats' ? report.par_fournisseur : report.par_client)" :key="row.label" class="border-t">
                <td class="py-1">{{ row.label }}</td>
                <td class="text-right">{{ dh(row.ttc) }}</td>
                <td class="text-right text-gray-500">{{ row.nombre }}</td>
              </tr>
            </tbody>
          </table>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Par produit</h2>
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="row in report.par_produit.slice(0, 12)" :key="row.label" class="border-t">
                <td class="py-1">{{ row.label }}</td>
                <td class="text-right">{{ dh(row.ttc) }}</td>
              </tr>
            </tbody>
          </table>
        </section>
        <section class="bg-white border rounded-xl p-4">
          <h2 class="font-semibold mb-2">Par catégorie et par mode</h2>
          <p v-for="row in report.par_categorie" :key="row.label" class="text-sm flex justify-between border-b py-1"><span>{{ row.label }}</span><span>{{ dh(row.ttc) }}</span></p>
          <h3 class="font-medium mt-3 mb-1">Règlements</h3>
          <p v-for="row in report.par_reglement" :key="row.label" class="text-sm flex justify-between"><span>{{ row.label }}</span><span>{{ dh(row.montant) }}</span></p>
          <h3 class="font-medium mt-3 mb-1">TVA par taux</h3>
          <p v-for="row in report.taux" :key="row.taux" class="text-sm flex justify-between"><span>{{ row.taux }} %</span><span>{{ dh(row.tva) }}</span></p>
        </section>
      </div>

      <div v-if="report" class="bg-white border rounded-xl overflow-x-auto mt-4">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="py-2 px-3">Facture</th><th>Date</th><th>Tiers</th><th>Statut</th><th>Règlement</th><th>HT</th><th>TVA</th><th>TTC</th><th>Réglé</th><th>Reste</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in report.factures" :key="f.type + f.id" class="border-t">
              <td class="py-2 px-3">{{ f.numero }}</td>
              <td>{{ dateFr(f.date) }}</td>
              <td>{{ f.partie }}</td>
              <td class="capitalize">{{ f.statut }}</td>
              <td>{{ labelPaiement(f.statut_paiement) }}</td>
              <td>{{ dh(f.ht, f.devise) }}</td>
              <td>{{ dh(f.tva, f.devise) }}</td>
              <td>{{ dh(f.ttc, f.devise) }}</td>
              <td>{{ dh(f.paid, f.devise) }}</td>
              <td>{{ dh(f.reste, f.devise) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, money, yearStart, today, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'RapportFactures',
  components: { FinDate },
  props: { kind: { type: String, default: 'ventes' } },
  data() {
    return {
      meta: { clients: [], fournisseurs: [], articles: [], categories: [], modes: [], devises: [] },
      report: null,
      filters: {
        from: yearStart(), to: today(), client_id: '', fournisseur_id: '', article_id: '',
        categorie: '', statut: '', paiement: '', mode_paiement: '', devise: '', regroupement: 'month',
      },
    };
  },
  computed: {
    maxTtc() {
      return Math.max(...(this.report?.series || []).map((r) => Number(r.ttc) || 0), 1);
    },
    kpiCards() {
      const k = this.report?.kpis || {};
      const devises = [...new Set((this.report?.factures || []).filter((f) => !f.annulee).map((f) => f.devise))];
      const unit = devises.length === 1 ? devises[0] : null;
      const fmt = (n) => unit ? dh(n, unit) : money(n);
      const cards = [
        { label: 'Total HT', text: fmt(k.total_ht) },
        { label: 'Total TVA', text: fmt(k.total_tva) },
        { label: 'Total TTC', text: fmt(k.total_ttc) },
        { label: 'Total réglé', text: fmt(k.total_regle) },
        { label: 'Total impayé', text: fmt(k.total_impaye) },
        { label: 'Factures', text: k.nombre ?? 0 },
      ];
      if (this.kind === 'ventes') {
        cards.push({ label: 'Facture moyenne', text: fmt(k.moyenne) });
        cards.push({ label: 'Annulées', text: `${k.annulees || 0} · ${fmt(k.annulees_ttc)}` });
      }
      cards.push({ label: 'Payées / partielles / impayées', text: `${k.payees || 0} / ${k.partielles || 0} / ${k.impayees || 0}` });
      return cards;
    },
  },
  mounted() {
    this.loadMeta();
    this.load();
  },
  watch: {
    kind() {
      this.report = null;
      this.load();
    },
  },
  methods: {
    dh, dateFr, money,
    bar(value) { return `${Math.max(2, (Number(value) / this.maxTtc) * 100)}%`; },
    labelPaiement(code) {
      return { payee: 'Payé', partielle: 'Partiel', impayee: 'Impayé', annulee: 'Annulé' }[code] || code;
    },
    async loadMeta() {
      const { data } = await axios.get('/api/rapports/meta');
      this.meta = data;
    },
    async load() {
      const { data } = await axios.get(`/api/rapports/${this.kind}`, { params: this.filters });
      this.report = data;
    },
    exportFile(format) {
      downloadFile(`/api/rapports/${this.kind}`, { ...this.filters, format }, `rapport-${this.kind}.${format === 'xlsx' ? 'xlsx' : format === 'print' ? 'pdf' : format}`);
    },
  },
};
</script>
