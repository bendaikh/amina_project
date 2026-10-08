<template>
  <div class="fin-page">
      <header class="fin-header">
        <div>
          <p class="fin-kicker">Finance</p>
          <h1>{{ title }}</h1>
          <p>Paiement partiel ou total. Le reste de la facture et le solde du {{ sens === 'client' ? 'client' : 'fournisseur' }} sont recalculés à l’enregistrement.</p>
        </div>
        <button type="button" class="fin-btn fin-btn--primary" @click="openCreate">Nouveau règlement</button>
      </header>

      <section class="fin-kpis fin-kpis--3">
        <article class="fin-kpi">
          <span class="fin-kpi__label">Montant</span>
          <strong>{{ dh(totaux.montant) }}</strong>
          <span class="fin-kpi__hint">{{ total }} règlement{{ total > 1 ? 's' : '' }} affiché{{ total > 1 ? 's' : '' }}</span>
        </article>
        <article class="fin-kpi">
          <span class="fin-kpi__label">Affecté aux factures</span>
          <strong>{{ dh(totaux.affecte) }}</strong>
          <span class="fin-kpi__hint">Déjà lettré</span>
        </article>
        <article class="fin-kpi fin-kpi--amber">
          <span class="fin-kpi__label">Non affecté</span>
          <strong>{{ dh(totaux.reste) }}</strong>
          <span class="fin-kpi__hint">Avance ou reste à lettrer</span>
        </article>
      </section>

      <section class="fin-panel">
        <div class="fin-search">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
          <input v-model="filters.search" @input="debounced" type="search" placeholder="Rechercher un numéro, une référence ou un nom" />
        </div>
        <div class="fin-grid fin-grid--6">
          <label class="fin-field">Mode
            <select v-model="filters.mode_paiement" @change="load(1)">
              <option value="">Tous</option>
              <option v-for="m in modes" :key="m.code" :value="m.code">{{ m.label }}</option>
            </select>
          </label>
          <label class="fin-field">État
            <select v-model="filters.etat" @change="load(1)">
              <option value="">Tous</option>
              <option value="solde">Soldé</option>
              <option value="partiel">Partiel</option>
              <option value="non_affecte">Non affecté</option>
            </select>
          </label>
          <label class="fin-field">{{ sens === 'client' ? 'Client' : 'Fournisseur' }}
            <select v-model="filters.party_id" @change="load(1)">
              <option value="">Tous</option>
              <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </label>
          <FinDate v-model="filters.from" label="Du" @change="load(1)" />
          <FinDate v-model="filters.to" label="Au" @change="load(1)" />
        </div>
        <div class="fin-bar">
          <button type="button" class="fin-btn" @click="resetFilters">Réinitialiser</button>
          <div class="fin-export">
            <span>Exporter</span>
            <button type="button" @click="exportFile('csv')">CSV</button>
            <button type="button" @click="exportFile('xlsx')">Excel</button>
            <button type="button" @click="exportFile('pdf')">PDF</button>
          </div>
        </div>
      </section>

      <p v-if="error" class="fin-note" style="color:#be123c">{{ error }}</p>

      <section class="fin-card">
        <div style="overflow-x:auto">
        <table class="fin-table">
          <thead>
            <tr>
              <th>N°</th>
              <th>Date</th>
              <th>{{ sens === 'client' ? 'Client' : 'Fournisseur' }}</th>
              <th>Mode</th>
              <th>Référence</th>
              <th>Montant</th>
              <th>Affecté</th>
              <th>Reste</th>
              <th>Factures</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="10" class="fin-empty">Chargement…</td></tr>
            <tr v-else-if="!items.length">
              <td colspan="10" class="fin-empty">
                <strong>Aucun règlement pour ces critères</strong>
                <span>Enregistrez un encaissement ou élargissez la recherche.</span>
                <button type="button" class="fin-btn fin-btn--primary" @click="openCreate">Nouveau règlement</button>
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td class="fin-num">{{ item.numero }}</td>
              <td>{{ dateFr(item.date_reglement) }}</td>
              <td>{{ item.partie || '—' }}</td>
              <td>{{ item.mode_label }}</td>
              <td>{{ item.reference || '—' }}</td>
              <td style="font-weight:650">{{ dh(item.montant, item.devise) }}</td>
              <td>{{ dh(item.montant_affecte, item.devise) }}</td>
              <td>
                <span class="fin-badge" :class="etatOf(item).cls">{{ etatOf(item).label }}</span>
                <div style="margin-top:.2rem;font-size:.75rem;color:#6b7280">{{ dh(item.reste, item.devise) }}</div>
              </td>
              <td>{{ item.factures.map(f => f.numero).join(', ') || '—' }}</td>
              <td style="text-align:right;white-space:nowrap">
                <button type="button" class="fin-btn" @click="openDetail(item)">Détail</button>
                <button type="button" class="fin-btn" @click="openEdit(item)">Modifier</button>
                <button type="button" class="fin-btn fin-btn--danger" @click="remove(item)">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <div class="fin-pager">
          <span>{{ total }} règlement{{ total > 1 ? 's' : '' }}</span>
          <div style="display:flex;align-items:center;gap:.5rem">
            <button type="button" class="fin-btn" :disabled="page <= 1" @click="load(page - 1)">Précédent</button>
            <span>{{ page }} / {{ lastPage }}</span>
            <button type="button" class="fin-btn" :disabled="page >= lastPage" @click="load(page + 1)">Suivant</button>
          </div>
        </div>
      </section>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ viewing ? 'Détail du règlement' : (form.id ? 'Modifier le règlement' : 'Nouveau règlement') }}</h2>
            <button type="button" class="app-modal__close" @click="showModal = false">&times;</button>
          </div>
          <div class="app-modal__body">
            <p v-if="formError" class="mb-3 text-sm text-red-600">{{ formError }}</p>
            <div class="fin-grid fin-grid--3">
              <FinDate v-model="form.date_reglement" label="Date" :disabled="viewing" />
              <label class="fin-field">{{ sens === 'client' ? 'Client' : 'Fournisseur' }}
                <select v-model="form.party_id" :disabled="viewing" @change="loadInvoices">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.nom }}</option>
                </select>
              </label>
              <label class="fin-field">Mode
                <select v-model="form.mode_paiement" :disabled="viewing">
                  <option v-for="m in modes" :key="m.code" :value="m.code">{{ m.label }}</option>
                </select>
              </label>
              <label class="fin-field">Montant
                <input type="number" step="0.01" min="0" v-model.number="form.montant" :disabled="viewing" />
              </label>
              <label class="fin-field">Devise
                <select v-model="form.devise" :disabled="viewing">
                  <option v-for="d in devises" :key="d" :value="d">{{ d === 'MAD' ? 'MAD (DH)' : d }}</option>
                </select>
              </label>
              <label class="fin-field">Référence
                <input v-model="form.reference" :disabled="viewing" />
              </label>
              <label v-if="form.mode_paiement !== 'especes'" class="fin-field" style="grid-column: span 2">Compte bancaire
                <select v-model="form.compte_bancaire_id" :disabled="viewing">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in comptes" :key="c.id" :value="c.id">{{ c.nom_banque }} — {{ c.nom_compte }} ({{ c.devise }})</option>
                </select>
              </label>
              <label class="fin-field" style="grid-column: 1 / -1">Notes
                <textarea v-model="form.notes" :disabled="viewing" rows="2"></textarea>
              </label>
            </div>

            <div v-if="solde" class="mt-4 p-3 bg-teal-50 rounded-lg text-sm">
              Solde du tiers : <strong>{{ dh(solde.solde, solde.devise) }}</strong>
              <span v-if="Object.keys(solde.par_devise || {}).length > 1" class="text-gray-600">
                · {{ Object.entries(solde.par_devise).map(([d, v]) => dh(v, d)).join(' · ') }}
              </span>
            </div>

            <div class="mt-4 flex items-center justify-between">
              <h3 class="font-semibold">Factures à affecter</h3>
              <button v-if="!viewing" type="button" class="text-sm text-teal-700" @click="allocateFifo">Affecter automatiquement</button>
            </div>
            <div class="fin-card" style="margin-top:.5rem">
              <table class="fin-table">
                <thead>
                  <tr>
                    <th>Facture</th>
                    <th>Date</th>
                    <th>Échéance</th>
                    <th>Total</th>
                    <th>Disponible</th>
                    <th>Affectation</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!invoices.length"><td colspan="6" class="py-4 text-gray-500">Aucune facture ouverte pour ce tiers.</td></tr>
                  <tr v-for="inv in invoices" :key="inv.facture_type + inv.facture_id" class="border-b border-gray-50">
                    <td class="py-2">{{ inv.numero }} <span class="text-xs text-gray-400">{{ inv.source }}</span></td>
                    <td>{{ dateFr(inv.date) }}</td>
                    <td>{{ dateFr(inv.echeance) }}</td>
                    <td>{{ dh(inv.total, inv.devise) }}</td>
                    <td>{{ dh(inv.disponible, inv.devise) }}</td>
                    <td>
                      <input type="number" step="0.01" min="0" :max="inv.disponible" :disabled="viewing" v-model.number="allocations[keyOf(inv)]" class="border rounded px-2 py-1 w-28" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="mt-3 text-sm text-gray-600">
              Affecté {{ dh(allocatedSum, form.devise) }} · Reste du règlement {{ dh(Math.max(0, (Number(form.montant) || 0) - allocatedSum), form.devise) }}
            </p>
          </div>
          <div class="app-modal__footer">
            <button type="button" class="px-4 py-2 border rounded-lg" @click="showModal = false">Fermer</button>
            <button v-if="viewing" type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg" @click="viewing = false">Modifier</button>
            <button v-else type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg" :disabled="saving" @click="save">{{ saving ? 'Enregistrement…' : 'Enregistrer' }}</button>
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
  name: 'Reglements',
  components: { FinDate },
  props: { sens: { type: String, required: true } },
  data() {
    return {
      items: [],
      parties: [],
      modes: [],
      devises: ['MAD'],
      comptes: [],
      totaux: { montant: 0, affecte: 0, reste: 0 },
      loading: false,
      saving: false,
      error: '',
      formError: '',
      page: 1,
      lastPage: 1,
      total: 0,
      timer: null,
      showModal: false,
      viewing: false,
      invoices: [],
      allocations: {},
      solde: null,
      filters: { search: '', mode_paiement: '', etat: '', party_id: '', from: '', to: '' },
      form: this.emptyForm(),
    };
  },
  computed: {
    title() {
      return this.sens === 'client' ? 'Règlements clients' : 'Règlements fournisseurs';
    },
    allocatedSum() {
      return Object.values(this.allocations).reduce((s, n) => s + (Number(n) || 0), 0);
    },
  },
  mounted() {
    this.loadMeta();
    this.load(1);
  },
  watch: {
    sens() {
      this.filters.party_id = '';
      this.showModal = false;
      this.loadMeta();
      this.load(1);
    },
  },
  methods: {
    dh, dateFr,
    etatOf(item) {
      const reste = Number(item.reste) || 0;
      const affecte = Number(item.montant_affecte) || 0;
      if (affecte > 0 && reste <= 0.009) return { label: 'Soldé', cls: 'fin-badge--ok' };
      if (affecte > 0) return { label: 'Partiel', cls: 'fin-badge--warn' };
      return { label: 'Non affecté', cls: 'fin-badge--muted' };
    },
    resetFilters() {
      this.filters = { search: '', mode_paiement: '', etat: '', party_id: '', from: '', to: '' };
      this.load(1);
    },
    emptyForm() {
      return {
        id: null,
        date_reglement: new Date().toISOString().slice(0, 10),
        party_id: null,
        mode_paiement: 'virement',
        montant: 0,
        devise: 'MAD',
        reference: '',
        notes: '',
        compte_bancaire_id: null,
      };
    },
    keyOf(inv) {
      return `${inv.facture_type}:${inv.facture_id}`;
    },
    async loadMeta() {
      const [meta, tiers] = await Promise.all([
        axios.get('/api/finance/meta'),
        axios.get('/api/finance/tiers', { params: { sens: this.sens } }),
      ]);
      this.modes = meta.data.modes || [];
      this.devises = meta.data.devises || ['MAD'];
      this.comptes = meta.data.comptes || [];
      this.parties = tiers.data.data || tiers.data || [];
    },
    debounced() {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.load(1), 300);
    },
    async load(page = 1) {
      this.loading = true;
      this.error = '';
      try {
        const { data } = await axios.get('/api/finance/reglements', {
          params: {
            sens: this.sens,
            page,
            search: this.filters.search,
            mode_paiement: this.filters.mode_paiement,
            etat: this.filters.etat,
            from: this.filters.from,
            to: this.filters.to,
            client_id: this.sens === 'client' ? this.filters.party_id : '',
            fournisseur_id: this.sens === 'fournisseur' ? this.filters.party_id : '',
          },
        });
        this.items = data.data || [];
        this.page = data.current_page;
        this.lastPage = data.last_page;
        this.total = data.total;
        this.totaux = data.totaux || this.totaux;
      } catch (e) {
        this.error = apiError(e);
      } finally {
        this.loading = false;
      }
    },
    openCreate() {
      this.form = this.emptyForm();
      this.invoices = [];
      this.allocations = {};
      this.solde = null;
      this.viewing = false;
      this.formError = '';
      this.showModal = true;
    },
    async openEdit(item) {
      await this.fill(item, false);
    },
    async openDetail(item) {
      await this.fill(item, true);
    },
    async fill(item, viewing) {
      const { data } = await axios.get(`/api/finance/reglements/${item.id}`);
      this.form = {
        id: data.id,
        date_reglement: data.date_reglement,
        party_id: this.sens === 'client' ? data.client_id : data.fournisseur_id,
        mode_paiement: data.mode_paiement,
        montant: data.montant,
        devise: data.devise || 'MAD',
        reference: data.reference || '',
        notes: data.notes || '',
        compte_bancaire_id: data.compte_bancaire_id,
      };
      this.viewing = viewing;
      this.formError = '';
      this.showModal = true;
      await this.loadInvoices(data.factures || []);
    },
    async loadInvoices(existing = []) {
      this.allocations = {};
      this.solde = null;
      if (!this.form.party_id) {
        this.invoices = [];
        return;
      }
      const { data } = await axios.get('/api/finance/factures-ouvertes', {
        params: {
          sens: this.sens,
          client_id: this.sens === 'client' ? this.form.party_id : '',
          fournisseur_id: this.sens === 'fournisseur' ? this.form.party_id : '',
          reglement_id: this.form.id || '',
        },
      });
      this.invoices = data.factures || [];
      this.solde = data.solde;
      const party = this.parties.find((p) => p.id === this.form.party_id);
      if (party?.devise && !this.form.id) this.form.devise = party.devise;
      const seed = {};
      this.invoices.forEach((inv) => { seed[this.keyOf(inv)] = inv.montant_sur_reglement || 0; });
      existing.forEach((line) => { seed[`${line.facture_type}:${line.facture_id}`] = line.montant; });
      this.allocations = seed;
    },
    allocateFifo() {
      let left = Number(this.form.montant) || 0;
      const next = {};
      if (left <= 0) {
        left = this.invoices.reduce((s, inv) => s + Number(inv.disponible || 0), 0);
        this.form.montant = Math.round(left * 100) / 100;
      }
      this.invoices.forEach((inv) => {
        const take = Math.min(left, Number(inv.disponible) || 0);
        next[this.keyOf(inv)] = Math.round(take * 100) / 100;
        left -= take;
      });
      this.allocations = next;
    },
    async save() {
      this.saving = true;
      this.formError = '';
      const lignes = this.invoices
        .map((inv) => ({
          facture_type: inv.facture_type,
          facture_id: inv.facture_id,
          montant: Number(this.allocations[this.keyOf(inv)]) || 0,
        }))
        .filter((l) => l.montant > 0);
      const payload = {
        sens: this.sens,
        client_id: this.sens === 'client' ? this.form.party_id : null,
        fournisseur_id: this.sens === 'fournisseur' ? this.form.party_id : null,
        date_reglement: this.form.date_reglement,
        mode_paiement: this.form.mode_paiement,
        montant: this.form.montant,
        devise: this.form.devise,
        reference: this.form.reference,
        notes: this.form.notes,
        compte_bancaire_id: this.form.mode_paiement === 'especes' ? null : this.form.compte_bancaire_id,
        lignes,
      };
      try {
        if (this.form.id) await axios.put(`/api/finance/reglements/${this.form.id}`, payload);
        else await axios.post('/api/finance/reglements', payload);
        this.showModal = false;
        await this.load(this.page);
      } catch (e) {
        this.formError = apiError(e);
      } finally {
        this.saving = false;
      }
    },
    async remove(item) {
      if (!window.confirm(`Supprimer le règlement ${item.numero} ?`)) return;
      try {
        await axios.delete(`/api/finance/reglements/${item.id}`);
        await this.load(this.page);
      } catch (e) {
        this.error = apiError(e);
      }
    },
    exportFile(format) {
      const ext = format === 'xlsx' ? 'xlsx' : format;
      downloadFile('/api/finance/reglements', {
        sens: this.sens,
        format,
        search: this.filters.search,
        mode_paiement: this.filters.mode_paiement,
        etat: this.filters.etat,
        from: this.filters.from,
        to: this.filters.to,
        client_id: this.sens === 'client' ? this.filters.party_id : '',
        fournisseur_id: this.sens === 'fournisseur' ? this.filters.party_id : '',
      }, `${this.sens === 'client' ? 'reglements-clients' : 'reglements-fournisseurs'}.${ext}`);
    },
  },
};
</script>
