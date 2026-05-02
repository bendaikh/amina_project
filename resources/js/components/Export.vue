<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Export</h1>
        <p class="text-gray-600">Gérez vos opérations d'exportation internationale</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Exportations en Cours</h3>
            <span class="text-2xl font-bold text-indigo-600">45</span>
          </div>
          <div class="h-2 bg-gray-200 rounded-full">
            <div class="h-2 bg-indigo-600 rounded-full" style="width: 65%"></div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Pays Destinations</h3>
            <span class="text-2xl font-bold text-green-600">23</span>
          </div>
          <div class="h-2 bg-gray-200 rounded-full">
            <div class="h-2 bg-green-600 rounded-full" style="width: 85%"></div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Valeur Totale</h3>
            <span class="text-2xl font-bold text-blue-600">€1.2M</span>
          </div>
          <div class="h-2 bg-gray-200 rounded-full">
            <div class="h-2 bg-blue-600 rounded-full" style="width: 75%"></div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-6">Opérations d'Export</h3>
        <div class="text-center py-12 text-gray-500">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-lg mb-2">Gestion Export</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Export'
};
</script>
