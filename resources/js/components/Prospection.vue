<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Prospects</h1>
        <p class="text-gray-600">Gestion des parties › Prospects</p>
      </div>

      <!-- KPIs -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-sm text-gray-500">Total prospects</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-sm text-gray-500">En cours</p>
          <p class="text-2xl font-bold text-teal-700">{{ stats.enCours }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-sm text-gray-500">À relancer</p>
          <p class="text-2xl font-bold text-amber-600">{{ stats.aRelancer }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
          <p class="text-sm text-gray-500">Convertis (filtrés hors liste)</p>
          <p class="text-2xl font-bold text-green-700">{{ stats.convertis }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-6">
          <div class="flex-1 relative">
            <input
              v-model="searchQuery"
              @input="searchProspects"
              type="text"
              placeholder="Rechercher (société, code, contact, email...)"
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            />
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <select
            v-model="filterStatut"
            @change="fetchProspects(1)"
            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white min-w-[200px]"
          >
            <option value="">Tous les statuts</option>
            <option v-for="s in pipelineStatuts" :key="s" :value="s">{{ s }}</option>
          </select>
          <button
            @click="openCreateModal"
            class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium whitespace-nowrap"
          >
            + Nouveau prospect
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Code</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Société</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Pays</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Contact</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Commercial</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Statut</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Prochaine action</th>
                <th class="text-left py-3 px-3 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="py-10 text-center text-gray-500">Chargement...</td>
              </tr>
              <tr v-else-if="prospects.length === 0">
                <td colspan="8" class="py-10 text-center text-gray-500">
                  <p>Aucun prospect trouvé</p>
                  <p class="text-sm mt-1">Cliquez sur « Nouveau prospect » pour commencer</p>
                </td>
              </tr>
              <tr
                v-else
                v-for="p in prospects"
                :key="p.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-3 px-3 text-sm font-medium text-gray-700">{{ p.code_client || '—' }}</td>
                <td class="py-3 px-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-teal-100 rounded-full flex items-center justify-center shrink-0">
                      <span class="text-teal-700 text-xs font-semibold">{{ getInitials(p.nom) }}</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ p.nom }}</p>
                      <p class="text-xs text-gray-500">{{ p.secteur_activite || '—' }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3 text-sm text-gray-600">{{ p.pays || '—' }}</td>
                <td class="py-3 px-3 text-sm text-gray-600">
                  <p>{{ p.contact_nom || '—' }}</p>
                  <p class="text-xs text-gray-400">{{ p.telephone || p.contact_telephone || '' }}</p>
                </td>
                <td class="py-3 px-3 text-sm text-gray-600">{{ p.commercial_charge || '—' }}</td>
                <td class="py-3 px-3">
                  <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', statutBadgeClass(p.statut_prospection)]">
                    {{ p.statut_prospection || 'Nouveau' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-sm text-gray-600">
                  <p>{{ p.prochaine_action || '—' }}</p>
                  <p v-if="p.prochaine_action_date" class="text-xs text-gray-400">{{ formatDate(p.prochaine_action_date) }}</p>
                </td>
                <td class="py-3 px-3">
                  <div class="flex items-center gap-2">
                    <button @click="viewProspect(p)" class="text-teal-600 hover:text-teal-800" title="Voir">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button @click="editProspect(p)" class="text-blue-600 hover:text-blue-800" title="Modifier">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button
                      v-if="p.statut_prospection !== 'Converti en client'"
                      @click="convertProspect(p)"
                      class="text-green-600 hover:text-green-800"
                      title="Convertir en client"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <button @click="deleteProspect(p)" class="text-red-600 hover:text-red-800" title="Supprimer">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-gray-600">Page {{ pagination.current_page }} / {{ pagination.last_page }}</div>
          <div class="flex gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-4 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
            >Précédent</button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-4 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
            >Suivant</button>
          </div>
        </div>
      </div>

      <!-- Create / Edit modal -->
      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--lg" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">
              {{ isEditing ? 'Modifier le prospect' : 'Nouveau prospect' }}
            </h2>
            <button type="button" class="app-modal__close" @click="closeModal" aria-label="Fermer">&times;</button>
          </div>

          <div class="app-modal__body">
          <form @submit.prevent="saveProspect" class="space-y-8">
            <section>
              <h3 class="text-sm font-semibold text-teal-700 uppercase tracking-wide mb-4">Informations générales</h3>
              <div class="app-form-grid app-form-grid--2">
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Code</label>
                  <input v-model="form.code_client" type="text" class="field" placeholder="PRO-0001" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Société *</label>
                  <input v-model="form.nom" type="text" required class="field" placeholder="Raison sociale" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Pays</label>
                  <input v-model="form.pays" type="text" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Secteur</label>
                  <select v-model="form.secteur_activite" class="field bg-white">
                    <option value="">— Sélectionner —</option>
                    <option v-for="s in parametres.secteur_activite" :key="s.id || s.libelle" :value="s.libelle || s.valeur || s">
                      {{ s.libelle || s.valeur || s }}
                    </option>
                  </select>
                </div>
                <div class="app-span-full">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Adresse</label>
                  <textarea v-model="form.adresse" rows="2" class="field"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Contact</label>
                  <input v-model="form.contact_nom" type="text" class="field" placeholder="Nom du contact" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Téléphone</label>
                  <input v-model="form.telephone" type="text" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">E-mail</label>
                  <input v-model="form.email" type="email" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Fonction du contact</label>
                  <input v-model="form.contact_fonction" type="text" class="field" />
                </div>
              </div>
            </section>

            <section>
              <h3 class="text-sm font-semibold text-teal-700 uppercase tracking-wide mb-4">Suivi commercial</h3>
              <div class="app-form-grid app-form-grid--2">
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Commercial</label>
                  <input v-model="form.commercial_charge" type="text" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Source</label>
                  <input v-model="form.source" type="text" class="field" placeholder="Salon, site web, recommandation..." />
                </div>
                <div class="app-span-full">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Produits intéressés</label>
                  <textarea v-model="form.produits_interesses" rows="2" class="field" placeholder="Produits ou références d'intérêt"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Premier contact</label>
                  <input v-model="form.premier_contact" type="date" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Dernier contact</label>
                  <input v-model="form.dernier_contact" type="date" class="field" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Prochaine action</label>
                  <input v-model="form.prochaine_action" type="text" class="field" placeholder="Appeler, envoyer offre..." />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Date prochaine action</label>
                  <input v-model="form.prochaine_action_date" type="date" class="field" />
                </div>
                <div class="app-span-full">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Statut</label>
                  <select v-model="form.statut_prospection" class="field bg-white">
                    <option v-for="s in pipelineStatuts" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
              </div>
            </section>
          </form>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg">Annuler</button>
            <button
              type="button"
              @click="saveProspect"
              :disabled="saving"
              class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
            >
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>

      <!-- View modal -->
      <div v-if="showViewModal && selected" class="app-modal-overlay">
        <div class="app-modal app-modal--lg" @click.stop>
          <div class="app-modal__header">
            <div>
              <h2 class="app-modal__title">{{ selected.nom }}</h2>
              <span :class="['inline-block mt-1 px-2.5 py-1 text-xs font-medium rounded-full', statutBadgeClass(selected.statut_prospection)]">
                {{ selected.statut_prospection || 'Nouveau' }}
              </span>
            </div>
            <button type="button" class="app-modal__close" @click="showViewModal = false" aria-label="Fermer">&times;</button>
          </div>
          <div class="app-modal__body space-y-6">
            <div class="app-form-grid app-form-grid--2 text-sm">
              <div><span class="text-gray-500">Code</span><p class="font-medium">{{ selected.code_client || '—' }}</p></div>
              <div><span class="text-gray-500">Pays</span><p class="font-medium">{{ selected.pays || '—' }}</p></div>
              <div><span class="text-gray-500">Secteur</span><p class="font-medium">{{ selected.secteur_activite || '—' }}</p></div>
              <div><span class="text-gray-500">Adresse</span><p class="font-medium">{{ selected.adresse || '—' }}</p></div>
              <div><span class="text-gray-500">Contact</span><p class="font-medium">{{ selected.contact_nom || '—' }}</p></div>
              <div><span class="text-gray-500">Téléphone</span><p class="font-medium">{{ selected.telephone || selected.contact_telephone || '—' }}</p></div>
              <div><span class="text-gray-500">E-mail</span><p class="font-medium">{{ selected.email || selected.contact_email || '—' }}</p></div>
              <div><span class="text-gray-500">Commercial</span><p class="font-medium">{{ selected.commercial_charge || '—' }}</p></div>
              <div><span class="text-gray-500">Source</span><p class="font-medium">{{ selected.source || '—' }}</p></div>
              <div><span class="text-gray-500">Produits intéressés</span><p class="font-medium">{{ selected.produits_interesses || '—' }}</p></div>
              <div><span class="text-gray-500">Premier contact</span><p class="font-medium">{{ formatDate(selected.premier_contact) }}</p></div>
              <div><span class="text-gray-500">Dernier contact</span><p class="font-medium">{{ formatDate(selected.dernier_contact) }}</p></div>
              <div><span class="text-gray-500">Prochaine action</span><p class="font-medium">{{ selected.prochaine_action || '—' }}</p></div>
              <div><span class="text-gray-500">Date prochaine action</span><p class="font-medium">{{ formatDate(selected.prochaine_action_date) }}</p></div>
            </div>
          </div>
          <div class="app-modal__footer">
            <button @click="editProspect(selected); showViewModal = false" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Modifier</button>
            <button
              v-if="selected.statut === 'prospect'"
              @click="convertProspect(selected)"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
            >
              Convertir en client
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Prospection',
  data() {
    return {
      prospects: [],
      allForStats: [],
      loading: false,
      saving: false,
      showModal: false,
      showViewModal: false,
      isEditing: false,
      selected: null,
      searchQuery: '',
      filterStatut: '',
      searchTimeout: null,
      pipelineStatuts: [
        'Nouveau',
        'En prospection',
        'Contact établi',
        'Échantillon envoyé',
        'Offre envoyée',
        'En négociation',
        'Converti en client',
        'Perdu',
        'À relancer'
      ],
      parametres: { secteur_activite: [] },
      pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
      form: this.emptyForm(),
      errors: {}
    };
  },
  computed: {
    stats() {
      const list = this.allForStats;
      const closed = ['Converti en client', 'Perdu'];
      return {
        total: list.length,
        enCours: list.filter(p => !closed.includes(p.statut_prospection)).length,
        aRelancer: list.filter(p => p.statut_prospection === 'À relancer').length,
        convertis: list.filter(p => p.statut_prospection === 'Converti en client').length
      };
    }
  },
  mounted() {
    this.fetchProspects();
    this.fetchStatsPool();
    this.fetchParametres();
  },
  methods: {
    emptyForm() {
      return {
        id: null,
        code_client: '',
        nom: '',
        pays: '',
        secteur_activite: '',
        adresse: '',
        contact_nom: '',
        contact_fonction: '',
        telephone: '',
        email: '',
        commercial_charge: '',
        source: '',
        produits_interesses: '',
        premier_contact: '',
        dernier_contact: '',
        prochaine_action: '',
        prochaine_action_date: '',
        statut_prospection: 'Nouveau',
        statut: 'prospect',
        categorie: 'export',
        devise: 'EUR',
        actif: true
      };
    },
    getCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },
    dateOnly(value) {
      if (!value) return '';
      return String(value).substring(0, 10);
    },
    formatDate(value) {
      if (!value) return '—';
      const d = this.dateOnly(value);
      const [y, m, day] = d.split('-');
      if (!y || !m || !day) return d;
      return `${day}/${m}/${y}`;
    },
    getInitials(name) {
      if (!name) return '??';
      const parts = name.trim().split(/\s+/);
      if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase();
      return name.substring(0, 2).toUpperCase();
    },
    statutBadgeClass(statut) {
      const map = {
        'Nouveau': 'bg-sky-100 text-sky-800',
        'En prospection': 'bg-blue-100 text-blue-800',
        'Contact établi': 'bg-indigo-100 text-indigo-800',
        'Échantillon envoyé': 'bg-violet-100 text-violet-800',
        'Offre envoyée': 'bg-fuchsia-100 text-fuchsia-800',
        'En négociation': 'bg-amber-100 text-amber-800',
        'Converti en client': 'bg-green-100 text-green-800',
        'Perdu': 'bg-red-100 text-red-800',
        'À relancer': 'bg-orange-100 text-orange-800'
      };
      return map[statut] || 'bg-gray-100 text-gray-800';
    },
    async fetchParametres() {
      try {
        const response = await fetch('/api/parametres/all-for-forms');
        if (response.ok) {
          const data = await response.json();
          this.parametres = { secteur_activite: data.secteur_activite || [] };
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchStatsPool() {
      try {
        const params = new URLSearchParams({ statut: 'prospect', per_page: 100 });
        const response = await fetch(`/api/clients?${params}`);
        const data = await response.json();
        this.allForStats = data.data || [];
      } catch (e) {
        this.allForStats = [];
      }
    },
    async fetchProspects(page = 1) {
      this.loading = true;
      try {
        const params = new URLSearchParams({
          page: String(page),
          search: this.searchQuery,
          statut: 'prospect'
        });
        if (this.filterStatut) params.set('statut_prospection', this.filterStatut);

        const response = await fetch(`/api/clients?${params}`);
        const data = await response.json();
        this.prospects = data.data || [];
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total
        };
      } catch (e) {
        console.error(e);
        this.prospects = [];
      } finally {
        this.loading = false;
      }
    },
    searchProspects() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => this.fetchProspects(1), 300);
    },
    changePage(page) {
      this.fetchProspects(page);
    },
    openCreateModal() {
      this.isEditing = false;
      this.form = this.emptyForm();
      this.form.premier_contact = new Date().toISOString().substring(0, 10);
      this.showModal = true;
    },
    editProspect(p) {
      this.isEditing = true;
      this.form = {
        ...this.emptyForm(),
        ...p,
        premier_contact: this.dateOnly(p.premier_contact),
        dernier_contact: this.dateOnly(p.dernier_contact),
        prochaine_action_date: this.dateOnly(p.prochaine_action_date),
        statut: 'prospect',
        statut_prospection: p.statut_prospection || 'Nouveau'
      };
      this.showModal = true;
    },
    async viewProspect(p) {
      try {
        const response = await fetch(`/api/clients/${p.id}`);
        this.selected = await response.json();
      } catch (e) {
        this.selected = p;
      }
      this.showViewModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.form = this.emptyForm();
      this.errors = {};
    },
    async saveProspect() {
      this.saving = true;
      this.errors = {};
      try {
        const payload = {
          ...this.form,
          statut: 'prospect',
          telephone: this.form.telephone || null,
          email: this.form.email || null
        };
        const url = this.isEditing ? `/api/clients/${this.form.id}` : '/api/clients';
        const method = this.isEditing ? 'PUT' : 'POST';
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          },
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok) {
          if (response.status === 422) this.errors = data.errors || {};
          alert(data.message || 'Une erreur est survenue');
          return;
        }
        this.closeModal();
        await this.fetchProspects(this.pagination.current_page);
        await this.fetchStatsPool();
        alert(data.message);
      } catch (e) {
        console.error(e);
        alert('Une erreur est survenue');
      } finally {
        this.saving = false;
      }
    },
    async convertProspect(p) {
      if (!confirm(`Convertir « ${p.nom} » en client sans ressaisie ? L'historique sera conservé.`)) {
        return;
      }
      try {
        const response = await fetch(`/api/clients/${p.id}/convert`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          }
        });
        const data = await response.json();
        if (!response.ok) {
          alert(data.message || 'Conversion impossible');
          return;
        }
        this.showViewModal = false;
        await this.fetchProspects(this.pagination.current_page);
        await this.fetchStatsPool();
        alert(data.message + '\nLe dossier est maintenant disponible dans Clients.');
      } catch (e) {
        console.error(e);
        alert('Une erreur est survenue');
      }
    },
    async deleteProspect(p) {
      if (!confirm(`Supprimer le prospect « ${p.nom} » ?`)) return;
      try {
        const response = await fetch(`/api/clients/${p.id}`, {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          }
        });
        const data = await response.json();
        if (!response.ok) {
          alert(data.message || 'Suppression impossible');
          return;
        }
        await this.fetchProspects(this.pagination.current_page);
        await this.fetchStatsPool();
      } catch (e) {
        console.error(e);
        alert('Une erreur est survenue');
      }
    }
  }
};
</script>

<style scoped>
.field {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
}
.field:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.35);
  border-color: transparent;
}
</style>
