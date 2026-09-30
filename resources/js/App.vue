<template>
  <div class="app-shell">
    <!-- Login without chrome -->
    <div v-if="$route.path === '/login'">
      <router-view></router-view>
    </div>

    <div v-else>
      <!-- Mobile top bar -->
      <header class="app-mobile-bar">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <h1 class="text-base font-bold text-gray-800 truncate">{{ currentPageTitle }}</h1>
        </div>
        <button
          type="button"
          @click="sidebarOpen = !sidebarOpen"
          class="p-2 rounded-md text-gray-600 hover:bg-gray-100"
          :aria-expanded="sidebarOpen"
          aria-label="Menu"
        >
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path v-if="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </header>

      <div v-if="sidebarOpen" class="app-overlay" @click="sidebarOpen = false"></div>

      <aside class="app-sidebar" :class="{ 'is-open': sidebarOpen, 'is-collapsed': sidebarCollapsed }">
        <div class="sb-brand">
          <div class="sb-brand-icon">
            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <div class="min-w-0 sb-brand-text">
            <h2 class="text-xl font-bold leading-tight">Batixper</h2>
            <p class="text-xs text-teal-100/90 truncate">Gestion Exportations</p>
          </div>
          <button
            type="button"
            class="sb-collapse-btn"
            @click="sidebarCollapsed = !sidebarCollapsed"
            :title="sidebarCollapsed ? 'Étendre le menu' : 'Réduire le menu'"
            :aria-label="sidebarCollapsed ? 'Étendre le menu' : 'Réduire le menu'"
          >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
          </button>
          <button
            type="button"
            class="sb-close-mobile"
            @click="sidebarOpen = false"
            aria-label="Fermer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <nav class="sb-nav">
          <p class="sb-section-label">Structure</p>
          <ul class="space-y-0.5">
            <li v-for="section in menuSections" :key="section.id">
              <router-link
                v-if="!section.children"
                :to="section.path"
                class="sb-item"
                :class="{ 'is-active': isPathActive(section.path) }"
                :title="section.label"
                @click="closeMobile"
              >
                <span class="sb-emoji">{{ section.emoji }}</span>
                <span class="truncate sb-item-label">{{ section.label }}</span>
              </router-link>

              <template v-else>
                <button
                  type="button"
                  class="sb-item"
                  :class="{ 'is-open': isExpanded(section.id) || isSectionActive(section) }"
                  :title="section.label"
                  @click="onSectionClick(section)"
                >
                  <span class="sb-emoji">{{ section.emoji }}</span>
                  <span class="truncate sb-item-label">{{ section.label }}</span>
                  <svg class="sb-chevron" :class="{ 'is-open': isExpanded(section.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>

                <ul v-show="isExpanded(section.id)" class="sb-children">
                  <li v-for="item in section.children" :key="item.id || item.path || item.label">
                    <template v-if="item.children">
                      <button
                        type="button"
                        class="sb-group-btn"
                        :class="{ 'is-open': isExpanded(item.id) }"
                        @click="toggleSection(item.id)"
                      >
                        <span class="truncate">{{ item.label }}</span>
                        <svg class="sb-chevron" :class="{ 'is-open': isExpanded(item.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                      </button>
                      <ul v-show="isExpanded(item.id)" class="sb-nested">
                        <li v-for="child in item.children" :key="child.path">
                          <router-link
                            :to="child.path"
                            class="sb-sub"
                            :class="{ 'is-active': isPathActive(child.path) }"
                            @click="closeMobile"
                          >{{ child.label }}</router-link>
                        </li>
                      </ul>
                    </template>
                    <router-link
                      v-else
                      :to="item.path"
                      class="sb-sub"
                      :class="{ 'is-active': isPathActive(item.path) }"
                      @click="closeMobile"
                    >{{ item.label }}</router-link>
                  </li>
                </ul>
              </template>
            </li>
          </ul>
        </nav>

        <div class="sb-footer">
          <div class="sb-footer-row">
            <div class="sb-avatar" :title="userEmail">{{ userInitials }}</div>
            <div class="flex-1 min-w-0 sb-footer-meta">
              <p class="text-sm font-medium truncate">Super Admin</p>
              <p class="text-xs text-teal-100/80 truncate">{{ userEmail }}</p>
            </div>
            <button type="button" class="sb-logout" title="Déconnexion" @click="handleLogout">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </aside>

      <main class="app-main" :class="{ 'is-collapsed': sidebarCollapsed }">
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
      sidebarCollapsed: true,
      expanded: {},
      menuSections: [
        {
          id: 'dashboard',
          emoji: '🏠',
          label: '1. Tableau de bord',
          path: '/dashboard'
        },
        {
          id: 'parties',
          emoji: '👥',
          label: '2. Gestion des parties',
          children: [
            { label: 'Clients', path: '/clients' },
            { label: 'Fournisseurs', path: '/fournisseurs' },
            { label: 'Prospects', path: '/prospects' }
          ]
        },
        {
          id: 'articles',
          emoji: '🫒',
          label: '3. Articles',
          children: [
            { label: 'Articles', path: '/articles' },
            { label: 'Familles', path: '/articles/familles' },
            { label: 'Paramètres articles', path: '/articles/parametres' }
          ]
        },
        {
          id: 'achats',
          emoji: '🛒',
          label: '4. Achats',
          children: [
            { label: 'Achats', path: '/achats' },
            { label: 'Factures fournisseurs', path: '/achats/factures' },
            { label: 'Suivi réglementaire', path: '/achats/suivi-reglementaire' }
          ]
        },
        {
          id: 'stock',
          emoji: '📦',
          label: '5. Stock',
          children: [
            { label: 'Bons de réception', path: '/stock/receptions' },
            { label: 'Mouvements', path: '/stock/mouvements' },
            { label: 'Situation stock', path: '/stock/situation' },
            { label: 'Inventaire', path: '/stock/inventaire' }
          ]
        },
        {
          id: 'ventes',
          emoji: '💰',
          label: '6. Ventes',
          children: [
            {
              id: 'commandes',
              label: 'Commandes',
              children: [
                { label: 'Liste des commandes', path: '/ventes/commandes' },
                { label: 'Logistique', path: '/ventes/commandes/logistique' }
              ]
            },
            {
              id: 'ventes-locales',
              label: 'Ventes locales',
              children: [
                { label: 'Bons de livraison', path: '/ventes/locales/bons-livraison' },
                { label: 'Facturation', path: '/ventes/locales/facturation' },
                { label: 'Bons de retour', path: '/ventes/locales/bons-retour' }
              ]
            },
            {
              id: 'ventes-export',
              label: 'Ventes export',
              children: [
                { label: 'Liste de colisage', path: '/ventes/export/colisage' },
                { label: 'Facturation', path: '/ventes/export/facturation' },
                { label: 'Documents export', path: '/ventes/export/documents' },
                { label: 'Emballages temporaires / DUM 52', path: '/ventes/export/emballages' }
              ]
            },
            { label: 'Réclamations', path: '/ventes/reclamations' },
            { label: 'Notes de crédit', path: '/ventes/notes-credit' },
            { label: 'Suivi des ventes', path: '/ventes/suivi' }
          ]
        },
        {
          id: 'finance',
          emoji: '💳',
          label: '7. Finance',
          children: [
            { label: 'Règlements clients', path: '/finance/reglements-clients' },
            { label: 'Règlements fournisseurs', path: '/finance/reglements-fournisseurs' },
            { label: 'Échéances', path: '/finance/echeances' },
            { label: 'Lettrage', path: '/finance/lettrage' },
            { label: 'Banque', path: '/finance/banque' },
            { label: 'Rapprochement bancaire', path: '/finance/rapprochement' }
          ]
        },
        {
          id: 'rapports',
          emoji: '📊',
          label: '8. Rapports',
          children: [
            { label: 'Ventes', path: '/rapports/ventes' },
            { label: 'Achats', path: '/rapports/achats' },
            { label: 'Stock', path: '/rapports/stock' },
            { label: 'Finance', path: '/rapports/finance' },
            { label: 'Export', path: '/rapports/export' }
          ]
        },
        {
          id: 'parametre',
          emoji: '⚙️',
          label: '9. Paramètre',
          children: [
            { label: 'Exportateurs', path: '/parametre/exportateurs' }
          ]
        }
      ]
    };
  },
  computed: {
    flatLabels() {
      const map = {};
      const walk = (nodes) => {
        nodes.forEach((n) => {
          if (n.path) map[n.path] = n.label.replace(/^\d+\.\s*/, '');
          if (n.children) walk(n.children);
        });
      };
      walk(this.menuSections);
      return map;
    },
    currentPageTitle() {
      if (this.$route.path.startsWith('/exportations/')) return 'Fiche exportation';
      if (this.$route.path.match(/^\/achats\/\d+/)) return 'Fiche achat';
      if (this.$route.meta?.title) return this.$route.meta.title;
      return this.flatLabels[this.$route.path] || 'Batixper';
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
  watch: {
    '$route.path': {
      immediate: true,
      handler(path) {
        this.expandForPath(path);
        this.sidebarOpen = false;
      }
    }
  },
  methods: {
    closeMobile() {
      this.sidebarOpen = false;
    },
    onSectionClick(section) {
      if (this.sidebarCollapsed && window.matchMedia('(min-width: 1024px)').matches) {
        this.sidebarCollapsed = false;
        this.expanded = { ...this.expanded, [section.id]: true };
        return;
      }
      this.toggleSection(section.id);
    },
    isPathActive(path) {
      if (!path) return false;
      if (this.$route.path === path) return true;
      if (path === '/ventes/export/colisage' && this.$route.path.startsWith('/exportations')) return true;
      if (path === '/achats' && this.$route.path.match(/^\/achats\/\d+/)) return true;
      if (path === '/ventes/commandes' && this.$route.path.match(/^\/ventes\/commandes\/\d+/)) return true;
      return false;
    },
    isSectionActive(section) {
      if (section.path) return this.isPathActive(section.path);
      if (!section.children) return false;
      return section.children.some((c) => {
        if (c.path) return this.isPathActive(c.path) || this.$route.path.startsWith(c.path + '/');
        return this.isSectionActive(c);
      });
    },
    isExpanded(id) {
      return !!this.expanded[id];
    },
    toggleSection(id) {
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] };
    },
    expandForPath(path) {
      const next = { ...this.expanded };
      const walk = (nodes, parents = []) => {
        nodes.forEach((n) => {
          if (n.path === path || (n.path && path.startsWith(n.path + '/'))) {
            parents.forEach((pid) => { next[pid] = true; });
          }
          if (n.children) {
            walk(n.children, n.id ? [...parents, n.id] : parents);
          }
        });
      };
      if (path.startsWith('/exportations')) {
        next.ventes = true;
        next['ventes-export'] = true;
      }
      if (path.startsWith('/achats')) {
        next.achats = true;
      }
      walk(this.menuSections);
      this.expanded = next;
    },
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
