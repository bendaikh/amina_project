<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-full mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Articles</h1>
        <p class="text-gray-600">Gérez votre catalogue de produits et articles</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm mb-1">Total Articles</p>
              <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_articles }}</h3>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                @input="debouncedSearch"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <button 
              @click="openCreateModal"
              class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium"
            >
              + Nouvel Article
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Chargement...</p>
        </div>

        <!-- Table -->
        <div v-else-if="articles.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HS Code</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colis</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOT</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calibre</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Prod.</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marque</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emballage</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poids Brut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poids Net</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poids Égoutté</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="article in articles" :key="article.id" class="hover:bg-gray-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ article.designation }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.hs_code || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="block">P: {{ article.total_colis_palet || 0 }}</span>
                  <span class="block">C: {{ article.total_colis_carton || 0 }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.lot || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.calibre || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(article.date_production) }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ article.marque || '-' }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="block">{{ article.emballage_type || '-' }}</span>
                  <span class="block text-xs text-gray-400">{{ article.emballage_ref || '' }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="block">U: {{ formatNumber(article.poids_brut_unitaire) }}</span>
                  <span class="block font-medium">T: {{ formatNumber(article.poids_brut_total) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="block">U: {{ formatNumber(article.poids_net_unitaire) }}</span>
                  <span class="block font-medium">T: {{ formatNumber(article.poids_net_total) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="block">U: {{ formatNumber(article.poids_net_egoutte_unitaire) }}</span>
                  <span class="block font-medium">T: {{ formatNumber(article.poids_net_egoutte_total) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button @click="openEditModal(article)" class="text-indigo-600 hover:text-indigo-900 mr-3">
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
      <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ isEditing ? 'Modifier l\'article' : 'Nouvel Article' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitForm" class="p-6">
          <!-- Désignation & HS Code -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Informations Générales</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Désignation *</label>
                <input
                  v-model="form.designation"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: OLIVES VERTES EN RONDELLE"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">HS Code</label>
                <input
                  v-model="form.hs_code"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: 2005700081"
                />
              </div>
            </div>
          </div>

          <!-- Colis & LOT -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Colis & Lot</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Colis (Palet)</label>
                <input
                  v-model.number="form.total_colis_palet"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Colis (Carton)</label>
                <input
                  v-model.number="form.total_colis_carton"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">LOT</label>
                <input
                  v-model="form.lot"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: 158F26.26"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Calibre</label>
                <input
                  v-model="form.calibre"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: 30/33"
                />
              </div>
            </div>
          </div>

          <!-- Production & Marque -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Production & Marque</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Production</label>
                <input
                  v-model="form.date_production"
                  type="date"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marque</label>
                <input
                  v-model="form.marque"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: MARJANA"
                />
              </div>
            </div>
          </div>

          <!-- Emballage -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Emballage</h4>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  v-model="form.emballage_type"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">Sélectionner</option>
                  <option value="Seaux">Seaux</option>
                  <option value="Pots">Pots</option>
                  <option value="sachets">Sachets</option>
                  <option value="Cartons">Cartons</option>
                  <option value="Bidons">Bidons</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
                <input
                  v-model="form.emballage_ref"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Ex: 10L, 870ml"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tare</label>
                <input
                  v-model.number="form.emballage_tare"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">T/Colis</label>
                <input
                  v-model.number="form.t_colis"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">T/Emb</label>
                <input
                  v-model.number="form.t_emb"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
            </div>
          </div>

          <!-- Poids Brut -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Poids Brut (KG)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unitaire</label>
                <input
                  v-model.number="form.poids_brut_unitaire"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                <input
                  v-model.number="form.poids_brut_total"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
            </div>
          </div>

          <!-- Poids Net -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Poids Net (KG)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unitaire</label>
                <input
                  v-model.number="form.poids_net_unitaire"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                <input
                  v-model.number="form.poids_net_total"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
            </div>
          </div>

          <!-- Poids Net Égoutté -->
          <div class="mb-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Poids Net Égoutté (KG)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unitaire</label>
                <input
                  v-model.number="form.poids_net_egoutte_unitaire"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                <input
                  v-model.number="form.poids_net_egoutte_total"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="0.00"
                />
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-4 pt-4 border-t">
            <button
              type="button"
              @click="closeModal"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ submitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer l\'article') }}
            </button>
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
        designation: '',
        hs_code: '',
        total_colis_palet: 0,
        total_colis_carton: 0,
        lot: '',
        calibre: '',
        date_production: '',
        marque: '',
        emballage_type: '',
        emballage_ref: '',
        emballage_tare: null,
        t_colis: null,
        t_emb: null,
        poids_brut_unitaire: null,
        poids_brut_total: null,
        poids_net_unitaire: null,
        poids_net_total: null,
        poids_net_egoutte_unitaire: null,
        poids_net_egoutte_total: null
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
