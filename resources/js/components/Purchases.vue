<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Achats</h1>
        <p class="text-gray-600">Gérez vos achats et commandes fournisseurs</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Commandes du Mois</p>
          <h3 class="text-3xl font-bold text-gray-900">89</h3>
          <p class="text-blue-600 text-xs mt-1">+15% vs mois dernier</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Montant Total</p>
          <h3 class="text-3xl font-bold text-gray-900">€156K</h3>
          <p class="text-green-600 text-xs mt-1">Budget: €200K</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">En Attente</p>
          <h3 class="text-3xl font-bold text-orange-600">23</h3>
          <p class="text-gray-500 text-xs mt-1">À valider</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Reçues</p>
          <h3 class="text-3xl font-bold text-green-600">66</h3>
          <p class="text-gray-500 text-xs mt-1">Ce mois</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Commandes d'Achat</h3>
          <button class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            + Nouvelle Commande
          </button>
        </div>

        <div class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-lg mb-2">Gestion des Achats</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Purchases'
};
</script>
