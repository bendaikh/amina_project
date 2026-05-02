<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Documents de Vente</h1>
        <p class="text-gray-600">Gérez vos devis, bons de commande et documents commerciaux</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Devis</p>
          <h3 class="text-2xl font-bold text-gray-900">234</h3>
          <p class="text-yellow-600 text-xs mt-1">45 en attente</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Commandes</p>
          <h3 class="text-2xl font-bold text-gray-900">567</h3>
          <p class="text-blue-600 text-xs mt-1">23 en cours</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Bons de Livraison</p>
          <h3 class="text-2xl font-bold text-gray-900">432</h3>
          <p class="text-green-600 text-xs mt-1">12 aujourd'hui</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Factures</p>
          <h3 class="text-2xl font-bold text-gray-900">389</h3>
          <p class="text-purple-600 text-xs mt-1">8 impayées</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-6">Documents Récents</h3>
        <div class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-lg mb-2">Documents de Vente</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SalesDocuments'
};
</script>
