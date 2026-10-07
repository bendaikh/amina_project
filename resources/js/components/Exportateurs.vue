<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Exportateurs</h1>
        <p class="text-gray-600">Gérez les exportateurs — l’exportateur primaire est celui de la société</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <div class="flex-1">
            <div class="relative">
              <input
                v-model="searchQuery"
                @input="debouncedSearch"
                type="text"
                placeholder="Rechercher un exportateur..."
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <button
            @click="openModal()"
            class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium"
          >
            + Ajouter exportateur
          </button>
        </div>

        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement...</p>
        </div>

        <div v-else-if="exportateurs.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Réf. Foodex</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Adresse</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Tél.</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">E-mail</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Web</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Primaire</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">État</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in exportateurs"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center shrink-0">
                      <span class="text-teal-600 font-semibold">{{ getInitials(item.nom) }}</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ item.nom }}</p>
                      <p v-if="item.nom_societe && item.nom_societe !== item.nom" class="text-xs text-gray-500">{{ item.nom_societe }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">{{ item.ref_foodex || '-' }}</td>
                <td class="py-4 px-4 text-gray-600 max-w-xs truncate" :title="item.adresse">{{ item.adresse || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ item.telephone || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ item.email || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">
                  <a
                    v-if="item.web"
                    :href="formatWebUrl(item.web)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-teal-600 hover:underline"
                  >{{ item.web }}</a>
                  <span v-else>-</span>
                </td>
                <td class="py-4 px-4">
                  <span
                    v-if="item.est_defaut"
                    class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800"
                  >
                    Primaire
                  </span>
                  <button
                    v-else
                    type="button"
                    @click="setPrimaire(item)"
                    class="px-2.5 py-1 text-xs font-medium rounded-full border border-gray-300 text-gray-600 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-800 transition-colors"
                    title="Définir comme exportateur primaire de la société"
                  >
                    Définir
                  </button>
                </td>
                <td class="py-4 px-4">
                  <button
                    type="button"
                    @click="toggleActive(item)"
                    :class="[
                      'px-3 py-1 text-xs font-medium rounded-full',
                      item.actif ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ item.actif ? 'Actif' : 'Inactif' }}
                  </button>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button @click="openModal(item)" class="text-teal-600 hover:text-teal-800" title="Modifier">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="deleteExportateur(item.id)" class="text-red-600 hover:text-red-800" title="Supprimer">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="pagination.last_page > 1" class="mt-6 flex justify-center">
            <nav class="flex space-x-2">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <span class="px-4 py-2 text-gray-600">
                Page {{ pagination.current_page }} sur {{ pagination.last_page }}
              </span>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
            </nav>
          </div>
        </div>

        <div v-else class="text-center py-12">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <p class="text-gray-500 text-lg">Aucun exportateur trouvé</p>
          <p class="text-gray-400 mt-2">Commencez par ajouter votre premier exportateur</p>
        </div>
      </div>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-teal-600 rounded flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h2 class="app-modal__title">
                {{ isEditing ? 'Modifier exportateur' : 'Nouvel exportateur' }}
              </h2>
            </div>
            <button type="button" class="app-modal__close" @click="closeModal" aria-label="Fermer">&times;</button>
          </div>

          <form @submit.prevent="saveExportateur">
            <div class="app-modal__body space-y-4">
              <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ errorMessage }}
              </div>

              <div class="border border-gray-300 rounded overflow-hidden">
                <div class="bg-gray-200 text-center py-2 font-bold text-sm tracking-wide text-gray-800 uppercase">
                  Exportateur
                </div>
                <div class="p-4 space-y-4 bg-white">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                    <input
                      v-model="form.nom"
                      type="text"
                      required
                      placeholder="AGRO FOOD ASSA"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la société</label>
                    <input
                      v-model="form.nom_societe"
                      type="text"
                      placeholder="AGRO FOOD ASSA"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Réf. Foodex</label>
                    <input
                      v-model="form.ref_foodex"
                      type="text"
                      placeholder="FDX-2026-001"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <textarea
                      v-model="form.adresse"
                      rows="2"
                      placeholder="Béni Mellal, Maroc"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    ></textarea>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tél.</label>
                    <input
                      v-model="form.telephone"
                      type="text"
                      placeholder="+212 5XX XX XX XX"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input
                      v-model="form.email"
                      type="email"
                      placeholder="export@agrofoodassa.ma"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Web</label>
                    <input
                      v-model="form.web"
                      type="text"
                      placeholder="www.agrofoodassa.ma"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>

              <div class="border border-gray-300 rounded overflow-hidden">
                <div class="bg-gray-200 text-center py-2 font-bold text-sm tracking-wide text-gray-800 uppercase">
                  Coordonnées bancaires
                </div>
                <div class="p-4 space-y-4 bg-white">
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Banque</label>
                      <input v-model="form.banque" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Agence</label>
                      <input v-model="form.agence" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bénéficiaire</label>
                    <input v-model="form.beneficiaire" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                    <input v-model="form.iban" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">SWIFT</label>
                      <input v-model="form.swift" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">RIB</label>
                      <input v-model="form.rib" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 space-y-2">
                <label class="inline-flex items-start gap-2 text-sm text-gray-800">
                  <input
                    v-model="form.est_defaut"
                    type="checkbox"
                    class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                  />
                  <span>
                    <span class="font-medium">Exportateur primaire (société)</span>
                    <span class="block text-xs text-gray-600 mt-0.5">
                      Utilisé automatiquement sur les listes de colisage et factures export. Un seul exportateur peut être primaire.
                    </span>
                  </span>
                </label>
              </div>

              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                Actif
              </label>
            </div>

            <div class="app-modal__footer">
              <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Annuler
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
              >
                {{ saving ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Enregistrer') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Exportateurs',
  data() {
    return {
      exportateurs: [],
      loading: false,
      saving: false,
      showModal: false,
      isEditing: false,
      editingId: null,
      searchQuery: '',
      searchTimeout: null,
      errorMessage: '',
      form: this.getEmptyForm(),
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      }
    };
  },
  mounted() {
    this.fetchExportateurs();
  },
  methods: {
    getEmptyForm() {
      return {
        nom: '',
        nom_societe: '',
        ref_foodex: '',
        adresse: '',
        telephone: '',
        email: '',
        web: '',
        banque: '',
        agence: '',
        beneficiaire: '',
        iban: '',
        swift: '',
        rib: '',
        actif: true,
        est_defaut: false,
      };
    },
    async fetchExportateurs(page = 1) {
      this.loading = true;
      try {
        const params = { page };
        if (this.searchQuery) {
          params.search = this.searchQuery;
        }
        const response = await axios.get('/api/exportateurs', { params });
        this.exportateurs = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        };
      } catch (error) {
        console.error('Error fetching exportateurs:', error);
      } finally {
        this.loading = false;
      }
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchExportateurs(1);
      }, 300);
    },
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchExportateurs(page);
      }
    },
    openModal(item = null) {
      this.errorMessage = '';
      if (item) {
        this.isEditing = true;
        this.editingId = item.id;
        this.form = {
          nom: item.nom || '',
          nom_societe: item.nom_societe || '',
          ref_foodex: item.ref_foodex || '',
          adresse: item.adresse || '',
          telephone: item.telephone || '',
          email: item.email || '',
          web: item.web || '',
          banque: item.banque || '',
          agence: item.agence || '',
          beneficiaire: item.beneficiaire || '',
          iban: item.iban || '',
          swift: item.swift || '',
          rib: item.rib || '',
          actif: item.actif !== false,
          est_defaut: !!item.est_defaut,
        };
      } else {
        this.isEditing = false;
        this.editingId = null;
        this.form = this.getEmptyForm();
      }
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.errorMessage = '';
    },
    async saveExportateur() {
      this.saving = true;
      this.errorMessage = '';
      try {
        if (this.isEditing) {
          await axios.put(`/api/exportateurs/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/exportateurs', this.form);
        }
        this.closeModal();
        this.fetchExportateurs(this.pagination.current_page);
      } catch (error) {
        if (error.response?.data?.errors) {
          this.errorMessage = Object.values(error.response.data.errors).flat().join(', ');
        } else {
          this.errorMessage = 'Une erreur est survenue. Veuillez réessayer.';
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteExportateur(id) {
      if (!confirm('Êtes-vous sûr de vouloir supprimer cet exportateur ?')) return;
      try {
        await axios.delete(`/api/exportateurs/${id}`);
        this.fetchExportateurs(this.pagination.current_page);
      } catch (error) {
        console.error('Error deleting exportateur:', error);
        alert('Erreur lors de la suppression de l\'exportateur');
      }
    },
    async toggleActive(item) {
      try {
        const response = await axios.post(`/api/exportateurs/${item.id}/toggle-active`);
        item.actif = response.data.exportateur.actif;
      } catch (error) {
        console.error('Error toggling actif:', error);
      }
    },
    async setPrimaire(item) {
      if (!confirm(`Définir « ${item.nom} » comme exportateur primaire de la société ?`)) return;
      try {
        await axios.post(`/api/exportateurs/${item.id}/set-primaire`);
        this.fetchExportateurs(this.pagination.current_page);
      } catch (error) {
        console.error('Error setting primaire:', error);
        alert(error.response?.data?.message || 'Erreur lors de la définition de l\'exportateur primaire');
      }
    },
    formatWebUrl(web) {
      if (!web) return '#';
      if (/^https?:\/\//i.test(web)) return web;
      return `https://${web}`;
    },
    getInitials(name) {
      if (!name) return '?';
      return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2);
    }
  }
};
</script>
