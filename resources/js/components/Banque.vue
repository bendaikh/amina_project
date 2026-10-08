<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Finance</p>
          <h1>Banque</h1>
          <p>Comptes, RIB, IBAN et opérations. Le relevé s’importe depuis un fichier CSV ou Excel.</p>
        </div>
        <button type="button" class="fin-btn fin-btn--primary" @click="openAccount()">Nouveau compte</button>
      </header>

      <div class="fin-grid fin-grid--3" style="margin-bottom:1rem">
        <button v-for="c in comptes" :key="c.id" type="button" class="fin-account" :class="{ 'is-active': selected && selected.id === c.id }" @click="openCompte(c.id)">
          <span class="fin-account__bank">{{ c.nom_banque }}</span>
          <strong>{{ c.nom_compte }}</strong>
          <p class="fin-note" style="margin:.35rem 0 0">{{ c.rib || c.iban || 'RIB non renseigné' }}</p>
          <p class="fin-account__bal">{{ dh(c.solde_actuel, c.devise) }}</p>
          <p class="fin-note" style="margin:0">Ouverture {{ dh(c.solde_ouverture, c.devise) }}</p>
        </button>
        <article v-if="!comptes.length" class="fin-card"><div class="fin-empty"><strong>Aucun compte</strong><span>Ajoutez le compte sur lequel passent les virements, chèques et cartes.</span><button type="button" class="fin-btn fin-btn--primary" @click="openAccount()">Nouveau compte</button></div></article>
      </div>

      <div v-if="detail" class="fin-card">
        <div class="fin-card__head">
          <div>
            <h2>{{ detail.compte.nom_banque }} — {{ detail.compte.nom_compte }}</h2>
            <p class="fin-note" style="margin:.2rem 0 0">RIB {{ detail.compte.rib || '—' }} · IBAN {{ detail.compte.iban || '—' }} · solde {{ dh(detail.compte.solde_actuel, detail.compte.devise) }}</p>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:.5rem">
            <button type="button" class="fin-btn" @click="openAccount(detail.compte)">Modifier</button>
            <button type="button" class="fin-btn" @click="showMove = true">Opération</button>
            <label class="fin-btn" style="cursor:pointer">Importer
              <input type="file" accept=".csv,.txt,.xlsx" class="hidden" @change="importFile" />
            </label>
            <div class="fin-export">
              <button type="button" @click="exportFile('csv')">CSV</button>
              <button type="button" @click="exportFile('xlsx')">Excel</button>
            </div>
          </div>
        </div>
        <p v-if="notice" class="text-sm text-teal-700 mb-2">{{ notice }}</p>
        <p v-if="error" class="text-sm text-red-600 mb-2">{{ error }}</p>
        <div style="overflow-x:auto">
          <table class="fin-table">
            <thead>
              <tr>
                <th>Date</th><th>Description</th><th>Référence</th><th>Débit</th><th>Crédit</th><th>Solde</th><th>Type</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in detail.mouvements" :key="m.id">
                <td>{{ dateFr(m.date_operation) }}</td>
                <td>{{ m.description }}</td>
                <td>{{ m.reference || '—' }}</td>
                <td>{{ m.debit ? dh(m.debit, detail.compte.devise) : '—' }}</td>
                <td>{{ m.credit ? dh(m.credit, detail.compte.devise) : '—' }}</td>
                <td style="font-weight:650">{{ dh(m.solde, detail.compte.devise) }}</td>
                <td><span class="fin-badge fin-badge--muted">{{ m.type_label }}</span></td>
                <td><button type="button" class="fin-btn fin-btn--danger" @click="removeMove(m)">Supprimer</button></td>
              </tr>
              <tr v-if="!detail.mouvements.length"><td colspan="8" class="fin-empty"><strong>Aucune opération</strong><span>Saisissez une ligne ou importez le relevé.</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="showAccount" class="app-modal-overlay">
        <div class="app-modal app-modal--md">
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ account.id ? 'Modifier le compte' : 'Nouveau compte' }}</h2>
            <button type="button" class="app-modal__close" @click="showAccount = false">&times;</button>
          </div>
          <div class="app-modal__body fin-grid fin-grid--2">
            <label class="fin-field">Banque<input v-model="account.nom_banque" /></label>
            <label class="fin-field">Nom du compte<input v-model="account.nom_compte" /></label>
            <label class="fin-field">RIB<input v-model="account.rib" /></label>
            <label class="fin-field">IBAN<input v-model="account.iban" /></label>
            <label class="fin-field">Devise
              <select v-model="account.devise">
                <option v-for="d in devises" :key="d" :value="d">{{ d === 'MAD' ? 'MAD (DH)' : d }}</option>
              </select>
            </label>
            <label class="fin-field">Solde d’ouverture<input type="number" step="0.01" v-model.number="account.solde_ouverture" /></label>
            <label class="fin-field" style="text-transform:none;letter-spacing:0;font-size:.875rem;font-weight:500;color:#111827"><input type="checkbox" v-model="account.actif" /> Compte actif</label>
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showAccount = false">Annuler</button>
            <button type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg" @click="saveAccount">Enregistrer</button>
          </div>
        </div>
      </div>

      <div v-if="showMove" class="app-modal-overlay">
        <div class="app-modal app-modal--md">
          <div class="app-modal__header">
            <h2 class="app-modal__title">Nouvelle opération</h2>
            <button type="button" class="app-modal__close" @click="showMove = false">&times;</button>
          </div>
          <div class="app-modal__body fin-grid fin-grid--2">
            <FinDate v-model="move.date_operation" label="Date" />
            <label class="fin-field">Type
              <select v-model="move.type">
                <option v-for="(label, code) in types" :key="code" :value="code">{{ label }}</option>
              </select>
            </label>
            <label class="fin-field" style="grid-column:1 / -1">Description<input v-model="move.description" /></label>
            <label class="fin-field">Référence<input v-model="move.reference" /></label>
            <label class="fin-field">Débit<input type="number" step="0.01" min="0" v-model.number="move.debit" /></label>
            <label class="fin-field">Crédit<input type="number" step="0.01" min="0" v-model.number="move.credit" /></label>
          </div>
          <div class="app-modal__footer">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showMove = false">Annuler</button>
            <button type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg" @click="saveMove">Enregistrer</button>
          </div>
        </div>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import { dh, dateFr, apiError, downloadFile } from '../finance/format';
