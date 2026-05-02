import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import Prospection from './components/Prospection.vue';
import ClientsProspects from './components/ClientsProspects.vue';
import Articles from './components/Articles.vue';
import SalesDocuments from './components/SalesDocuments.vue';
import BillingFinancial from './components/BillingFinancial.vue';
import Export from './components/Export.vue';
import LogisticsDelivery from './components/LogisticsDelivery.vue';
import Suppliers from './components/Suppliers.vue';
import Claims from './components/Claims.vue';
import Users from './components/Users.vue';
import Purchases from './components/Purchases.vue';
import Settings from './components/Settings.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/login' },
        { path: '/login', name: 'Login', component: Login, meta: { requiresGuest: true } },
        { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true } },
        { path: '/prospection', name: 'Prospection', component: Prospection, meta: { requiresAuth: true } },
        { path: '/clients-prospects', name: 'ClientsProspects', component: ClientsProspects, meta: { requiresAuth: true } },
        { path: '/articles', name: 'Articles', component: Articles, meta: { requiresAuth: true } },
        { path: '/sales-documents', name: 'SalesDocuments', component: SalesDocuments, meta: { requiresAuth: true } },
        { path: '/billing-financial', name: 'BillingFinancial', component: BillingFinancial, meta: { requiresAuth: true } },
        { path: '/export', name: 'Export', component: Export, meta: { requiresAuth: true } },
        { path: '/logistics-delivery', name: 'LogisticsDelivery', component: LogisticsDelivery, meta: { requiresAuth: true } },
        { path: '/suppliers', name: 'Suppliers', component: Suppliers, meta: { requiresAuth: true } },
        { path: '/claims', name: 'Claims', component: Claims, meta: { requiresAuth: true } },
        { path: '/users', name: 'Users', component: Users, meta: { requiresAuth: true } },
        { path: '/purchases', name: 'Purchases', component: Purchases, meta: { requiresAuth: true } },
        { path: '/settings', name: 'Settings', component: Settings, meta: { requiresAuth: true } },
    ]
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
    
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next('/dashboard');
    } else {
        next();
    }
});

const app = createApp(App);
app.use(router);
app.mount('#app');
