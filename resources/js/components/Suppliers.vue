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
            + Ajouter Fournisseur
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement...</p>
        </div>

        <!-- Data Table -->
        <div v-else-if="fournisseurs.length > 0" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">ICE</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Code Fournisseur</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Pays</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">État</th>
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
                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                      <span class="text-teal-600 font-semibold">{{ getInitials(fournisseur.nom) }}</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ fournisseur.nom }}</p>
                      <p class="text-sm text-gray-500">{{ fournisseur.nom_alternatif || fournisseur.categorie }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.ice }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.code_fournisseur || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.email || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.telephone || '-' }}</td>
                <td class="py-4 px-4 text-gray-600">{{ fournisseur.pays || '-' }}</td>
                <td class="py-4 px-4">
                  <span :class="[
                    'px-3 py-1 text-xs font-medium rounded-full',
                    fournisseur.etat === 'Ouvert' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]">
                    {{ fournisseur.etat || 'Ouvert' }}
                  </span>
                </td>
                <td class="py-4 px-4">
                  <div class="flex space-x-2">
                    <button @click="openModal(fournisseur)" class="text-teal-600 hover:text-teal-800">
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
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-purple-50 border-b border-purple-200 px-6 py-4 z-10">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-purple-600 rounded flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h2 class="text-lg font-semibold text-purple-800">
                Nouveau tiers (prospect, client, fournisseur)
              </h2>
            </div>
          </div>
          
          <form @submit.prevent="saveFournisseur" class="p-6">
            <!-- Nom du tiers -->
            <div class="mb-6">
              <label class="block text-sm font-semibold text-purple-700 mb-2">Nom du tiers</label>
              <input 
                v-model="form.nom" 
                type="text" 
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder="Nom de l'entreprise"
              />
            </div>

            <!-- Nom alternatif -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nom alternatif (commercial, marque, ...)</label>
              <input 
                v-model="form.nom_alternatif" 
                type="text" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder="Nom commercial ou marque"
              />
            </div>

            <!-- Code client / Code fournisseur -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code client</label>
                <div class="flex">
                  <input 
                    v-model="form.code_client" 
                    type="text" 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    placeholder="CU2605-00016"
                  />
                  <button type="button" class="px-3 py-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-500 hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code fournisseur</label>
                <div class="flex">
                  <input 
                    v-model="form.code_fournisseur" 
                    type="text" 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    placeholder="SU2605-00005"
                  />
                  <button type="button" class="px-3 py-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-500 hover:bg-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- État -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">État</label>
              <select 
                v-model="form.etat"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
              >
                <option value="Ouvert">Ouvert</option>
                <option value="Fermé">Fermé</option>
                <option value="En attente">En attente</option>
              </select>
            </div>

            <!-- Adresse -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
              <textarea 
                v-model="form.adresse" 
                rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder="Adresse complète"
              ></textarea>
            </div>

            <!-- Code postal / Ville -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                <input 
                  v-model="form.code_postal" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="20000"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                <input 
                  v-model="form.ville" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Casablanca"
                />
              </div>
            </div>

            <!-- Pays -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">🌍</span>
                </div>
                <select 
                  v-model="form.pays"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Maroc (MA)">Maroc (MA)</option>
                  <option value="France (FR)">France (FR)</option>
                  <option value="Espagne (ES)">Espagne (ES)</option>
                  <option value="Allemagne (DE)">Allemagne (DE)</option>
                  <option value="Belgique (BE)">Belgique (BE)</option>
                  <option value="Italie (IT)">Italie (IT)</option>
                </select>
              </div>
            </div>

            <!-- Département / Canton -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Département / Canton</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">🏛️</span>
                </div>
                <input 
                  v-model="form.departement" 
                  type="text" 
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Département ou Canton"
                />
              </div>
            </div>

            <!-- Téléphone / Tél portable -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">📞</span>
                  </div>
                  <input 
                    v-model="form.telephone" 
                    type="tel" 
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    placeholder="+212 5 22 XX XX XX"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tél portable</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">📱</span>
                  </div>
                  <input 
                    v-model="form.tel_portable" 
                    type="tel" 
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    placeholder="+212 6 XX XX XX XX"
                  />
                </div>
              </div>
            </div>

            <!-- Fax -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Fax</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">📠</span>
                </div>
                <input 
                  v-model="form.fax" 
                  type="text" 
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="+212 5 22 XX XX XX"
                />
              </div>
            </div>

            <!-- Web -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Web</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">🌐</span>
                </div>
                <input 
                  v-model="form.web" 
                  type="url" 
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="https://www.exemple.com"
                />
              </div>
            </div>

            <!-- Email -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">@</span>
                </div>
                <input 
                  v-model="form.email" 
                  type="email" 
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="contact@exemple.com"
                />
              </div>
            </div>

            <!-- Id. prof. 1-4 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Id. prof. 1 (R.C.)</label>
                <input 
                  v-model="form.id_prof_1_rc" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Registre de Commerce"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Id. prof. 2 (Patente)</label>
                <input 
                  v-model="form.id_prof_2_patente" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Numéro de Patente"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Id. prof. 3 (I.F.)</label>
                <input 
                  v-model="form.id_prof_3_if" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Identifiant Fiscal"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Id. prof. 4 (C.N.S.S.)</label>
                <input 
                  v-model="form.id_prof_4_cnss" 
                  type="text" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="Numéro CNSS"
                />
              </div>
            </div>

            <!-- ICE -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Identifiant Commun d'Entreprise (ICE)</label>
              <input 
                v-model="form.ice" 
                type="text" 
                required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder="00000000000000"
              />
            </div>

            <!-- Assujetti à la TVA / Numéro de TVA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Assujetti à la TVA</label>
                <label class="flex items-center mt-2">
                  <input type="checkbox" v-model="form.assujetti_tva" class="w-5 h-5 text-teal-600 border-gray-300 rounded focus:ring-teal-500" />
                  <span class="ml-3 text-sm text-gray-700">Oui</span>
                </label>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de TVA</label>
                <input 
                  v-model="form.numero_tva" 
                  type="text" 
                  :disabled="!form.assujetti_tva"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100 disabled:text-gray-400"
                  placeholder="MA000000000"
                />
              </div>
            </div>

            <!-- Type du tiers / Effectifs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type du tiers</label>
                <select 
                  v-model="form.type_tiers"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Fournisseur">Fournisseur</option>
                  <option value="Client">Client</option>
                  <option value="Prospect">Prospect</option>
                  <option value="Client/Fournisseur">Client/Fournisseur</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Effectifs</label>
                <select 
                  v-model="form.effectifs"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="1-10">1-10 employés</option>
                  <option value="11-50">11-50 employés</option>
                  <option value="51-200">51-200 employés</option>
                  <option value="201-500">201-500 employés</option>
                  <option value="500+">Plus de 500 employés</option>
                </select>
              </div>
            </div>

            <!-- Type d'entité légale -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Type d'entité légale</label>
              <select 
                v-model="form.type_entite_legale"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
              >
                <option value="">Sélectionner</option>
                <option value="SARL">SARL</option>
                <option value="SA">SA</option>
                <option value="SAS">SAS</option>
                <option value="EURL">EURL</option>
                <option value="Auto-entrepreneur">Auto-entrepreneur</option>
                <option value="Association">Association</option>
              </select>
            </div>

            <!-- Capital -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Capital</label>
              <div class="flex">
                <input 
                  v-model.number="form.capital" 
                  type="number" 
                  step="0.01"
                  class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="0"
                />
                <select 
                  v-model="form.devise_capital"
                  class="px-4 py-3 border border-l-0 border-gray-300 rounded-r-lg bg-white focus:ring-2 focus:ring-teal-500"
                >
                  <option value="MAD">MAD</option>
                  <option value="EUR">EUR</option>
                  <option value="USD">USD</option>
                </select>
              </div>
            </div>

            <!-- Conditions de règlement / Mode de règlement -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Conditions de règlement</label>
                <select 
                  v-model="form.conditions_reglement"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Comptant">Comptant</option>
                  <option value="30 jours">30 jours</option>
                  <option value="60 jours">60 jours</option>
                  <option value="90 jours">90 jours</option>
                  <option value="Fin de mois">Fin de mois</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode de règlement</label>
                <select 
                  v-model="form.mode_reglement"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Virement">Virement bancaire</option>
                  <option value="Chèque">Chèque</option>
                  <option value="Espèces">Espèces</option>
                  <option value="Effet">Effet de commerce</option>
                  <option value="Carte">Carte bancaire</option>
                </select>
              </div>
            </div>

            <!-- Maison mère -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Maison mère</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">🏢</span>
                </div>
                <select 
                  v-model="form.maison_mere"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white"
                >
                  <option value="">Sélectionner un tiers</option>
                  <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.nom }}</option>
                </select>
              </div>
            </div>

            <!-- Assigner des commerciaux -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Assigner des commerciaux</label>
              <div class="flex flex-wrap gap-2">
                <span v-for="(commercial, index) in form.commerciaux" :key="index" class="inline-flex items-center px-3 py-1 bg-teal-100 text-teal-800 rounded-full text-sm">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ commercial }}
                  <button type="button" @click="removeCommercial(index)" class="ml-2 text-teal-600 hover:text-teal-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <input 
                  type="text" 
                  v-model="newCommercial"
                  @keypress.enter.prevent="addCommercial"
                  class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 text-sm"
                  placeholder="Ajouter un commercial..."
                />
              </div>
            </div>

            <!-- Logo -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
              <div class="flex items-center gap-4">
                <input 
                  type="file" 
                  @change="handleLogoUpload"
                  accept="image/*"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                />
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-600">
              {{ errorMessage }}
            </div>

            <!-- Form Actions -->
            <div class="flex justify-center gap-4 pt-6 border-t border-gray-200">
              <button 
                type="submit"
                :disabled="saving"
                class="px-8 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium disabled:opacity-50"
              >
                {{ saving ? 'Enregistrement...' : 'CRÉER TIERS' }}
              </button>
              <button 
                type="button"
                @click="closeModal"
                class="px-8 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-medium"
              >
                ANNULER
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
      newCommercial: '',
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      },
      form: this.getEmptyForm()
    };
  },
  mounted() {
    this.fetchFournisseurs();
  },
  methods: {
    getEmptyForm() {
      return {
        nom: '',
        nom_alternatif: '',
        code_client: '',
        code_fournisseur: '',
        etat: 'Ouvert',
        adresse: '',
        code_postal: '',
        ville: '',
        pays: 'Maroc (MA)',
        departement: '',
        telephone: '',
        tel_portable: '',
        fax: '',
        web: '',
        email: '',
        id_prof_1_rc: '',
        id_prof_2_patente: '',
        id_prof_3_if: '',
        id_prof_4_cnss: '',
        ice: '',
        assujetti_tva: true,
        numero_tva: '',
        type_tiers: 'Fournisseur',
        effectifs: '',
        type_entite_legale: '',
        capital: null,
        devise_capital: 'MAD',
        conditions_reglement: '',
        mode_reglement: '',
        maison_mere: '',
        commerciaux: [],
        logo: null,
        categorie: 'local',
        devise: 'MAD',
        eori: ''
      };
    },
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
        this.form = { ...this.getEmptyForm(), ...fournisseur };
        if (typeof this.form.commerciaux === 'string') {
          this.form.commerciaux = this.form.commerciaux ? this.form.commerciaux.split(',') : [];
        }
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
    addCommercial() {
      if (this.newCommercial.trim()) {
        if (!this.form.commerciaux) {
          this.form.commerciaux = [];
        }
        this.form.commerciaux.push(this.newCommercial.trim());
        this.newCommercial = '';
      }
    },
    removeCommercial(index) {
      this.form.commerciaux.splice(index, 1);
    },
    handleLogoUpload(event) {
      const file = event.target.files[0];
      if (file) {
        this.form.logo = file;
      }
    },
    async saveFournisseur() {
      this.saving = true;
      this.errorMessage = '';
      try {
        const formData = {
          ...this.form,
          commerciaux: Array.isArray(this.form.commerciaux) ? this.form.commerciaux.join(',') : this.form.commerciaux
        };
        
        if (this.isEditing) {
          await axios.put(`/api/fournisseurs/${this.editingId}`, formData);
        } else {
          await axios.post('/api/fournisseurs', formData);
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
