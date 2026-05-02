<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Fournisseurs</h1>
        <p class="text-gray-600">Gérez vos relations avec les fournisseurs</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <h3 class="text-gray-500 text-sm mb-2">Total Fournisseurs</h3>
          <p class="text-4xl font-bold text-gray-900">156</p>
          <p class="text-green-600 text-sm mt-2">+12 ce mois</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <h3 class="text-gray-500 text-sm mb-2">Fournisseurs Actifs</h3>
          <p class="text-4xl font-bold text-gray-900">142</p>
          <p class="text-blue-600 text-sm mt-2">91% du total</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <h3 class="text-gray-500 text-sm mb-2">Commandes Fournisseurs</h3>
          <p class="text-4xl font-bold text-gray-900">89</p>
          <p class="text-orange-600 text-sm mt-2">23 en attente</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Liste des Fournisseurs</h3>
          <button class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            + Nouveau Fournisseur
          </button>
        </div>

        <div class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <p class="text-lg mb-2">Gestion Fournisseurs</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Suppliers'
};
</script>
