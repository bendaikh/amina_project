<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Volet Prospection</h1>
        <p class="text-gray-600">Gérez vos prospects et opportunités commerciales</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
          <h3 class="text-lg font-semibold mb-2">Prospects Actifs</h3>
          <p class="text-4xl font-bold mb-1">156</p>
          <p class="text-blue-100 text-sm">+23 cette semaine</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
          <h3 class="text-lg font-semibold mb-2">Opportunités</h3>
          <p class="text-4xl font-bold mb-1">89</p>
          <p class="text-green-100 text-sm">€234,500 potentiel</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
          <h3 class="text-lg font-semibold mb-2">Taux de Conversion</h3>
          <p class="text-4xl font-bold mb-1">34%</p>
          <p class="text-purple-100 text-sm">+5% vs. mois dernier</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-6">Pipeline de Prospection</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-blue-600 font-bold">AC</span>
              </div>
              <div>
                <h4 class="font-semibold text-gray-900">ABC Corporation</h4>
                <p class="text-sm text-gray-500">Contact: Jean Dupont</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-semibold text-gray-900">€25,000</p>
              <span class="inline-block px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">En négociation</span>
            </div>
          </div>
          <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p>Contenu en cours de développement</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Prospection'
};
</script>
