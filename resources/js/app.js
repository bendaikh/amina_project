import './bootstrap';
import '../css/layout.css';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import ClientsProspects from './components/ClientsProspects.vue';
import Articles from './components/Articles.vue';
import Exportations from './components/Exportations.vue';
import ExportationDetail from './components/ExportationDetail.vue';
import Emballages from './components/Emballages.vue';
import Suppliers from './components/Suppliers.vue';
import Settings from './components/Settings.vue';
import Prospection from './components/Prospection.vue';
import Purchases from './components/Purchases.vue';
import AchatDetail from './components/AchatDetail.vue';
import FacturesFournisseurs from './components/FacturesFournisseurs.vue';
import SuiviReglementaire from './components/SuiviReglementaire.vue';
import Claims from './components/Claims.vue';
import NotesCredit from './components/NotesCredit.vue';
import SuiviVentes from './components/SuiviVentes.vue';
import Commandes from './components/Commandes.vue';
import CommandeDetail from './components/CommandeDetail.vue';
import VentesProduction from './components/VentesProduction.vue';
import VentesLogistique from './components/VentesLogistique.vue';
import BonsLivraison from './components/BonsLivraison.vue';
import FacturationLocale from './components/FacturationLocale.vue';
import Placeholder from './components/Placeholder.vue';
import StockReceptions from './components/StockReceptions.vue';
import StockMouvements from './components/StockMouvements.vue';
import StockSituation from './components/StockSituation.vue';
import StockInventaire from './components/StockInventaire.vue';
import Exportateurs from './components/Exportateurs.vue';
import FacturationExport from './components/FacturationExport.vue';
import FactureCommercialeDetail from './components/FactureCommercialeDetail.vue';
import Reglements from './components/Reglements.vue';
import Echeances from './components/Echeances.vue';
import Lettrage from './components/Lettrage.vue';
import Banque from './components/Banque.vue';
import RapprochementBancaire from './components/RapprochementBancaire.vue';
import RapportFactures from './components/RapportFactures.vue';
import RapportStock from './components/RapportStock.vue';
import RapportFinance from './components/RapportFinance.vue';
import RapportExport from './components/RapportExport.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/login' },
        { path: '/login', name: 'Login', component: Login, meta: { requiresGuest: true } },
        { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true, title: 'Tableau de bord' } },

        // 2. Gestion des parties
        { path: '/clients', name: 'Clients', component: ClientsProspects, meta: { requiresAuth: true, title: 'Clients' } },
        { path: '/clients-prospects', redirect: '/clients' },
        { path: '/fournisseurs', name: 'Fournisseurs', component: Suppliers, meta: { requiresAuth: true, title: 'Fournisseurs' } },
        { path: '/suppliers', redirect: '/fournisseurs' },
        { path: '/prospects', name: 'Prospects', component: Prospection, meta: { requiresAuth: true, title: 'Prospects' } },

        // 3. Articles
        { path: '/articles', name: 'Articles', component: Articles, meta: { requiresAuth: true, title: 'Articles' } },
        { path: '/articles/familles', name: 'Familles', component: Placeholder, meta: { requiresAuth: true, title: 'Familles' } },
        { path: '/articles/parametres', name: 'ParametresArticles', component: Settings, meta: { requiresAuth: true, title: 'Paramètres articles' } },
        { path: '/settings', redirect: '/articles/parametres' },

        // 4. Achats
        { path: '/achats', name: 'Achats', component: Purchases, meta: { requiresAuth: true, title: 'Achats' } },
        { path: '/achats/factures', name: 'FacturesFournisseurs', component: FacturesFournisseurs, meta: { requiresAuth: true, title: 'Factures fournisseurs' } },
        { path: '/achats/suivi-reglementaire', name: 'SuiviReglementaire', component: SuiviReglementaire, meta: { requiresAuth: true, title: 'Suivi réglementaire' } },
        { path: '/achats/:id', name: 'AchatDetail', component: AchatDetail, meta: { requiresAuth: true, title: 'Fiche achat' } },

        // 5. Stock
        { path: '/stock/receptions', name: 'BonsReception', component: StockReceptions, meta: { requiresAuth: true, title: 'Bons de réception' } },
        { path: '/stock/mouvements', name: 'MouvementsStock', component: StockMouvements, meta: { requiresAuth: true, title: 'Mouvements' } },
        { path: '/stock/situation', name: 'SituationStock', component: StockSituation, meta: { requiresAuth: true, title: 'Situation stock' } },
        { path: '/stock/inventaire', name: 'Inventaire', component: StockInventaire, meta: { requiresAuth: true, title: 'Inventaire' } },

        // 6. Ventes
        { path: '/ventes/commandes', name: 'Commandes', component: Commandes, meta: { requiresAuth: true, title: 'Commandes' } },
        { path: '/ventes/commandes/production', name: 'CommandesProduction', component: VentesProduction, meta: { requiresAuth: true, title: 'Production' } },
        { path: '/ventes/commandes/logistique', name: 'CommandesLogistique', component: VentesLogistique, meta: { requiresAuth: true, title: 'Logistique' } },
        { path: '/ventes/commandes/:id', name: 'CommandeDetail', component: CommandeDetail, meta: { requiresAuth: true, title: 'Fiche commande' } },
        { path: '/ventes/locales/bons-livraison', name: 'BonsLivraison', component: BonsLivraison, meta: { requiresAuth: true, title: 'Bons de livraison' } },
        { path: '/ventes/locales/facturation', name: 'FacturationLocale', component: FacturationLocale, meta: { requiresAuth: true, title: 'Facturation locale' } },
        { path: '/ventes/locales/bons-retour', name: 'BonsRetour', component: Placeholder, meta: { requiresAuth: true, title: 'Bons de retour' } },
        { path: '/ventes/export/colisage', name: 'ListeColisage', component: Exportations, meta: { requiresAuth: true, title: 'Liste de colisage' } },
        { path: '/ventes/export/facturation', name: 'FacturationExport', component: FacturationExport, meta: { requiresAuth: true, title: 'Facturation export' } },
        { path: '/ventes/export/facturation/:id', name: 'FactureCommercialeDetail', component: FactureCommercialeDetail, meta: { requiresAuth: true, title: 'Facture commerciale' } },
        { path: '/ventes/export/documents', name: 'DocumentsExport', component: Placeholder, meta: { requiresAuth: true, title: 'Documents export' } },
        { path: '/ventes/export/emballages', name: 'Emballages', component: Emballages, meta: { requiresAuth: true, title: 'Emballages temporaires / DUM 52' } },
        { path: '/ventes/reclamations', name: 'Reclamations', component: Claims, meta: { requiresAuth: true, title: 'Réclamations' } },
        { path: '/ventes/notes-credit', name: 'NotesCredit', component: NotesCredit, meta: { requiresAuth: true, title: 'Notes de crédit' } },
        { path: '/ventes/suivi', name: 'SuiviVentes', component: SuiviVentes, meta: { requiresAuth: true, title: 'Suivi des ventes' } },

        // Legacy export routes
        { path: '/exportations', redirect: '/ventes/export/colisage' },
        { path: '/exportations/:id', name: 'ExportationDetail', component: ExportationDetail, meta: { requiresAuth: true, title: 'Fiche exportation' } },
        { path: '/emballages', redirect: '/ventes/export/emballages' },
        { path: '/export', redirect: '/ventes/export/colisage' },

        // 7. Finance
        { path: '/finance/reglements-clients', name: 'ReglementsClients', component: Reglements, props: { sens: 'client' }, meta: { requiresAuth: true, title: 'Règlements clients' } },
        { path: '/finance/reglements-fournisseurs', name: 'ReglementsFournisseurs', component: Reglements, props: { sens: 'fournisseur' }, meta: { requiresAuth: true, title: 'Règlements fournisseurs' } },
        { path: '/finance/echeances', name: 'Echeances', component: Echeances, meta: { requiresAuth: true, title: 'Échéances' } },
        { path: '/finance/lettrage', name: 'Lettrage', component: Lettrage, meta: { requiresAuth: true, title: 'Lettrage' } },
        { path: '/finance/banque', name: 'Banque', component: Banque, meta: { requiresAuth: true, title: 'Banque' } },
        { path: '/finance/rapprochement', name: 'Rapprochement', component: RapprochementBancaire, meta: { requiresAuth: true, title: 'Rapprochement bancaire' } },

        // 8. Rapports
        { path: '/rapports/ventes', name: 'RapportsVentes', component: RapportFactures, props: { kind: 'ventes' }, meta: { requiresAuth: true, title: 'Rapports ventes' } },
        { path: '/rapports/achats', name: 'RapportsAchats', component: RapportFactures, props: { kind: 'achats' }, meta: { requiresAuth: true, title: 'Rapports achats' } },
        { path: '/rapports/stock', name: 'RapportsStock', component: RapportStock, meta: { requiresAuth: true, title: 'Rapports stock' } },
        { path: '/rapports/finance', name: 'RapportsFinance', component: RapportFinance, meta: { requiresAuth: true, title: 'Rapports finance' } },
        { path: '/rapports/export', name: 'RapportsExport', component: RapportExport, meta: { requiresAuth: true, title: 'Export' } },

        // 9. Paramètre
        { path: '/parametre/exportateurs', name: 'Exportateurs', component: Exportateurs, meta: { requiresAuth: true, title: 'Exportateurs' } },
    ]
});

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
