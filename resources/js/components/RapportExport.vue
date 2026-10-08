<template>
  <div class="fin-page" style="max-width:64rem">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Rapports</p>
          <h1>Export</h1>
          <p>PDF, Excel, CSV ou impression. La période et les filtres choisis sont repris dans le document.</p>
        </div>
      </header>

      <section v-if="societe" class="fin-card" style="padding:1rem 1.15rem;margin-bottom:1rem">
        <p class="fin-kicker">Société</p>
        <p style="font-weight:750;font-size:1.05rem;margin:.15rem 0">{{ societe.nom }}</p>
        <p class="fin-note">{{ societe.adresse || 'Adresse non renseignée' }}</p>
        <p class="fin-note">{{ societe.telephone || '' }} {{ societe.email || '' }}</p>
        <p class="fin-note">ICE {{ societe.ice || '—' }} · IF {{ societe.if || '—' }} · RC {{ societe.rc || '—' }}</p>
        <p v-if="societe.rib || societe.iban" class="fin-note">RIB {{ societe.rib || '—' }} · IBAN {{ societe.iban || '—' }}</p>
      </section>

      <section class="fin-panel">
        <div class="fin-grid fin-grid--2">
        <FinDate v-model="filters.from" label="Du" />
        <FinDate v-model="filters.to" label="Au" />
        <label class="fin-field">Client
          <select v-model="filters.client_id">
            <option value="">Tous</option>
            <option v-for="c in meta.clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
          </select>
        </label>
        <label class="fin-field">Fournisseur
          <select v-model="filters.fournisseur_id">
            <option value="">Tous</option>
            <option v-for="c in meta.fournisseurs" :key="c.id" :value="c.id">{{ c.nom }}</option>
          </select>
        </label>
        <label class="fin-field">Devise
          <select v-model="filters.devise">
            <option value="">Toutes</option>
            <option v-for="d in meta.devises" :key="d" :value="d">{{ d === 'MAD' ? 'MAD (DH)' : d }}</option>
          </select>
        </label>
        <label class="fin-field">Compte (rapprochement / relevé)
          <select v-model="compteId">
            <option :value="null">—</option>
            <option v-for="c in comptes" :key="c.id" :value="c.id">{{ c.nom_banque }} — {{ c.nom_compte }}</option>
          </select>
        </label>
        </div>
      </section>

      <div class="space-y-3">
        <article v-for="item in documents" :key="item.key" class="fin-card" style="padding:1rem 1.15rem;display:flex;flex-wrap:wrap;justify-content:space-between;gap:.75rem;align-items:center">
          <div>
            <h2 class="font-semibold">{{ item.label }}</h2>
            <p class="text-sm text-gray-500">{{ item.description }}</p>
          </div>
          <div class="flex gap-2">
            <button v-for="fmt in formats" :key="fmt.id" type="button" class="fin-btn" @click="run(item, fmt.id)">{{ fmt.label }}</button>
          </div>
        </article>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { yearStart, today, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'RapportExport',
  components: { FinDate },
  data() {
    return {
      meta: { clients: [], fournisseurs: [], devises: [], catalogue: [] },
      societe: null,
      comptes: [],
      compteId: null,
      filters: { from: yearStart(), to: today(), client_id: '', fournisseur_id: '', devise: '', regroupement: 'month' },
      formats: [
        { id: 'pdf', label: 'PDF' },
        { id: 'xlsx', label: 'Excel' },
        { id: 'csv', label: 'CSV' },
        { id: 'print', label: 'Imprimer' },
      ],
    };
  },
  computed: {
    documents() {
      const base = this.meta.catalogue || [];
      return [
        ...base,
        { key: 'reglements-clients', label: 'Règlements clients', description: 'Liste des encaissements filtrée.' },
        { key: 'reglements-fournisseurs', label: 'Règlements fournisseurs', description: 'Liste des décaissements filtrée.' },
        { key: 'echeances-clients', label: 'Échéances clients', description: 'Balance âgée clients.' },
        { key: 'echeances-fournisseurs', label: 'Échéances fournisseurs', description: 'Balance âgée fournisseurs.' },
        { key: 'releve', label: 'Relevé bancaire', description: 'Opérations du compte sélectionné.' },
        { key: 'rapprochement', label: 'Rapprochement bancaire', description: 'Écart banque / ERP du compte sélectionné.' },
      ];
    },
  },
  mounted() {
    axios.get('/api/rapports/meta').then(({ data }) => {
      this.meta = data;
      this.societe = data.societe;
    });
    axios.get('/api/finance/banque/comptes').then(({ data }) => { this.comptes = data; });
  },
  methods: {
    run(item, format) {
      const ext = format === 'xlsx' ? 'xlsx' : 'pdf';
      if (item.key === 'ventes' || item.key === 'achats' || item.key === 'stock' || item.key === 'finance') {
        downloadFile(`/api/rapports/${item.key}`, { ...this.filters, format }, `${item.key}.${format === 'csv' ? 'csv' : ext}`);
        return;
      }
      if (item.key === 'reglements-clients' || item.key === 'reglements-fournisseurs') {
        const sens = item.key.endsWith('clients') ? 'client' : 'fournisseur';
        downloadFile('/api/finance/reglements', { sens, format, from: this.filters.from, to: this.filters.to, client_id: this.filters.client_id, fournisseur_id: this.filters.fournisseur_id }, `${item.key}.${format === 'csv' ? 'csv' : ext}`);
        return;
      }
      if (item.key.startsWith('echeances')) {
        const sens = item.key.endsWith('clients') ? 'client' : 'fournisseur';
        downloadFile('/api/finance/echeances', { sens, format, from: this.filters.from, to: this.filters.to, party_id: sens === 'client' ? this.filters.client_id : this.filters.fournisseur_id }, `${item.key}.${format === 'csv' ? 'csv' : ext}`);
        return;
      }
      if (!this.compteId) {
        window.alert('Choisissez un compte bancaire.');
        return;
      }
      const path = item.key === 'releve' ? 'export' : 'rapprochement';
      downloadFile(`/api/finance/banque/comptes/${this.compteId}/${path}`, { format, from: this.filters.from, to: this.filters.to }, `${item.key}.${format === 'csv' ? 'csv' : ext}`);
    },
  },
};
</script>
