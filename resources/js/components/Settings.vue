<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Paramètres</h1>
        <p class="text-gray-600">Configurez les paramètres et données de référence du système</p>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="border-b border-gray-200">
          <nav class="flex -mb-px overflow-x-auto">
            <button
              @click="activeTab = 'parametres'"
              :class="[
                'px-6 py-4 text-sm font-medium whitespace-nowrap',
                activeTab === 'parametres' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700'
              ]"
            >
              Paramètres de référence
            </button>
            <button
              @click="activeTab = 'entreprise'"
              :class="[
                'px-6 py-4 text-sm font-medium whitespace-nowrap',
                activeTab === 'entreprise' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700'
              ]"
            >
              Entreprise
            </button>
            <button
              @click="activeTab = 'systeme'"
              :class="[
                'px-6 py-4 text-sm font-medium whitespace-nowrap',
                activeTab === 'systeme' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700'
              ]"
            >
              Système
            </button>
          </nav>
        </div>
      </div>

      <!-- Parametres Tab -->
      <div v-if="activeTab === 'parametres'" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Types List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Types de paramètres</h3>
          <div class="space-y-1">
            <button
              v-for="(label, type) in parametreTypes"
              :key="type"
              @click="selectType(type)"
              :class="[
                'w-full text-left px-4 py-3 rounded-lg text-sm transition-colors',
                selectedType === type ? 'bg-teal-50 text-teal-700 font-medium' : 'text-gray-600 hover:bg-gray-50'
              ]"
            >
              {{ label }}
            </button>
          </div>
        </div>

        <!-- Parametres List -->
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-900">{{ parametreTypes[selectedType] || 'Sélectionnez un type' }}</h3>
            <button
              v-if="selectedType"
              @click="openCreateModal"
              class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-medium"
            >
              + Ajouter
            </button>
          </div>

          <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-teal-600 mx-auto"></div>
            <p class="mt-4 text-gray-500">Chargement...</p>
          </div>

          <div v-else-if="parametres.length === 0" class="text-center py-12 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <p>{{ selectedType ? 'Aucun paramètre trouvé' : 'Sélectionnez un type de paramètre' }}</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordre</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valeur</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="param in parametres" :key="param.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm text-gray-500">{{ param.ordre }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ param.valeur }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ param.code || '-' }}</td>
                  <td class="px-4 py-3">
                    <span :class="param.actif ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded-full text-xs font-medium">
                      {{ param.actif ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <div class="flex space-x-2">
                      <button @click="openEditModal(param)" class="text-teal-600 hover:text-teal-900" title="Modifier">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button @click="toggleParamActive(param)" :class="param.actif ? 'text-green-600' : 'text-gray-400'" title="Activer/Désactiver">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </button>
                      <button @click="deleteParam(param)" class="text-red-600 hover:text-red-900" title="Supprimer">
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
        </div>
      </div>

      <!-- Other tabs placeholder -->
      <div v-if="activeTab !== 'parametres'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <p class="text-lg mb-2">{{ activeTab === 'entreprise' ? 'Paramètres Entreprise' : 'Paramètres Système' }}</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="border-b border-gray-200 px-6 py-4">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? 'Modifier le paramètre' : 'Nouveau paramètre' }}
          </h3>
        </div>
        <form @submit.prevent="saveParam" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Valeur *</label>
            <input
              v-model="form.valeur"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
              placeholder="Entrez la valeur"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Code (optionnel)</label>
            <input
              v-model="form.code"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
              placeholder="Code court"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ordre</label>
            <input
              v-model.number="form.ordre"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
            />
          </div>
          <div v-if="selectedType === 'sous_famille'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Famille parente</label>
            <select
              v-model="form.parent_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white"
            >
              <option :value="null">Aucune</option>
              <option v-for="famille in familles" :key="famille.id" :value="famille.id">{{ famille.valeur }}</option>
            </select>
          </div>
          <div class="flex items-center">
            <input
              v-model="form.actif"
              type="checkbox"
              id="actif"
              class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
            />
            <label for="actif" class="ml-2 text-sm text-gray-700">Actif</label>
          </div>
          <div class="flex justify-end gap-4 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50"
            >
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Settings',
  data() {
    return {
      activeTab: 'parametres',
      selectedType: 'famille',
      parametres: [],
      familles: [],
      loading: false,
      saving: false,
      showModal: false,
      isEditing: false,
      parametreTypes: {
        famille: 'Familles',
        sous_famille: 'Sous-familles',
        calibre: 'Calibres',
        type_emballage_primaire: 'Types emballage primaire',
        type_emballage_secondaire: 'Types emballage secondaire',
        type_palette: 'Types de palette',
        unite_facturation: 'Unités de facturation',
        devise: 'Devises',
        taux_tva: 'Taux TVA',
        marche: 'Marchés',
        secteur_activite: 'Secteurs d\'activité',
        groupe_categorie: 'Groupes / Catégories',
        incoterm: 'Incoterms',
        delai_paiement: 'Délais de paiement'
      },
      form: {
        id: null,
        type: '',
        valeur: '',
        code: '',
        ordre: 0,
        actif: true,
        parent_id: null
      }
    };
  },
  mounted() {
    this.fetchParametres();
    this.fetchFamilles();
  },
  methods: {
    async fetchParametres() {
      if (!this.selectedType) return;
      this.loading = true;
      try {
        const response = await fetch(`/api/parametres/type/${this.selectedType}`);
        if (response.ok) {
          this.parametres = await response.json();
        }
      } catch (error) {
        console.error('Error fetching parametres:', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchFamilles() {
      try {
        const response = await fetch('/api/parametres/type/famille');
        if (response.ok) {
          this.familles = await response.json();
        }
      } catch (error) {
        console.error('Error fetching familles:', error);
      }
    },
    selectType(type) {
      this.selectedType = type;
      this.fetchParametres();
    },
    openCreateModal() {
      this.isEditing = false;
      this.form = {
        id: null,
        type: this.selectedType,
        valeur: '',
        code: '',
        ordre: this.parametres.length,
        actif: true,
        parent_id: null
      };
      this.showModal = true;
    },
    openEditModal(param) {
      this.isEditing = true;
      this.form = { ...param };
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.form = { id: null, type: '', valeur: '', code: '', ordre: 0, actif: true, parent_id: null };
    },
    async saveParam() {
      this.saving = true;
      try {
        const url = this.isEditing ? `/api/parametres/${this.form.id}` : '/api/parametres';
        const method = this.isEditing ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.form)
        });
        
        if (response.ok) {
          this.closeModal();
          this.fetchParametres();
          if (this.selectedType === 'famille') {
            this.fetchFamilles();
          }
        } else {
          const error = await response.json();
          alert(error.message || 'Une erreur est survenue');
        }
      } catch (error) {
        console.error('Error saving param:', error);
        alert('Une erreur est survenue');
      } finally {
        this.saving = false;
      }
    },
    async toggleParamActive(param) {
      try {
        const response = await fetch(`/api/parametres/${param.id}/toggle-active`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          const data = await response.json();
          param.actif = data.parametre.actif;
        }
      } catch (error) {
        console.error('Error toggling param:', error);
      }
    },
    async deleteParam(param) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer "${param.valeur}" ?`)) return;
      
      try {
        const response = await fetch(`/api/parametres/${param.id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          this.fetchParametres();
        } else {
          alert('Erreur lors de la suppression');
        }
      } catch (error) {
        console.error('Error deleting param:', error);
        alert('Une erreur est survenue');
      }
    }
  }
};
</script>
