<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Liste de colisage</h1>
          <p class="text-gray-600">Packing list — exportateur, destinataire, expédition et détail colis</p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle liste de colisage
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input v-model="search" @input="debouncedLoad" type="text" placeholder="Rechercher N° export, client, conteneur, booking…"
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
              <th class="py-3 px-4">N° Export</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Destination</th>
              <th class="py-3 px-4">Conteneur</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4">Régime 52</th>
              <th class="py-3 px-4">Valeur</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="8" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="8" class="py-10 text-center text-gray-500">Aucune exportation</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ item.dest_nom || item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.destination || item.pays || '—' }}</td>
              <td class="py-3 px-4">{{ item.conteneur || '—' }}</td>
              <td class="py-3 px-4"><span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span></td>
              <td class="py-3 px-4">{{ item.emballages_temporaires ? 'Oui' : 'Non' }}</td>
              <td class="py-3 px-4">{{ formatMoney(item.total_valeur) }} {{ item.devise }}</td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1">
                  <button
                    type="button"
                    title="Convertir en facture"
                    :disabled="convertingId === item.id"
                    @click="convertirEnFacture(item)"
                    class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors disabled:opacity-50"
                  >
                    <svg v-if="convertingId !== item.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                  </button>
                  <router-link
                    :to="`/exportations/${item.id}`"
                    title="Ouvrir"
                    class="p-2 rounded-lg text-teal-600 hover:bg-teal-50 transition-colors inline-flex"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </router-link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create modal -->
      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--lg" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Nouvelle liste de colisage</h2>
            <button type="button" class="app-modal__close" @click="showModal=false" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="app-form-grid app-form-grid--2">
              <div>
                <label class="text-sm text-gray-600">Exportateur (fiche société)</label>
                <select v-model="form.exportateur_id" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="e in exportateurs" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Destinataire (client)</label>
                <select v-model="form.client_id" @change="onClientChange" class="w-full border rounded-lg px-3 py-2">
                  <option :value="null">Sélectionner…</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Réf. client</label>
                <input v-model="form.reference_commande_client" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Pays</label>
                <input v-model="form.pays" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Destination</label>
                <input v-model="form.destination" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Incoterm</label>
                <input v-model="form.incoterm" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Devise</label>
                <input v-model="form.devise" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Mode transport</label>
                <select v-model="form.mode_transport" class="w-full border rounded-lg px-3 py-2">
                  <option value="maritime">Maritime</option>
                  <option value="routier">Routier</option>
                  <option value="aerien">Aérien</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Commercial</label>
                <input v-model="form.commercial" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div class="app-span-full">
                <label class="flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="form.emballages_temporaires" />
                  Emballages temporaires (régime 52) ?
                </label>
              </div>
            </div>

            <div v-if="form.emballages_temporaires" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg app-form-grid app-form-grid--2">
              <div>
                <label class="text-sm text-gray-600">Type emballage / fût</label>
                <input v-model="form.dossier_emballage.type_fut" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Quantité exportée</label>
                <input type="number" v-model.number="form.dossier_emballage.quantite_exportee" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">DUM 52</label>
                <input v-model="form.dossier_emballage.dum_52" class="w-full border rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date DUM</label>
                <input type="date" v-model="form.dossier_emballage.date_dum" class="w-full border rounded-lg px-3 py-2" />
              </div>
            </div>

            <div class="mt-6">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">Articles</h3>
                <button @click="addLigne" type="button" class="text-sm text-teal-600">+ Ligne</button>
              </div>
              <div v-for="(ligne, idx) in form.lignes" :key="idx" class="app-line-card">
                <div class="app-form-grid app-form-grid--3">
                  <div class="app-span-full">
                    <select v-model="ligne.article_id" @change="fillArticle(ligne)" class="w-full border rounded-lg px-2 py-2 text-sm">
                      <option :value="null">Article…</option>
                      <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }} — {{ a.designation }}</option>
                    </select>
                  </div>
                  <div>
                    <input type="number" v-model.number="ligne.quantite" placeholder="Qté" class="w-full border rounded-lg px-2 py-2 text-sm" />
                  </div>
                  <div>
                    <input type="number" step="0.01" v-model.number="ligne.prix_unitaire" placeholder="Prix" class="w-full border rounded-lg px-2 py-2 text-sm" />
                  </div>
                  <div class="flex items-end">
                    <button @click="form.lignes.splice(idx,1)" type="button" class="text-red-500">×</button>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button @click="showModal=false" class="px-4 py-2 border rounded-lg">Annuler</button>
            <button @click="save" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg">{{ saving ? 'Enregistrement…' : 'Créer' }}</button>
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
  name: 'Exportations',
  data() {
    return {
      items: [],
      clients: [],
      exportateurs: [],
      articles: [],
      loading: false,
      saving: false,
      convertingId: null,
      showModal: false,
      search: '',
      statut: '',
      error: '',
      timer: null,
      statuts: STATUTS,
      form: this.emptyForm(),
    };
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    emptyForm() {
      return {
        client_id: null,
        exportateur_id: null,
        pays: '',
        destination: '',
        reference_commande_client: '',
        incoterm: 'FOB',
        devise: 'EUR',
        commercial: '',
        mode_transport: 'maritime',
        emballages_temporaires: false,
        dossier_emballage: { type_fut: '', quantite_exportee: 0, dum_52: '', date_dum: '' },
        lignes: [{ article_id: null, quantite: 1, nb_colis: 1, prix_unitaire: 0 }],
      };
    },
    labelStatut(s) {
      const map = {
        commande: 'Commande', preparation: 'Préparation', production: 'Production', emballage: 'Emballage',
        reservation: 'Réservation', chargement: 'Chargement', documents: 'Documents', expedition: 'Expédition',
        arrivee: 'Arrivée', cloturee: 'Clôturée',
      };
      return map[s] || s;
    },
    badgeClass(s) {
      const map = {
        commande: 'bg-slate-100 text-slate-700', preparation: 'bg-blue-100 text-blue-700',
        production: 'bg-indigo-100 text-indigo-700', emballage: 'bg-violet-100 text-violet-700',
        reservation: 'bg-cyan-100 text-cyan-700', chargement: 'bg-amber-100 text-amber-700',
        documents: 'bg-orange-100 text-orange-700', expedition: 'bg-teal-100 text-teal-700',
        arrivee: 'bg-green-100 text-green-700', cloturee: 'bg-gray-200 text-gray-700',
      };
      return map[s] || 'bg-gray-100 text-gray-700';
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/exportations', { params: { search: this.search, statut: this.statut } });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const [c, a, e] = await Promise.all([
        axios.get('/api/clients'),
        axios.get('/api/articles'),
        axios.get('/api/exportateurs', { params: { per_page: 200, actif: true } }),
      ]);
      this.clients = c.data.data || c.data;
      this.articles = a.data.data || a.data;
      this.exportateurs = e.data.data || e.data;
    },
    openCreate() {
      this.form = this.emptyForm();
      const defaut = this.exportateurs.find(e => e.est_defaut) || this.exportateurs[0];
      if (defaut) this.form.exportateur_id = defaut.id;
      this.error = '';
      this.showModal = true;
    },
    onClientChange() {
      const c = this.clients.find(x => x.id === this.form.client_id);
      if (!c) return;
      this.form.pays = c.pays || this.form.pays;
      this.form.incoterm = c.incoterm || this.form.incoterm;
      this.form.devise = c.devise || this.form.devise;
      this.form.commercial = c.commercial_charge || this.form.commercial;
      this.form.destination = c.ville || c.pays || this.form.destination;
    },
    addLigne() {
      this.form.lignes.push({ article_id: null, quantite: 1, nb_colis: 1, prix_unitaire: 0 });
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (a) ligne.prix_unitaire = Number(a.prix_vente || 0);
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        const payload = { ...this.form };
        if (!payload.emballages_temporaires) delete payload.dossier_emballage;
        const { data } = await axios.post('/api/exportations', payload);
        this.showModal = false;
        this.$router.push(`/exportations/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la création';
      } finally {
        this.saving = false;
      }
    },
    async convertirEnFacture(item) {
      if (!confirm(`Convertir la liste de colisage ${item.numero} en facture commerciale ?`)) {
        return;
      }
      this.convertingId = item.id;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/exportations/${item.id}/to-facture`);
        this.$router.push(`/ventes/export/facturation/${data.id}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur conversion en facture';
        alert(this.error);
      } finally {
        this.convertingId = null;
      }
    },
  },
};
</script>
