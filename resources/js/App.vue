<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Show login page without sidebar -->
    <div v-if="$route.path === '/login'">
      <router-view></router-view>
    </div>

    <!-- Show dashboard layout with sidebar for authenticated routes -->
    <div v-else>
      <!-- Mobile menu button -->
      <div class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-50 px-4 py-3 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800">{{ currentPageTitle }}</h1>
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path v-if="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Overlay for mobile -->
      <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"></div>

      <!-- Sidebar -->
      <aside 
        :class="[
          'fixed top-0 left-0 h-full bg-gradient-to-b from-indigo-600 to-indigo-800 text-white transition-transform duration-300 ease-in-out z-50',
          'w-64 shadow-xl',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
      >
      <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="p-6 border-b border-indigo-500">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold">Admin Panel</h2>
              <p class="text-xs text-indigo-200">Super Admin</p>
            </div>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 scrollbar-thin scrollbar-thumb-indigo-500 scrollbar-track-indigo-700">
          <ul class="space-y-1">
            <li v-for="item in menuItems" :key="item.path">
              <router-link
                :to="item.path"
                @click="sidebarOpen = false"
                :class="[
                  'flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200',
                  $route.path === item.path 
                    ? 'bg-white text-indigo-600 shadow-md' 
                    : 'text-white hover:bg-indigo-700 hover:shadow-md'
                ]"
              >
                <span v-html="item.icon" class="w-5 h-5"></span>
                <span class="font-medium">{{ item.label }}</span>
              </router-link>
            </li>
          </ul>
        </nav>

        <!-- User Profile -->
        <div class="p-4 border-t border-indigo-500">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-indigo-400 rounded-full flex items-center justify-center">
              <span class="text-sm font-bold">{{ userInitials }}</span>
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium">Super Admin</p>
              <p class="text-xs text-indigo-200">{{ userEmail }}</p>
            </div>
            <button @click="handleLogout" class="p-2 hover:bg-indigo-700 rounded-lg transition-colors" title="Déconnexion">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      </aside>

      <!-- Main Content -->
      <main :class="['lg:ml-64 transition-all duration-300', 'pt-16 lg:pt-0']">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      sidebarOpen: false,
      menuItems: [
        {
          path: '/dashboard',
          label: 'Tableau de Bord',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>'
        },
        {
          path: '/prospection',
          label: 'Volet Prospection',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>'
        },
        {
          path: '/clients-prospects',
          label: 'Gestion Clients & Prospects',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
        },
        {
          path: '/articles',
          label: 'Gestion Articles',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>'
        },
        {
          path: '/sales-documents',
          label: 'Documents de Vente',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>'
        },
        {
          path: '/billing-financial',
          label: 'Facturation & Suivi Financier',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        },
        {
          path: '/export',
          label: 'Gestion Export',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        },
        {
          path: '/logistics-delivery',
          label: 'Logistique & Livraison',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>'
        },
        {
          path: '/suppliers',
          label: 'Gestion Fournisseurs',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
        },
        {
          path: '/claims',
          label: 'Gestion Réclamations',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>'
        },
        {
          path: '/users',
          label: 'Gestion Utilisateurs',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>'
        },
        {
          path: '/purchases',
          label: 'Gestion Achats',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
        },
        {
          path: '/settings',
          label: 'Paramètres',
          icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>'
        }
      ]
    };
  },
  computed: {
    currentPageTitle() {
      const currentItem = this.menuItems.find(item => item.path === this.$route.path);
      return currentItem ? currentItem.label : 'Dashboard';
    },
    userEmail() {
      return localStorage.getItem('userEmail') || 'admin@system.com';
    },
    userInitials() {
      const email = this.userEmail;
      const parts = email.split('@')[0].split('.');
      if (parts.length > 1) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return email.substring(0, 2).toUpperCase();
    }
  },
  methods: {
    handleLogout() {
      if (confirm('Êtes-vous sûr de vouloir vous déconnecter?')) {
        localStorage.removeItem('isAuthenticated');
        localStorage.removeItem('userEmail');
        this.$router.push('/login');
      }
    }
  }
};
</script>

<style>
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thumb-indigo-500::-webkit-scrollbar-thumb {
  background-color: rgba(99, 102, 241, 0.5);
  border-radius: 3px;
}

.scrollbar-track-indigo-700::-webkit-scrollbar-track {
  background-color: rgba(67, 56, 202, 0.3);
}
</style>
