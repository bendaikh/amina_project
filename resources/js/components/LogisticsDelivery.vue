<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Logistique & Livraison</h1>
        <p class="text-gray-600">Suivez vos livraisons et opérations logistiques</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-5 border border-l-4 border-l-blue-500">
          <p class="text-gray-500 text-sm mb-1">En Préparation</p>
          <h3 class="text-3xl font-bold text-gray-900">23</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-l-4 border-l-yellow-500">
          <p class="text-gray-500 text-sm mb-1">En Transit</p>
          <h3 class="text-3xl font-bold text-gray-900">67</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-l-4 border-l-green-500">
          <p class="text-gray-500 text-sm mb-1">Livrées</p>
          <h3 class="text-3xl font-bold text-gray-900">145</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-l-4 border-l-red-500">
          <p class="text-gray-500 text-sm mb-1">Problèmes</p>
          <h3 class="text-3xl font-bold text-gray-900">3</h3>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-6">Livraisons du Jour</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-blue-50 border border-blue-100 rounded-lg">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
              </div>
              <div>
                <h4 class="font-semibold text-gray-900">Livraison #LIV-2024-001</h4>
                <p class="text-sm text-gray-600">Client: ABC Corporation</p>
              </div>
            </div>
            <span class="px-4 py-2 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">En transit</span>
          </div>
        </div>

        <div class="text-center py-12 text-gray-500 mt-8">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
          </svg>
          <p class="text-lg mb-2">Suivi Logistique</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LogisticsDelivery'
};
</script>
