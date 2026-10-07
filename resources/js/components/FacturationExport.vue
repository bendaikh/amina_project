<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Facturation export</h1>
          <p class="text-gray-600">Factures commerciales — auto-remplies depuis société, client, commande et logistique</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="openFromCommande" class="px-5 py-2.5 border border-teal-600 text-teal-700 rounded-lg hover:bg-teal-50 font-medium">
            Depuis une commande
          </button>
          <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
            + Nouvelle facture
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input v-model="search" @input="debouncedLoad" type="text"
          placeholder="N° facture, export, client, booking…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500" />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option v-for="s in statuts" :key="s" :value="s">{{ labelStatut(s) }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° Facture</th>
              <th class="py-3 px-4">Export</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Commande</th>
              <th class="py-3 px-4">Incoterm</th>
              <th class="py-3 px-4">FOB</th>
              <th class="py-3 px-4">CFR</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucune facture</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero_facture || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ item.dest_nom || item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.numero_commande || item.commande?.numero || '—' }}</td>
              <td class="py-3 px-4">{{ item.incoterm || '—' }}</td>
              <td class="py-3 px-4">{{ formatMoney(item.total_fob) }} {{ item.devise }}</td>
              <td class="py-3 px-4">
                <span v-if="item.transport_applique">{{ formatMoney(item.total_cfr) }} {{ item.devise }}</span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="py-3 px-4"><span class="px-2 py-1 rounded text-xs bg-gray-100">{{ labelStatut(item.statut) }}</span></td>
              <td class="py-3 px-4">
                <div class="flex items-center justify-end gap-1">
                  <router-link
                    :to="`/ventes/export/facturation/${item.id}`"
                    title="Ouvrir"
                    class="p-2 rounded-lg text-teal-600 hover:bg-teal-50 transition-colors inline-flex"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </router-link>
                  <button
                    type="button"
                    title="Télécharger la facture"
                    :disabled="downloadingId === item.id"
                    @click="downloadFacture(item)"
                    class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors disabled:opacity-50"
                  >
                    <svg v-if="downloadingId !== item.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="actionError" class="mt-3 text-sm text-red-600">{{ actionError }}</p>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ fromCommandeMode ? 'Facture depuis commande' : 'Nouvelle facture commerciale' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body space-y-4">
            <div v-if="fromCommandeMode">
              <label class="text-sm text-gray-600">Commande export</label>
              <select v-model="createForm.commande_id" class="w-full border rounded-lg px-3 py-2">
                <option :value="null">Sélectionner…</option>
                <option v-for="c in commandes" :key="c.id" :value="c.id">
                  {{ c.numero }} — {{ c.client?.nom || '—' }} ({{ c.devise }})
                </option>
              </select>
            </div>
            <template v-else>
              <div>
                <label class="text-sm text-gray-600">Exportateur (primaire société)</label>
                <select v-model="createForm.exportateur_id" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">Exportateur primaire</option>
                  <option v-for="e in exportateurs" :key="e.id" :value="e.id">
                    {{ e.nom }}{{ e.est_defaut ? ' (primaire)' : '' }}
                  </option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Client / Destinataire</label>
                <select v-model="createForm.client_id" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Commande (optionnel)</label>
                <select v-model="createForm.commande_id" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">Sans commande</option>
                  <option v-for="c in commandes" :key="c.id" :value="c.id">{{ c.numero }} — {{ c.client?.nom || '—' }}</option>
                </select>
              </div>
            </template>
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button @click="showModal=false" class="px-4 py-2 border rounded-lg">Annuler</button>
            <button @click="create" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg">
              {{ saving ? 'Création…' : 'Créer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const STATUTS = ['commande','preparation','production','emballage','reservation','chargement','documents','expedition','arrivee','cloturee'];

export default {
  name: 'FacturationExport',
  data() {
    return {
      items: [],
      clients: [],
      exportateurs: [],
      commandes: [],
      loading: false,
      saving: false,
      downloadingId: null,
      actionError: '',
      search: '',
      statut: '',
      timer: null,
      showModal: false,
      fromCommandeMode: false,
      error: '',
      createForm: { exportateur_id: null, client_id: null, commande_id: null },
      statuts: STATUTS,
    };
  },
  mounted() {
    this.load();
    this.loadRefs();
  },
  methods: {
    labelStatut(s) {
      const map = {
        commande: 'Commande', preparation: 'Préparation', production: 'Production', emballage: 'Emballage',
        reservation: 'Réservation', chargement: 'Chargement', documents: 'Documents', expedition: 'Expédition',
        arrivee: 'Arrivée', cloturee: 'Clôturée',
      };
      return map[s] || s;
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    latestFactureDoc(item) {
      const docs = (item.documents || [])
        .filter(d => d.type === 'facture_commerciale')
        .sort((a, b) => (b.version || 0) - (a.version || 0));
      return docs[0] || null;
    },
    async downloadFacture(item) {
      this.downloadingId = item.id;
      this.actionError = '';
      try {
        let doc = this.latestFactureDoc(item);
        if (!doc) {
          const { data } = await axios.post(`/api/exportations/${item.id}/documents`, {
            type: 'facture_commerciale',
          });
          doc = data;
          if (!item.documents) item.documents = [];
          item.documents.push(data);
        }
        const filename = `${doc.titre || 'Facture_commerciale'}_${item.numero_facture || item.numero || doc.id}.pdf`
          .replace(/[^\w.\-]+/g, '_');
        const { data } = await axios.get(`/api/documents/${doc.id}/download`, {
          params: { format: 'pdf' },
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (e) {
        this.actionError = e.response?.data?.message || 'Impossible de télécharger la facture';
      } finally {
        this.downloadingId = null;
      }
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/exportations', {
          params: { search: this.search, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadRefs() {
      const [clients, exportateurs, commandes, meta] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 500, statut: 'client' } }),
        axios.get('/api/exportateurs', { params: { per_page: 200, actif: true } }),
        axios.get('/api/commandes', { params: { per_page: 200, type: 'export' } }),
        axios.get('/api/exportations-meta').catch(() => ({ data: {} })),
      ]);
      this.clients = clients.data.data || clients.data;
      this.exportateurs = exportateurs.data.data || exportateurs.data;
      this.commandes = commandes.data.data || commandes.data;
      const defaut = meta.data?.exportateur_defaut;
      if (defaut) this.createForm.exportateur_id = defaut.id;
    },
    openCreate() {
      this.fromCommandeMode = false;
      this.error = '';
      this.showModal = true;
    },
    openFromCommande() {
      this.fromCommandeMode = true;
      this.error = '';
      this.createForm.commande_id = null;
      this.showModal = true;
    },
    async create() {
      this.saving = true;
      this.error = '';
      try {
        let data;
        if (this.fromCommandeMode) {
          if (!this.createForm.commande_id) {
            this.error = 'Sélectionnez une commande';
            return;
          }
          ({ data } = await axios.post(`/api/exportations/from-commande/${this.createForm.commande_id}`));
        } else {
          if (!this.createForm.client_id && !this.createForm.commande_id) {
            this.error = 'Sélectionnez un client ou une commande';
            return;
          }
          ({ data } = await axios.post('/api/exportations', {
            exportateur_id: this.createForm.exportateur_id,
            client_id: this.createForm.client_id,
            commande_id: this.createForm.commande_id,
            origine_marchandise: 'Maroc',
          }));
        }
        this.showModal = false;
        this.$router.push(`/ventes/export/facturation/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la création';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
