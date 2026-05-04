<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Fournisseurs</h1>
        <p class="text-gray-600">Gérez votre liste de fournisseurs</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <div class="flex-1">
            <div class="relative">
              <input 
                v-model="searchQuery" 
                @input="debouncedSearch"
                type="text" 
                placeholder="Rechercher un fournisseur..." 
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <button 
            @click="openModal()"
            class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium"
          >
            + Ajouter Fournisseur
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement...</p>
        </div>

        <!-- Data Table -->
        <div v-else-if="fournisseurs.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">ICE</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">EORI</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Pays</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="fournisseur in fournisseurs" 
                :key="fournisseur.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                      <span class="text-indigo-600 font-semibold">{{ getInitials(fournisseur.nom) }}</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ fournisseur.nom }}</p>
                      <p class="text-sm text-gray-500">{{ fournisseur.categorie }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.ice }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.eori || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.email || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.telephone || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.pays || '-' }}</td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button @click="openModal(fournisseur)" class="text-indigo-600 hover:text-indigo-800">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="deleteFournisseur(fournisseur.id)" class="text-red-600 hover:text-red-800">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
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

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <p class="text-gray-500 text-lg">Aucun fournisseur trouvé</p>
          <p class="text-gray-400 mt-2">Commencez par ajouter votre premier fournisseur</p>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
              {{ isEditing ? 'Modifier Fournisseur' : 'Nouveau Fournisseur' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveFournisseur" class="p-6">
            <!-- Informations Fournisseur -->
            <fieldset class="border border-gray-300 rounded-lg p-4 mb-6">
              <legend class="text-sm font-medium text-gray-700 px-2">Informations Fournisseur</legend>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- ICE -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">ICE:*</label>
                  <input 
                    v-model="form.ice" 
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Identifiant Commun de l'Entreprise"
                  />
                </div>

                <!-- Nom -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Nom:*</label>
                  <input 
                    v-model="form.nom" 
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- EORI -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">EORI:</label>
                  <input 
                    v-model="form.eori" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    placeholder="Economic Operators Registration and Identification"
                  />
                </div>

                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- Téléphone -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone:</label>
                  <input 
                    v-model="form.telephone" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- Pays -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Pays:</label>
                  <input 
                    v-model="form.pays" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- Adresse (full width) -->
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Adresse:</label>
                  <input 
                    v-model="form.adresse" 
                    type="text" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- Catégorie -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie:</label>
                  <select 
                    v-model="form.categorie"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  >
                    <option value="local">Local</option>
                    <option value="international">International</option>
                  </select>
                </div>

                <!-- Devise -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Devise:</label>
                  <select 
                    v-model="form.devise"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  >
                    <option value="MAD">MAD</option>
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                  </select>
                </div>
              </div>
            </fieldset>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-600">
              {{ errorMessage }}
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
              <button 
                type="button"
                @click="closeModal"
                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Annuler
              </button>
              <button 
                type="submit"
                :disabled="saving"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
              >
                {{ saving ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer') }}
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
  name: 'Suppliers',
  data() {
    return {
      fournisseurs: [],
      loading: true,
      saving: false,
      showModal: false,
      isEditing: false,
      editingId: null,
      searchQuery: '',
      searchTimeout: null,
      errorMessage: '',
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      },
      form: {
        ice: '',
        eori: '',
        nom: '',
        email: '',
        telephone: '',
        adresse: '',
        pays: '',
        categorie: 'local',
        devise: 'MAD'
      }
    };
  },
  mounted() {
    this.fetchFournisseurs();
  },
  methods: {
    async fetchFournisseurs(page = 1) {
      this.loading = true;
      try {
        const params = { page };
        if (this.searchQuery) {
          params.search = this.searchQuery;
        }
        const response = await axios.get('/api/fournisseurs', { params });
        this.fournisseurs = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        };
      } catch (error) {
        console.error('Error fetching fournisseurs:', error);
      } finally {
        this.loading = false;
      }
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchFournisseurs(1);
      }, 300);
    },
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchFournisseurs(page);
      }
    },
    openModal(fournisseur = null) {
      this.errorMessage = '';
      if (fournisseur) {
        this.isEditing = true;
        this.editingId = fournisseur.id;
        this.form = {
          ice: fournisseur.ice,
          eori: fournisseur.eori || '',
          nom: fournisseur.nom,
          email: fournisseur.email || '',
          telephone: fournisseur.telephone || '',
          adresse: fournisseur.adresse || '',
          pays: fournisseur.pays || '',
          categorie: fournisseur.categorie || 'local',
          devise: fournisseur.devise || 'MAD'
        };
      } else {
        this.isEditing = false;
        this.editingId = null;
        this.form = {
          ice: '',
          eori: '',
          nom: '',
          email: '',
          telephone: '',
          adresse: '',
          pays: '',
          categorie: 'local',
          devise: 'MAD'
        };
      }
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.errorMessage = '';
    },
    async saveFournisseur() {
      this.saving = true;
      this.errorMessage = '';
      try {
        if (this.isEditing) {
          await axios.put(`/api/fournisseurs/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/fournisseurs', this.form);
        }
        this.closeModal();
        this.fetchFournisseurs(this.pagination.current_page);
      } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
          const errors = error.response.data.errors;
          this.errorMessage = Object.values(errors).flat().join(', ');
        } else {
          this.errorMessage = 'Une erreur est survenue. Veuillez réessayer.';
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteFournisseur(id) {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')) {
        try {
          await axios.delete(`/api/fournisseurs/${id}`);
          this.fetchFournisseurs(this.pagination.current_page);
        } catch (error) {
          console.error('Error deleting fournisseur:', error);
          alert('Erreur lors de la suppression du fournisseur');
        }
      }
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
