<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-full mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion des articles</h1>
        <p class="text-gray-600">Accueil > Articles > Gérez votre catalogue de produits et articles</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm mb-1">Total Articles</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_articles }}</h3>
            </div>
            <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm mb-1">Total Poids Brut (KG)</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.total_poids_brut) }}</h3>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm mb-1">Total Poids Net (KG)</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.total_poids_net) }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Articles Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Catalogue Articles</h3>
          <div class="flex gap-4">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher..."
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                @input="debouncedSearch"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <button 
              @click="openCreateModal"
              class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium"
            >
              + Nouvel Article
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement...</p>
        </div>

        <!-- Table -->
        <div v-else-if="articles.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code Article</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calibre</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Famille</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marque</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix de vente</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actif</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="article in articles" :key="article.id" class="hover:bg-gray-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ article.code_article || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ article.designation }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.calibre || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.famille || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.marque || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatNumber(article.prix_vente) }} {{ article.devise || 'EUR' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm">
                  <span :class="article.actif ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ article.actif ? 'Oui' : 'Non' }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button @click="openEditModal(article)" class="text-teal-600 hover:text-teal-900 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="deleteArticle(article.id)" class="text-red-600 hover:text-red-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="flex items-center justify-between mt-6">
            <p class="text-sm text-gray-500">
              Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} résultats
            </p>
            <div class="flex gap-2">
              <button
                @click="fetchArticles(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Précédent
              </button>
              <button
                @click="fetchArticles(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Suivant
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <p class="text-lg mb-2">Aucun article trouvé</p>
          <p class="text-sm">Commencez par créer un nouvel article</p>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-6xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ isEditing ? 'Modifier l\'article' : 'Nouvel Article' }}
          </h3>
          <div class="flex items-center gap-4">
            <button @click="closeModal" class="px-4 py-2 text-gray-600 hover:text-gray-800 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Retour
            </button>
            <button
              @click="submitForm"
              :disabled="submitting"
              class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors font-medium disabled:opacity-50 flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Enregistrer
            </button>
          </div>
        </div>

        <form @submit.prevent="submitForm" class="p-6">
          <!-- Section 1: Informations générales -->
          <div class="mb-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center text-sm">1</span>
              Informations générales
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code article *</label>
                <input
                  v-model="form.code_article"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="ART-000125"
                />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Désignation *</label>
                <input
                  v-model="form.designation"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Olives Vertes Dénoyautées 16/18"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Calibre</label>
                <select
                  v-model="form.calibre"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="16/18">16/18</option>
                  <option value="19/21">19/21</option>
                  <option value="22/25">22/25</option>
                  <option value="26/29">26/29</option>
                  <option value="30/33">30/33</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Famille *</label>
                <select
                  v-model="form.famille"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Olives">Olives</option>
                  <option value="Huiles">Huiles</option>
                  <option value="Conserves">Conserves</option>
                  <option value="Épices">Épices</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sous-famille</label>
                <select
                  v-model="form.sous_famille"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Olives vertes">Olives vertes</option>
                  <option value="Olives noires">Olives noires</option>
                  <option value="Olives mixtes">Olives mixtes</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marque</label>
                <input
                  v-model="form.marque"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Green Valley"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code-barres</label>
                <input
                  v-model="form.code_barres"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="6198765432105"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">HS Code</label>
                <input
                  v-model="form.hs_code"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="2005.70.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Actif</label>
                <label class="relative inline-flex items-center cursor-pointer mt-2">
                  <input type="checkbox" v-model="form.actif" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                  <span class="ml-3 text-sm font-medium text-gray-700">{{ form.actif ? 'Oui' : 'Non' }}</span>
                </label>
              </div>
              <div class="md:col-span-1 md:row-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo produit</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center h-32 flex flex-col items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <button type="button" class="text-teal-600 text-sm hover:text-teal-700">Changer la photo</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Poids & caractéristiques -->
          <div class="mb-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center text-sm">2</span>
              Poids & caractéristiques
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poids brut (Kg)</label>
                <input
                  v-model.number="form.poids_brut"
                  type="number"
                  step="0.001"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="1.200"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poids net (Kg)</label>
                <input
                  v-model.number="form.poids_net"
                  type="number"
                  step="0.001"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="1.000"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poids net égoutté (Kg)</label>
                <input
                  v-model.number="form.poids_net_egoutte"
                  type="number"
                  step="0.001"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="0.600"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">pH (optionnel)</label>
                <input
                  v-model.number="form.ph"
                  type="number"
                  step="0.01"
                  min="0"
                  max="14"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="4.20"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Taux de sel (%) (optionnel)</label>
                <input
                  v-model.number="form.taux_sel"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="3.50"
                />
              </div>
            </div>
          </div>

          <!-- Section 3: Conditionnement / Emballage -->
          <div class="mb-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center text-sm">3</span>
              Conditionnement / Emballage
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type emballage</label>
                <select
                  v-model="form.type_emballage"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Bocal verre">Bocal verre</option>
                  <option value="Seaux">Seaux</option>
                  <option value="Pots">Pots</option>
                  <option value="Sachets">Sachets</option>
                  <option value="Cartons">Cartons</option>
                  <option value="Bidons">Bidons</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unités par carton</label>
                <input
                  v-model.number="form.unites_par_carton"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="12"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cartons par palette</label>
                <input
                  v-model.number="form.cartons_par_palette"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="84"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre total par palette</label>
                <input
                  v-model.number="form.nombre_total_par_palette"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="1008"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type palette</label>
                <select
                  v-model="form.type_palette"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="">Sélectionner</option>
                  <option value="Europe (80x120)">Europe (80x120)</option>
                  <option value="US (100x120)">US (100x120)</option>
                  <option value="Demi-palette (60x80)">Demi-palette (60x80)</option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dimensions carton (L x l x H) cm</label>
                <div class="grid grid-cols-3 gap-2">
                  <input
                    v-model.number="form.dimension_carton_l"
                    type="number"
                    step="0.1"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    placeholder="30"
                  />
                  <input
                    v-model.number="form.dimension_carton_w"
                    type="number"
                    step="0.1"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    placeholder="22"
                  />
                  <input
                    v-model.number="form.dimension_carton_h"
                    type="number"
                    step="0.1"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    placeholder="24"
                  />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poids carton (Kg)</label>
                <input
                  v-model.number="form.poids_carton"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="14.50"
                />
              </div>
            </div>
          </div>

          <!-- Section 4: Logistique (simplified) -->
          <div class="mb-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center text-sm">4</span>
              Logistique
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poids palette (brut) Kg</label>
                <input
                  v-model.number="form.poids_palette"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="1246.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">DLC/DLUO (Mois)</label>
                <input
                  v-model.number="form.dlc_dluo"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="24"
                />
              </div>
            </div>
          </div>

          <!-- Section 5: Informations commerciales -->
          <div class="mb-8 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h4 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-teal-600 text-white rounded-full flex items-center justify-center text-sm">5</span>
              Informations commerciales
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix de vente</label>
                <input
                  v-model.number="form.prix_vente"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="25.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unité de facturation</label>
                <select
                  v-model="form.unite_facturation"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="Carton">Carton</option>
                  <option value="Unité">Unité</option>
                  <option value="Palette">Palette</option>
                  <option value="Kg">Kg</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Devise</label>
                <select
                  v-model="form.devise"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="EUR">EUR - Euro</option>
                  <option value="USD">USD - Dollar</option>
                  <option value="MAD">MAD - Dirham</option>
                  <option value="GBP">GBP - Livre</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Taux TVA (%)</label>
                <select
                  v-model="form.taux_tva"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="0">0%</option>
                  <option value="7">7%</option>
                  <option value="10">10%</option>
                  <option value="14">14%</option>
                  <option value="20">20%</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marché</label>
                <select
                  v-model="form.marche"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white"
                >
                  <option value="Local">Local</option>
                  <option value="Export">Export</option>
                  <option value="Les deux">Les deux</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Minimum de commande</label>
                <div class="flex">
                  <input
                    v-model.number="form.minimum_commande"
                    type="number"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                    placeholder="10"
                  />
                  <span class="inline-flex items-center px-3 text-sm text-gray-500 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg">
                    cartons
                  </span>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code interne (optionnel)</label>
                <input
                  v-model="form.code_interne"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="INT-OLV-00125"
                />
              </div>
              <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observations</label>
                <textarea
                  v-model="form.observations"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                  placeholder="Produit de qualité supérieure..."
                ></textarea>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Confirmer la suppression</h3>
          <p class="text-gray-500 mb-6">Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.</p>
          <div class="flex gap-4 justify-center">
            <button
              @click="showDeleteModal = false"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
            >
              Annuler
            </button>
            <button
              @click="confirmDelete"
              :disabled="deleting"
              class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50"
            >
              {{ deleting ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Articles',
  data() {
    return {
      articles: [],
      stats: {
        total_articles: 0,
        total_poids_brut: 0,
        total_poids_net: 0
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      loading: true,
      showModal: false,
      showDeleteModal: false,
      isEditing: false,
      submitting: false,
      deleting: false,
      searchQuery: '',
      searchTimeout: null,
      articleToDelete: null,
      form: this.getEmptyForm()
    };
  },
  mounted() {
    this.fetchArticles();
    this.fetchStats();
  },
  methods: {
    getEmptyForm() {
      return {
        id: null,
        code_article: '',
        designation: '',
        calibre: '',
        famille: '',
        sous_famille: '',
        marque: '',
        code_barres: '',
        hs_code: '',
        actif: true,
        poids_brut: null,
        poids_net: null,
        poids_net_egoutte: null,
        ph: null,
        taux_sel: null,
        type_emballage: '',
        unites_par_carton: null,
        cartons_par_palette: null,
        nombre_total_par_palette: null,
        type_palette: '',
        dimension_carton_l: null,
        dimension_carton_w: null,
        dimension_carton_h: null,
        poids_carton: null,
        poids_palette: null,
        dlc_dluo: null,
        prix_vente: null,
        unite_facturation: 'Carton',
        devise: 'EUR',
        taux_tva: 20,
        marche: 'Export',
        minimum_commande: null,
        code_interne: '',
        observations: ''
      };
    },
    async fetchArticles(page = 1) {
      this.loading = true;
      try {
        const params = new URLSearchParams({ page });
        if (this.searchQuery) {
          params.append('search', this.searchQuery);
        }
        const response = await fetch(`/api/articles?${params}`);
        const data = await response.json();
        this.articles = data.data;
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          from: data.from || 0,
          to: data.to || 0,
          total: data.total
        };
      } catch (error) {
        console.error('Error fetching articles:', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchStats() {
      try {
        const response = await fetch('/api/articles/stats');
        this.stats = await response.json();
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    },
    debouncedSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchArticles(1);
      }, 300);
    },
    openCreateModal() {
      this.isEditing = false;
      this.form = this.getEmptyForm();
      this.showModal = true;
    },
    openEditModal(article) {
      this.isEditing = true;
      this.form = { ...article };
      if (this.form.date_production) {
        this.form.date_production = this.form.date_production.split('T')[0];
      }
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.form = this.getEmptyForm();
    },
    async submitForm() {
      this.submitting = true;
      try {
        const url = this.isEditing ? `/api/articles/${this.form.id}` : '/api/articles';
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
          this.fetchArticles(this.pagination.current_page);
          this.fetchStats();
        } else {
          const error = await response.json();
          alert(error.message || 'Une erreur est survenue');
        }
      } catch (error) {
        console.error('Error submitting form:', error);
        alert('Une erreur est survenue');
      } finally {
        this.submitting = false;
      }
    },
    deleteArticle(id) {
      this.articleToDelete = id;
      this.showDeleteModal = true;
    },
    async confirmDelete() {
      this.deleting = true;
      try {
        const response = await fetch(`/api/articles/${this.articleToDelete}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          this.showDeleteModal = false;
          this.articleToDelete = null;
          this.fetchArticles(this.pagination.current_page);
          this.fetchStats();
        } else {
          alert('Une erreur est survenue lors de la suppression');
        }
      } catch (error) {
        console.error('Error deleting article:', error);
        alert('Une erreur est survenue');
      } finally {
        this.deleting = false;
      }
    },
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('fr-FR');
    },
    formatNumber(value) {
      if (value === null || value === undefined) return '-';
      return parseFloat(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }
};
</script>
