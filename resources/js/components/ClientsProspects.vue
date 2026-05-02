<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Clients & Prospects</h1>
        <p class="text-gray-600">Gérez votre portefeuille clients et prospects</p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <div class="flex-1">
            <div class="relative">
              <input type="text" placeholder="Rechercher un client ou prospect..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
          <button class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            + Ajouter Client
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                      <span class="text-indigo-600 font-semibold">JD</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">Jean Dupont</p>
                      <p class="text-sm text-gray-500">ABC Corp</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">jean.dupont@example.com</td>
                <td class="py-4 px-4 text-gray-600">+33 6 12 34 56 78</td>
                <td class="py-4 px-4">
                  <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Client</span>
                </td>
                <td class="py-4 px-4">
                  <button class="text-indigo-600 hover:text-indigo-800">Voir</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p>Contenu en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClientsProspects'
};
</script>