import FinDate from '../finance/FinDate.vue';

export default {
  name: 'Banque',
  components: { FinDate },
  data() {
    return {
      comptes: [],
      selected: null,
      detail: null,
      devises: ['MAD'],
      types: {},
      showAccount: false,
      showMove: false,
      notice: '',
      error: '',
      account: this.emptyAccount(),
      move: this.emptyMove(),
    };
  },
  mounted() {
    this.bootstrap();
  },
  methods: {
    dh, dateFr,
    emptyAccount() {
      return { id: null, nom_banque: '', nom_compte: '', rib: '', iban: '', devise: 'MAD', solde_ouverture: 0, actif: true };
    },
    emptyMove() {
      return { date_operation: new Date().toISOString().slice(0, 10), description: '', reference: '', type: 'virement_recu', debit: 0, credit: 0 };
    },
    async bootstrap() {
      const meta = await axios.get('/api/finance/meta');
      this.devises = meta.data.devises || ['MAD'];
      await this.loadComptes();
    },
    async loadComptes() {
      const { data } = await axios.get('/api/finance/banque/comptes');
      this.comptes = data;
      if (!this.selected && data[0]) await this.openCompte(data[0].id);
    },
    async openCompte(id) {
      const { data } = await axios.get(`/api/finance/banque/comptes/${id}`);
      this.detail = data;
      this.types = data.types || {};
      this.selected = data.compte;
      this.comptes = this.comptes.map((c) => c.id === data.compte.id ? data.compte : c);
    },
    openAccount(compte) {
      this.error = '';
      this.account = compte ? { ...compte } : this.emptyAccount();
      this.showAccount = true;
    },
    async saveAccount() {
      this.error = '';
      try {
        if (this.account.id) await axios.put(`/api/finance/banque/comptes/${this.account.id}`, this.account);
        else await axios.post('/api/finance/banque/comptes', this.account);
        this.showAccount = false;
        await this.loadComptes();
        if (this.account.id) await this.openCompte(this.account.id);
      } catch (e) {
        this.error = apiError(e);
      }
    },
    async saveMove() {
      this.error = '';
      try {
        await axios.post(`/api/finance/banque/comptes/${this.detail.compte.id}/mouvements`, this.move);
        this.showMove = false;
        this.move = this.emptyMove();
        await this.openCompte(this.detail.compte.id);
        await this.loadComptes();
      } catch (e) {
        this.error = apiError(e);
      }
    },
    async removeMove(m) {
      if (!window.confirm('Supprimer cette opération ?')) return;
      try {
        await axios.delete(`/api/finance/banque/mouvements/${m.id}`);
        await this.openCompte(this.detail.compte.id);
        await this.loadComptes();
      } catch (e) {
        this.error = apiError(e);
      }
    },
    async importFile(event) {
      const file = event.target.files[0];
      event.target.value = '';
      if (!file || !this.detail) return;
      const body = new FormData();
      body.append('fichier', file);
      try {
        const { data } = await axios.post(`/api/finance/banque/comptes/${this.detail.compte.id}/import`, body);
        this.notice = `${data.importes} opération(s) importée(s), ${data.ignores} ignorée(s).`;
        await this.openCompte(this.detail.compte.id);
        await this.loadComptes();
      } catch (e) {
        this.error = apiError(e);
      }
    },
    exportFile(format) {
      downloadFile(`/api/finance/banque/comptes/${this.detail.compte.id}/export`, { format }, `releve-${this.detail.compte.id}.${format === 'xlsx' ? 'xlsx' : format}`);
    },
  },
};
</script>
