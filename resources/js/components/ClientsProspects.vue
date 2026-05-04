<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Clients & Prospects</h1>
        <p class="text-gray-600">Gérez votre portefeuille clients et prospects</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <div class="flex-1">
            <div class="relative">
              <input 
                v-model="searchQuery"
                @input="searchClients"
                type="text" 
                placeholder="Rechercher un client ou prospect..." 
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <button 
            @click="openCreateModal"
            class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium"
          >
            + Ajouter Client
          </button>
        </div>

        <!-- Clients Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">ICE/CIN</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Catégorie</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" class="border-b border-gray-100">
                <td colspan="7" class="py-8 text-center text-gray-500">
                  <div class="flex items-center justify-center space-x-2">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Chargement...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="clients.length === 0" class="border-b border-gray-100">
                <td colspan="7" class="py-8 text-center text-gray-500">
                  <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <p>Aucun client trouvé</p>
                  <p class="text-sm mt-2">Cliquez sur "Ajouter Client" pour créer votre premier client</p>
                </td>
              </tr>
              <tr v-else v-for="client in clients" :key="client.id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-4 px-4 text-gray-600">{{ client.ice_cin }}</td>
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                      <span class="text-indigo-600 font-semibold">{{ getInitials(client.nom) }}</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ client.nom }}</p>
                      <p class="text-sm text-gray-500">{{ client.pays || '-' }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">{{ client.email || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ client.telephone || '-' }}</td>
                <td class="py-4 px-4">
                  <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                    {{ client.categorie }}
                  </span>
                </td>
                <td class="py-4 px-4">
                  <span :class="[
                    'px-3 py-1 text-xs font-medium rounded-full',
                    client.statut === 'client' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ client.statut === 'client' ? 'Client' : 'Prospect' }}
                  </span>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button @click="viewClient(client)" class="text-indigo-600 hover:text-indigo-800" title="Voir">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editClient(client)" class="text-blue-600 hover:text-blue-800" title="Modifier">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="deleteClient(client)" class="text-red-600 hover:text-red-800" title="Supprimer">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            Page {{ pagination.current_page }} sur {{ pagination.last_page }}
          </div>
          <div class="flex space-x-2">
            <button 
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-4 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Précédent
            </button>
            <button 
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-4 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold text-gray-900">
                {{ isEditing ? 'Modifier Client' : 'Nouveau Client' }}
              </h2>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <form @submit.prevent="saveClient" class="p-6">
            <!-- Informations Client Fieldset -->
            <fieldset class="border border-gray-300 rounded-lg p-4 mb-6">
              <legend class="text-sm font-semibold text-gray-700 px-2">Informations Client</legend>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- ICE/CIN -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    ICE/CIN:<span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="form.ice_cin"
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                  <p v-if="errors.ice_cin" class="text-red-500 text-xs mt-1">{{ errors.ice_cin[0] }}</p>
                </div>

                <!-- Nom -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nom:<span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="form.nom"
                    type="text" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                  <p v-if="errors.nom" class="text-red-500 text-xs mt-1">{{ errors.nom[0] }}</p>
                </div>

                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                  <input 
                    v-model="form.email"
                    type="email" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                  <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
                </div>

                <!-- Téléphone -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone:</label>
                  <input 
                    v-model="form.telephone"
                    type="tel" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>

                <!-- Adresse -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Adresse:</label>
                  <input 
                    v-model="form.adresse"
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

                <!-- Catégorie -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie:</label>
                  <select 
                    v-model="form.categorie"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white"
                  >
                    <option value="local">local</option>
                    <option value="international">international</option>
                    <option value="export">export</option>
                  </select>
                </div>

                <!-- Devise -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Devise:</label>
                  <select 
                    v-model="form.devise"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white"
                  >
                    <option value="MAD">MAD</option>
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                  </select>
                </div>
              </div>
            </fieldset>

            <!-- Statut -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Statut:</label>
              <div class="flex space-x-4">
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="form.statut" 
                    value="client"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Client</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="form.statut" 
                    value="prospect"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Prospect</span>
                </label>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
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

      <!-- View Modal -->
      <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold text-gray-900">Détails du Client</h2>
              <button @click="showViewModal = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <div class="p-6" v-if="selectedClient">
            <div class="space-y-4">
              <div class="flex items-center space-x-4 mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                  <span class="text-2xl text-indigo-600 font-bold">{{ getInitials(selectedClient.nom) }}</span>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-gray-900">{{ selectedClient.nom }}</h3>
                  <span :class="[
                    'px-3 py-1 text-xs font-medium rounded-full',
                    selectedClient.statut === 'client' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ selectedClient.statut === 'client' ? 'Client' : 'Prospect' }}
                  </span>
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-500">ICE/CIN</p>
                  <p class="font-medium">{{ selectedClient.ice_cin }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Email</p>
                  <p class="font-medium">{{ selectedClient.email || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Téléphone</p>
                  <p class="font-medium">{{ selectedClient.telephone || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Pays</p>
                  <p class="font-medium">{{ selectedClient.pays || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Catégorie</p>
                  <p class="font-medium">{{ selectedClient.categorie }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Devise</p>
                  <p class="font-medium">{{ selectedClient.devise }}</p>
                </div>
              </div>
              
              <div>
                <p class="text-sm text-gray-500">Adresse</p>
                <p class="font-medium">{{ selectedClient.adresse || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClientsProspects',
  data() {
    return {
      clients: [],
      loading: false,
      saving: false,
      showModal: false,
      showViewModal: false,
      isEditing: false,
      selectedClient: null,
      searchQuery: '',
      searchTimeout: null,
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      },
      form: {
        id: null,
        ice_cin: '',
        nom: '',
        email: '',
        telephone: '',
        adresse: '',
        pays: '',
        categorie: 'local',
        devise: 'MAD',
        statut: 'client'
      },
      errors: {}
    };
  },
  mounted() {
    this.fetchClients();
  },
  methods: {
    getCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },
    
    async fetchClients(page = 1) {
      this.loading = true;
      try {
        const params = new URLSearchParams({
          page: page,
          search: this.searchQuery
        });
        const response = await fetch(`/api/clients?${params}`);
        const data = await response.json();
        
        this.clients = data.data;
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total
        };
      } catch (error) {
        console.error('Error fetching clients:', error);
      } finally {
        this.loading = false;
      }
    },
    
    searchClients() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchClients(1);
      }, 300);
    },
    
    changePage(page) {
      this.fetchClients(page);
    },
    
    openCreateModal() {
      this.isEditing = false;
      this.resetForm();
      this.showModal = true;
    },
    
    editClient(client) {
      this.isEditing = true;
      this.form = { ...client };
      this.showModal = true;
    },
    
    viewClient(client) {
      this.selectedClient = client;
      this.showViewModal = true;
    },
    
    closeModal() {
      this.showModal = false;
      this.resetForm();
    },
    
    resetForm() {
      this.form = {
        id: null,
        ice_cin: '',
        nom: '',
        email: '',
        telephone: '',
        adresse: '',
        pays: '',
        categorie: 'local',
        devise: 'MAD',
        statut: 'client'
      };
      this.errors = {};
    },
    
    async saveClient() {
      this.saving = true;
      this.errors = {};
      
      try {
        const url = this.isEditing ? `/api/clients/${this.form.id}` : '/api/clients';
        const method = this.isEditing ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          },
          body: JSON.stringify(this.form)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
          if (response.status === 422) {
            this.errors = data.errors || {};
          } else {
            alert(data.message || 'Une erreur est survenue');
          }
          return;
        }
        
        this.closeModal();
        this.fetchClients(this.pagination.current_page);
        alert(data.message);
        
      } catch (error) {
        console.error('Error saving client:', error);
        alert('Une erreur est survenue');
      } finally {
        this.saving = false;
      }
    },
    
    async deleteClient(client) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer le client "${client.nom}" ?`)) {
        return;
      }
      
      try {
        const response = await fetch(`/api/clients/${client.id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
          alert(data.message || 'Une erreur est survenue');
          return;
        }
        
        this.fetchClients(this.pagination.current_page);
        alert(data.message);
        
      } catch (error) {
        console.error('Error deleting client:', error);
        alert('Une erreur est survenue');
      }
    },
    
    getInitials(name) {
      if (!name) return '??';
      const parts = name.split(' ');
      if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return name.substring(0, 2).toUpperCase();
    }
  }
};
</script>
