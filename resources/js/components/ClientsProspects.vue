<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Détail du client</h1>
        <p class="text-gray-600">Accueil > Clients > Détail du client</p>
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
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent" 
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <button 
            @click="openCreateModal"
            class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium"
          >
            + Ajouter Client
          </button>
        </div>

        <!-- Clients Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Code Client</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Raison Sociale</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" class="border-b border-gray-100">
                <td colspan="7" class="py-8 text-center text-gray-500">
                  <div class="flex items-center justify-center space-x-2">
                    <svg class="animate-spin h-5 w-5 text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
                <td class="py-4 px-4 text-gray-600 font-medium">{{ client.code_client || client.ice_cin }}</td>
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                      <span class="text-teal-600 font-semibold">{{ getInitials(client.nom) }}</span>
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
                    {{ client.statut === 'client' ? 'Actif' : 'Prospect' }}
                  </span>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button @click="viewClient(client)" class="text-teal-600 hover:text-teal-800" title="Voir">
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
                    <button @click="openAffectModal(client)" class="text-purple-600 hover:text-purple-800" title="Affecter articles">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                      </svg>
                    </button>
                    <button @click="toggleActive(client)" :class="client.actif !== false ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600'" :title="client.actif !== false ? 'Désactiver' : 'Activer'">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
        <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-xl font-bold text-gray-900">
              {{ isEditing ? 'Modifier Client' : 'Nouveau Client' }}
            </h2>
            <div class="flex items-center gap-4">
              <button @click="closeModal" class="px-4 py-2 text-gray-600 hover:text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour
              </button>
              <button @click="saveClient" :disabled="saving" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium disabled:opacity-50 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ saving ? 'Enregistrement...' : 'Modifier' }}
              </button>
            </div>
          </div>

          <form @submit.prevent="saveClient" class="p-6">
            <!-- Header Info Section -->
            <div class="mb-8 bg-white rounded-lg p-6 border border-teal-200 shadow-sm">
              <div class="flex items-center gap-6 mb-6">
                <div class="w-20 h-20 bg-teal-100 rounded-lg flex items-center justify-center">
                  <span class="text-2xl text-teal-600 font-bold">{{ getInitials(form.nom) }}</span>
                </div>
                <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Code client</label>
                    <input v-model="form.code_client" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="CLI-00045" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Raison sociale *</label>
                    <input v-model="form.nom" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Olive World Ltd" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Type</label>
                    <select v-model="form.categorie" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                      <option value="local">Local</option>
                      <option value="export">Export</option>
                      <option value="international">International</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Statut</label>
                    <span :class="form.statut === 'client' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" class="inline-block px-3 py-2 rounded-lg text-sm font-medium">
                      {{ form.statut === 'client' ? 'Actif' : 'Prospect' }}
                    </span>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Date de création</label>
                    <input v-model="form.date_creation" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" />
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Secteur d'activité</label>
                  <select v-model="form.secteur_activite" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                    <option value="">Sélectionner</option>
                    <option v-for="item in parametres.secteur_activite" :key="item.id" :value="item.valeur">{{ item.valeur }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Groupe / Catégorie</label>
                  <select v-model="form.groupe_categorie" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                    <option value="">Sélectionner</option>
                    <option v-for="item in parametres.groupe_categorie" :key="item.id" :value="item.valeur">{{ item.valeur }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500 mb-1">Commercial en charge</label>
                  <input v-model="form.commercial_charge" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Jean Dupont" />
                </div>
              </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-6 overflow-x-auto">
              <button type="button" @click="activeTab = 'informations'" :class="['px-4 py-3 text-sm font-medium whitespace-nowrap', activeTab === 'informations' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700']">
                <span class="flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  Informations
                </span>
              </button>
              <button type="button" @click="activeTab = 'articles'" :class="['px-4 py-3 text-sm font-medium whitespace-nowrap', activeTab === 'articles' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500 hover:text-gray-700']">
                <span class="flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                  Articles liés
                </span>
              </button>
            </div>

            <!-- Tab Content: Informations -->
            <div v-show="activeTab === 'informations'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Coordonnées -->
              <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">2</span>
                  Coordonnées
                </h4>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse complète</label>
                    <textarea v-model="form.adresse" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="25 Rue de la République"></textarea>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input v-model="form.ville" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Paris" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                    <select v-model="form.pays" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                      <option value="">Sélectionner</option>
                      <option value="France">France</option>
                      <option value="Maroc">Maroc</option>
                      <option value="Espagne">Espagne</option>
                      <option value="Allemagne">Allemagne</option>
                      <option value="Belgique">Belgique</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input v-model="form.telephone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="+33 1 42 86 95 10" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="contact@oliveworld.com" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site web</label>
                    <input v-model="form.site_web" type="url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="www.oliveworld.com" />
                  </div>
                </div>
              </div>

              <!-- Contact principal -->
              <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">3</span>
                  Contact principal
                </h4>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du contact</label>
                    <input v-model="form.contact_nom" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Jean DUPONT" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fonction</label>
                    <input v-model="form.contact_fonction" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Responsable achat" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone direct</label>
                    <input v-model="form.contact_telephone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="+33 6 12 34 56 78" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="form.contact_email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="jean.dupont@oliveworld.com" />
                  </div>
                </div>
              </div>

              <!-- Autres informations (identifiants) -->
              <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Autres informations (identifiants)</h4>
                <div class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">N° TVA</label>
                      <input v-model="form.numero_tva" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="FR12345678901" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">N° RC / Registre</label>
                      <input v-model="form.numero_rc" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="B123456" />
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">EORI</label>
                      <input v-model="form.eori" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="FR123456789000" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Pays EORI</label>
                      <input v-model="form.pays_eori" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="France" />
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Incoterm par défaut</label>
                      <select v-model="form.incoterm" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                        <option value="">Sélectionner</option>
                        <option v-for="item in parametres.incoterm" :key="item.id" :value="item.code">{{ item.valeur }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Devise par défaut</label>
                      <select v-model="form.devise" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                        <option value="">Sélectionner</option>
                        <option v-for="item in parametres.devise" :key="item.id" :value="item.code">{{ item.valeur }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                      <select v-model="form.langue" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                        <option value="Français">Français</option>
                        <option value="Anglais">Anglais</option>
                        <option value="Espagnol">Espagnol</option>
                        <option value="Arabe">Arabe</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Délai de paiement</label>
                      <select v-model="form.delai_paiement_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white">
                        <option value="">Sélectionner</option>
                        <option v-for="item in parametres.delai_paiement" :key="item.id" :value="item.code">{{ item.valeur }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" v-model="form.marque" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                        <span class="text-sm font-medium text-gray-700">Marque</span>
                      </label>
                    </div>
                    <div v-if="form.marque">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Nomination</label>
                      <input v-model="form.nomination" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Entrez la nomination" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Logistique -->
              <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">4</span>
                  Logistique
                </h4>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse de livraison</label>
                    <textarea v-model="form.adresse_livraison" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Zone industrielle, Bâtiment A&#10;69007 Lyon, France"></textarea>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transitaire (pour export)</label>
                    <textarea v-model="form.transitaire" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Transports Internationaux SAS&#10;10 Quai de la Gare, 76600 Le Havre, France"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab Content: Articles (placeholder) -->
            <div v-show="activeTab === 'articles'" class="bg-gray-50 rounded-lg p-6 border border-gray-200">
              <h4 class="text-lg font-semibold text-gray-900 mb-4">Articles liés</h4>
              <p class="text-gray-500 mb-4">Articles achetés + prix négocié</p>
              <button type="button" @click="openAffectModal(form)" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                + Affecter des articles
              </button>
            </div>

          </form>
        </div>
      </div>

      <!-- View Modal -->
      <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-xl font-bold text-gray-900">Détails du Client</h2>
            <button @click="showViewModal = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6" v-if="selectedClient">
            <!-- Header with client info -->
            <div class="flex items-center space-x-4 mb-6 pb-6 border-b border-gray-200">
              <div class="w-20 h-20 bg-teal-100 rounded-full flex items-center justify-center">
                <span class="text-2xl text-teal-600 font-bold">{{ getInitials(selectedClient.nom) }}</span>
              </div>
              <div class="flex-1">
                <h3 class="text-2xl font-semibold text-gray-900">{{ selectedClient.nom }}</h3>
                <div class="flex items-center gap-3 mt-2">
                  <span :class="[
                    'px-3 py-1 text-xs font-medium rounded-full',
                    selectedClient.statut === 'client' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ selectedClient.statut === 'client' ? 'Actif' : 'Prospect' }}
                  </span>
                  <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                    {{ selectedClient.categorie || 'Non défini' }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Client Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <!-- Informations générales -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Informations générales
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Code Client</span>
                    <span class="text-sm font-medium">{{ selectedClient.code_client || selectedClient.ice_cin || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">ICE/CIN</span>
                    <span class="text-sm font-medium">{{ selectedClient.ice_cin || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Secteur d'activité</span>
                    <span class="text-sm font-medium">{{ selectedClient.secteur_activite || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Commercial</span>
                    <span class="text-sm font-medium">{{ selectedClient.commercial_charge || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Date de création</span>
                    <span class="text-sm font-medium">{{ selectedClient.date_creation || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Coordonnées -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Coordonnées
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Email</span>
                    <span class="text-sm font-medium">{{ selectedClient.email || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Téléphone</span>
                    <span class="text-sm font-medium">{{ selectedClient.telephone || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Ville</span>
                    <span class="text-sm font-medium">{{ selectedClient.ville || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Pays</span>
                    <span class="text-sm font-medium">{{ selectedClient.pays || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Site web</span>
                    <span class="text-sm font-medium">{{ selectedClient.site_web || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Contact principal -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Contact principal
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Nom</span>
                    <span class="text-sm font-medium">{{ selectedClient.contact_nom || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Fonction</span>
                    <span class="text-sm font-medium">{{ selectedClient.contact_fonction || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Téléphone</span>
                    <span class="text-sm font-medium">{{ selectedClient.contact_telephone || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Email</span>
                    <span class="text-sm font-medium">{{ selectedClient.contact_email || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Informations commerciales -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Informations commerciales
                </h4>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Devise</span>
                    <span class="text-sm font-medium">{{ selectedClient.devise || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Incoterm</span>
                    <span class="text-sm font-medium">{{ selectedClient.incoterm || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Délai paiement</span>
                    <span class="text-sm font-medium">{{ selectedClient.delai_paiement ? selectedClient.delai_paiement + ' jours' : '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Plafond crédit</span>
                    <span class="text-sm font-medium">{{ selectedClient.plafond_credit ? formatNumber(selectedClient.plafond_credit) + ' ' + (selectedClient.devise || 'EUR') : '-' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Adresse complète -->
            <div class="bg-gray-50 rounded-lg p-4 mb-8" v-if="selectedClient.adresse">
              <h4 class="text-sm font-semibold text-gray-900 mb-2">Adresse complète</h4>
              <p class="text-sm text-gray-700">{{ selectedClient.adresse }}</p>
            </div>

            <!-- Articles affectés -->
            <div class="border-t border-gray-200 pt-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  Articles affectés
                </h4>
                <span class="px-3 py-1 text-sm font-medium bg-teal-100 text-teal-800 rounded-full">
                  {{ selectedClient.articles ? selectedClient.articles.length : 0 }} article(s)
                </span>
              </div>
              
              <div v-if="selectedClient.articles && selectedClient.articles.length > 0" class="border rounded-lg overflow-hidden">
                <table class="w-full">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Code</th>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Désignation</th>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Code à barres</th>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Marque</th>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Prix standard</th>
                      <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Prix négocié</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="article in selectedClient.articles" :key="article.id" class="border-t border-gray-100 hover:bg-gray-50">
                      <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ article.code_article || article.id }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ article.designation }}</td>
                      <td class="px-4 py-3 text-sm text-gray-500">{{ (article.pivot && article.pivot.code_barres) || article.code_barres || '-' }}</td>
                      <td class="px-4 py-3 text-sm text-gray-500">{{ (article.pivot && article.pivot.marque) || article.marque || '-' }}</td>
                      <td class="px-4 py-3 text-sm text-gray-500">{{ formatNumber(article.prix_vente) }} {{ article.devise || 'EUR' }}</td>
                      <td class="px-4 py-3 text-sm">
                        <span v-if="article.pivot && article.pivot.prix_negocie" class="font-medium text-teal-600">
                          {{ formatNumber(article.pivot.prix_negocie) }} {{ article.devise || 'EUR' }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <div v-else class="bg-gray-50 rounded-lg p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-gray-500">Aucun article affecté à ce client</p>
                <button @click="showViewModal = false; openAffectModal(selectedClient)" class="mt-4 px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700">
                  + Affecter des articles
                </button>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
              <button @click="showViewModal = false; openAffectModal(selectedClient)" class="px-4 py-2 border border-teal-600 text-teal-600 rounded-lg hover:bg-teal-50">
                Gérer les articles
              </button>
              <button @click="showViewModal = false; editClient(selectedClient)" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                Modifier le client
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Affect Articles Modal -->
      <div v-if="showAffectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <h2 class="text-xl font-bold text-gray-900">Affecter des articles à {{ affectClient?.nom }}</h2>
            <button @click="showAffectModal = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6">
            <div class="mb-4">
              <div class="relative">
                <input 
                  v-model="articleSearchQuery"
                  @input="searchArticlesForAffect"
                  type="text" 
                  placeholder="Rechercher un article..." 
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent" 
                />
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
            <div class="border rounded-lg overflow-hidden">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="w-12 px-4 py-3"></th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Code</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Désignation</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Prix</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Prix négocié</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Code à barres</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Marque</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="article in availableArticles" :key="article.id" class="border-t border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <input type="checkbox" v-model="selectedArticles" :value="article.id" class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500" />
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ article.code_article || article.id }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ article.designation }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ formatNumber(article.prix_vente) }} {{ article.devise || 'EUR' }}</td>
                    <td class="px-4 py-3">
                      <input type="number" v-model.number="negotiatedPrices[article.id]" step="0.01" class="w-24 px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-teal-500 text-sm" :placeholder="article.prix_vente" />
                    </td>
                    <td class="px-4 py-3">
                      <input type="text" v-model="articleCodeBarres[article.id]" class="w-32 px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-teal-500 text-sm" :placeholder="article.code_barres || ''" />
                    </td>
                    <td class="px-4 py-3">
                      <input type="text" v-model="articleMarque[article.id]" class="w-32 px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-teal-500 text-sm" :placeholder="article.marque || ''" />
                    </td>
                  </tr>
                  <tr v-if="availableArticles.length === 0">
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                      Aucun article trouvé
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="mt-6 flex justify-end gap-4">
              <button @click="showAffectModal = false" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Annuler
              </button>
              <button @click="affectArticles" :disabled="selectedArticles.length === 0" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                Affecter {{ selectedArticles.length }} article(s)
              </button>
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
      showAffectModal: false,
      isEditing: false,
      selectedClient: null,
      affectClient: null,
      searchQuery: '',
      articleSearchQuery: '',
      searchTimeout: null,
      activeTab: 'informations',
      availableArticles: [],
      selectedArticles: [],
      negotiatedPrices: {},
      articleCodeBarres: {},
      articleMarque: {},
      parametres: {
        secteur_activite: [],
        groupe_categorie: [],
        incoterm: [],
        devise: [],
        delai_paiement: []
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      },
      form: {
        id: null,
        code_client: '',
        ice_cin: '',
        nom: '',
        email: '',
        telephone: '',
        adresse: '',
        ville: '',
        pays: '',
        categorie: 'export',
        devise: 'EUR',
        statut: 'client',
        date_creation: '',
        secteur_activite: '',
        groupe_categorie: '',
        commercial_charge: '',
        site_web: '',
        contact_nom: '',
        contact_fonction: '',
        contact_telephone: '',
        contact_email: '',
        numero_tva: '',
        numero_rc: '',
        eori: '',
        pays_eori: '',
        incoterm: '',
        langue: 'Français',
        delai_paiement: 30,
        delai_paiement_type: '',
        actif: true,
        marque: false,
        nomination: '',
        plafond_credit: null,
        solde_actuel: null,
        mode_transport: 'maritime',
        adresse_livraison: '',
        transitaire: ''
      },
      errors: {}
    };
  },
  mounted() {
    this.fetchClients();
    this.fetchParametres();
  },
  methods: {
    async fetchParametres() {
      try {
        const response = await fetch('/api/parametres/all-for-forms');
        if (response.ok) {
          this.parametres = await response.json();
        }
      } catch (error) {
        console.error('Error fetching parametres:', error);
      }
    },
    async toggleActive(client) {
      try {
        const response = await fetch(`/api/clients/${client.id}/toggle-active`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          const data = await response.json();
          client.actif = data.client.actif;
        }
      } catch (error) {
        console.error('Error toggling active:', error);
      }
    },
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
      this.activeTab = 'informations';
      this.resetForm();
      this.showModal = true;
    },
    
    editClient(client) {
      this.isEditing = true;
      this.activeTab = 'informations';
      this.form = { ...this.getEmptyForm(), ...client };
      this.showModal = true;
    },
    
    async viewClient(client) {
      try {
        const response = await fetch(`/api/clients/${client.id}`);
        const data = await response.json();
        this.selectedClient = data;
        this.showViewModal = true;
      } catch (error) {
        console.error('Error fetching client details:', error);
        this.selectedClient = client;
        this.showViewModal = true;
      }
    },
    
    closeModal() {
      this.showModal = false;
      this.resetForm();
    },
    
    getEmptyForm() {
      return {
        id: null,
        code_client: '',
        ice_cin: '',
        nom: '',
        email: '',
        telephone: '',
        adresse: '',
        ville: '',
        pays: '',
        categorie: 'export',
        devise: 'EUR',
        statut: 'client',
        date_creation: '',
        secteur_activite: '',
        groupe_categorie: '',
        commercial_charge: '',
        site_web: '',
        contact_nom: '',
        contact_fonction: '',
        contact_telephone: '',
        contact_email: '',
        numero_tva: '',
        numero_rc: '',
        eori: '',
        pays_eori: '',
        incoterm: '',
        langue: 'Français',
        delai_paiement: 30,
        delai_paiement_type: '',
        actif: true,
        marque: false,
        nomination: '',
        plafond_credit: null,
        solde_actuel: null,
        mode_transport: 'maritime',
        adresse_livraison: '',
        transitaire: ''
      };
    },
    
    resetForm() {
      this.form = this.getEmptyForm();
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

    async openAffectModal(client) {
      this.affectClient = client;
      this.selectedArticles = [];
      this.negotiatedPrices = {};
      this.articleCodeBarres = {};
      this.articleMarque = {};
      this.articleSearchQuery = '';
      await this.fetchArticlesForAffect();
      this.showAffectModal = true;
    },

    async fetchArticlesForAffect() {
      try {
        const params = new URLSearchParams({ search: this.articleSearchQuery });
        const response = await fetch(`/api/articles?${params}`);
        const data = await response.json();
        this.availableArticles = data.data || [];
      } catch (error) {
        console.error('Error fetching articles:', error);
        this.availableArticles = [];
      }
    },

    searchArticlesForAffect() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchArticlesForAffect();
      }, 300);
    },

    async affectArticles() {
      try {
        const articlesToAffect = this.selectedArticles.map(id => ({
          article_id: id,
          prix_negocie: this.negotiatedPrices[id] || null,
          code_barres: this.articleCodeBarres[id] || null,
          marque: this.articleMarque[id] || null
        }));

        const response = await fetch(`/api/clients/${this.affectClient.id}/articles`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken()
          },
          body: JSON.stringify({ articles: articlesToAffect })
        });

        if (response.ok) {
          alert(`${this.selectedArticles.length} article(s) affecté(s) avec succès`);
          this.showAffectModal = false;
        } else {
          const data = await response.json();
          alert(data.message || 'Une erreur est survenue');
        }
      } catch (error) {
        console.error('Error affecting articles:', error);
        alert('Une erreur est survenue lors de l\'affectation');
      }
    },
    
    getInitials(name) {
      if (!name) return '??';
      const parts = name.split(' ');
      if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return name.substring(0, 2).toUpperCase();
    },

    formatCurrency(value) {
      if (!value) return '0,00 €';
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    },

    formatNumber(value) {
      if (value === null || value === undefined) return '-';
      return parseFloat(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }
};
</script>
