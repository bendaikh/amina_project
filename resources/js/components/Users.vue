<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Gestion Utilisateurs</h1>
        <p class="text-gray-600">Gérez les utilisateurs et leurs permissions</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Total Utilisateurs</p>
          <h3 class="text-3xl font-bold text-gray-900">34</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Actifs</p>
          <h3 class="text-3xl font-bold text-green-600">28</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Administrateurs</p>
          <h3 class="text-3xl font-bold text-blue-600">5</h3>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
          <p class="text-gray-500 text-sm mb-1">Inactifs</p>
          <h3 class="text-3xl font-bold text-red-600">6</h3>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Liste des Utilisateurs</h3>
          <button class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            + Nouvel Utilisateur
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Utilisateur</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rôle</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                      <span class="text-indigo-600 font-semibold">SA</span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">Super Admin</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4 text-gray-600">admin@system.com</td>
                <td class="py-4 px-4">
                  <span class="px-3 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Super Admin</span>
                </td>
                <td class="py-4 px-4">
                  <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Actif</span>
                </td>
                <td class="py-4 px-4">
                  <button class="text-indigo-600 hover:text-indigo-800">Modifier</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="text-center py-12 text-gray-500 mt-8">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <p class="text-lg mb-2">Gestion des Utilisateurs</p>
          <p class="text-sm">Fonctionnalité en cours de développement</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Users'
};
</script>
