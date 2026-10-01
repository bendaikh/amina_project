<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8" v-if="commande">
    <div class="max-w-7xl mx-auto no-print">
      <div class="mb-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <router-link to="/ventes/commandes" class="text-sm text-teal-600 hover:underline">← Commandes</router-link>
          <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ commande.numero }}</h1>
          <p class="text-gray-600">
            {{ commande.client?.nom || 'Sans client' }} ·
            {{ commande.type === 'export' ? 'Export' : 'Local' }}
            <span v-if="commande.incoterm"> · {{ commande.incoterm }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <select v-model="newStatut" @change="changeStatut" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option v-for="s in processus" :key="s.key" :value="s.key">{{ s.label }}</option>
          </select>
          <button @click="printCommande" type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimer
          </button>
          <button
            v-if="!commande.bon_livraison"
            @click="transformerEnBl"
            :disabled="creatingBl"
            type="button"
            class="px-4 py-2 border border-teal-600 text-teal-700 rounded-lg text-sm bg-white hover:bg-teal-50 disabled:opacity-50"
          >
            {{ creatingBl ? 'Transformation…' : 'Transformer en BL' }}
          </button>
          <router-link
            v-else
            :to="'/ventes/locales/bons-livraison'"
            class="px-4 py-2 border border-emerald-600 text-emerald-700 rounded-lg text-sm bg-white hover:bg-emerald-50"
          >
            Voir BL {{ commande.bon_livraison.numero }}
          </router-link>
          <button @click="save" :disabled="saving" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700 disabled:opacity-50">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            @click="activeTab = tab.key"
            class="pb-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors"
            :class="activeTab === tab.key
              ? 'border-teal-600 text-teal-700'
              : 'border-transparent text-gray-500 hover:text-gray-800'"
          >
            {{ tab.label }}
            <span v-if="tab.key === 'chargement' && conteneurCards.length"> · {{ conteneurCards.length }}</span>
          </button>
        </nav>
      </div>

      <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>
      <p v-if="message" class="text-teal-600 text-sm mb-4">{{ message }}</p>

      <!-- TAB: Commande -->
      <div v-show="activeTab === 'commande'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-lg mb-4">Informations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-gray-500">Client</label>
                <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option :value="null">—</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-gray-500">Référence client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Date</label>
                <input type="date" v-model="form.date_commande" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-xs text-gray-500">Devise</label>
                <select v-model="form.devise" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option value="EUR">EUR</option>
                  <option value="USD">USD</option>
                  <option value="MAD">MAD</option>
                </select>
              </div>
            </div>
          </section>

          <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-lg mb-4">Résumé</h2>
            <div class="space-y-2 text-sm text-gray-700">
              <p><span class="font-semibold">{{ conteneurCards.length }}</span> conteneur(s)</p>
              <p><span class="font-semibold">{{ (form.lignes || []).length }}</span> article(s)</p>
              <p>
                <span class="font-semibold">{{ formatMoney(totalTtc) }} {{ form.devise }}</span>
                total commande
              </p>
              <div class="pt-2">
                <span
                  class="inline-flex px-3 py-1 rounded-full text-xs font-semibold"
                  :class="isFacturee ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ isFacturee ? 'Facturée' : 'À facturer' }}
                </span>
              </div>
            </div>
          </section>
        </div>

        <section class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="font-semibold text-lg">Articles commandés</h2>
            <button @click="addLigne" type="button" class="px-3 py-1.5 text-sm font-medium text-teal-600 hover:bg-teal-50 rounded-lg">+ Ligne</button>
          </div>
          <div class="overflow-x-auto border border-gray-100 rounded-lg">
            <table class="w-full text-sm min-w-max">
              <thead class="bg-gray-50">
                <tr class="text-left text-gray-500 border-b border-gray-100">
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Code article</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Désignation</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">HS CODE</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Commandé</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Réparti</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Reste</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Prix</th>
                  <th class="py-2.5 px-3 text-xs font-medium uppercase tracking-wide">Total</th>
                  <th class="py-2.5 px-3 w-10"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!form.lignes?.length">
                  <td colspan="9" class="py-8 text-center text-gray-400">Aucune ligne</td>
                </tr>
                <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-b border-gray-50 hover:bg-gray-50/60">
                  <td class="py-2 px-3">
                    <select v-model="ligne.article_id" @change="fillArticle(ligne)" class="border border-gray-300 rounded px-2 py-1 w-28 text-xs bg-white">
                      <option :value="null">—</option>
                      <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                    </select>
                  </td>
                  <td class="py-2 px-3">
                    <input v-model="ligne.designation" class="border border-gray-300 rounded px-2 py-1 w-44 text-xs" />
                  </td>
                  <td class="py-2 px-3 text-xs text-gray-600">{{ ligneHsCode(ligne) || '—' }}</td>
                  <td class="py-2 px-3">
                    <div class="flex items-center gap-1">
                      <input type="number" step="0.001" v-model.number="ligne.quantite" class="border border-gray-300 rounded px-2 py-1 w-20 text-xs" />
                      <input v-model="ligne.unite" class="border border-gray-300 rounded px-2 py-1 w-16 text-xs" />
                    </div>
                  </td>
                  <td class="py-2 px-3 text-xs">{{ Number(ligne.quantite_livree || 0) }}</td>
                  <td class="py-2 px-3 text-xs">{{ Math.max(0, Number(ligne.quantite || 0) - Number(ligne.quantite_livree || 0)) }}</td>
                  <td class="py-2 px-3">
                    <input type="number" step="0.01" v-model.number="ligne.prix" class="border border-gray-300 rounded px-2 py-1 w-20 text-xs" />
                  </td>
                  <td class="py-2 px-3 text-xs font-medium whitespace-nowrap">{{ formatMoney(ligneHt(ligne)) }} {{ form.devise }}</td>
                  <td class="py-2 px-3 text-right">
                    <button type="button" @click="form.lignes.splice(idx,1)" class="text-red-500 hover:text-red-700 text-lg leading-none" title="Supprimer">×</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- TAB: Chargement -->
      <div v-show="activeTab === 'chargement'">
        <div v-if="!conteneurCards.length" class="bg-white border border-gray-100 rounded-xl p-10 text-center shadow-sm">
          <p class="text-gray-500 mb-4">Aucun conteneur. Créez une livraison avec réservation booking.</p>
          <div class="flex flex-wrap justify-center gap-3">
            <button @click="createLivraison" :disabled="creatingLiv" type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm">
              {{ creatingLiv ? 'Création…' : '+ Livraison' }}
            </button>
            <router-link to="/ventes/commandes/logistique" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50">
              Ouvrir logistique
            </router-link>
          </div>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <article
            v-for="card in conteneurCards"
            :key="card.key"
            class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col"
          >
            <div class="flex items-start justify-between gap-3 mb-2">
              <h3 class="font-semibold text-gray-900 truncate">{{ card.numero_conteneur || 'Conteneur sans N°' }}</h3>
              <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="card.badgeClass">
                {{ card.statutLabel }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mb-3">
              {{ card.typeLabel }} · capacité {{ card.capacite }} palettes · Plomb {{ card.plomb || '—' }}
            </p>
            <p class="text-sm font-semibold text-gray-900">{{ card.palettesChargees }} / {{ card.capacite }} palettes</p>
            <p class="text-sm font-semibold text-gray-900 mb-3">{{ card.poidsEstime }} kg estimés</p>
            <div class="text-xs text-gray-600 space-y-1 mb-4">
              <p>Articles sélectionnés : {{ card.articlesCount }}</p>
              <p>Booking : {{ card.booking || '—' }}</p>
              <p>Transport : {{ card.transport }}</p>
            </div>
            <div class="mt-auto space-y-2">
              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="ouvrirLivraison(card.livraison_id)"
                  class="px-3 py-1.5 bg-teal-700 text-white rounded-lg text-sm font-medium hover:bg-teal-800"
                >Ouvrir</button>
                <button
                  type="button"
                  @click="ouvrirListeColisage()"
                  :disabled="creatingColisage"
                  class="px-3 py-1.5 border border-gray-300 bg-white rounded-lg text-sm inline-flex items-center gap-1.5 hover:bg-gray-50 disabled:opacity-50"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Liste de colisage
                </button>
              </div>
              <button
                v-if="card.conteneur_id"
                type="button"
                @click="supprimerConteneur(card)"
                class="px-3 py-1.5 border border-gray-200 bg-white rounded-lg text-sm text-red-600 hover:bg-red-50"
              >Supprimer</button>
            </div>
          </article>
        </div>
      </div>

      <!-- TAB: Facturation -->
      <div v-show="activeTab === 'facturation'">
        <section class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm max-w-3xl">
          <h2 class="font-semibold text-lg mb-4">Facturation globale</h2>
          <div class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-100 text-sm text-emerald-800">
            La facture est liée à la commande entière, même si la commande contient plusieurs conteneurs.
          </div>
          <div class="space-y-2 text-sm text-gray-700 mb-6">
            <p>Commande : <span class="font-semibold">{{ commande.numero }}</span></p>
            <p>Conteneurs : <span class="font-semibold">{{ conteneurCards.length }}</span></p>
            <p class="text-2xl font-bold text-gray-900 pt-1">{{ formatMoney(totalTtc) }} {{ form.devise }}</p>
            <div class="pt-1">
              <span
                class="inline-flex px-3 py-1 rounded-full text-xs font-semibold"
                :class="isFacturee ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ isFacturee ? 'Facturée' : 'À facturer' }}
              </span>
            </div>
          </div>
          <button
            type="button"
            @click="marquerAFacturer"
            :disabled="markingFacture"
            class="px-4 py-2 bg-teal-700 text-white rounded-lg text-sm font-medium hover:bg-teal-800 disabled:opacity-50"
          >
            {{ markingFacture ? 'Mise à jour…' : (isFacturee ? 'Mettre à jour facturation' : 'Marquer à facturer') }}
          </button>
        </section>
      </div>

      <!-- TAB: Documents -->
      <div v-show="activeTab === 'documents'">
        <h2 class="font-semibold text-lg mb-4">Documents export</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <section
            v-for="doc in documentCards"
            :key="doc.type"
            class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex flex-col"
          >
            <h3 class="font-semibold text-gray-900 mb-2">{{ doc.title }}</h3>
            <p class="text-sm text-gray-500 mb-4 flex-1">{{ doc.description }}</p>
            <button
              type="button"
              @click="generateDocument(doc.type)"
              :disabled="generatingDoc === doc.type"
              class="self-start px-4 py-2 bg-teal-700 text-white rounded-lg text-sm font-medium hover:bg-teal-800 disabled:opacity-50"
            >
              {{ generatingDoc === doc.type ? 'Génération…' : doc.button }}
            </button>
          </section>
        </div>
        <section v-if="generatedDocs.length" class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
          <h3 class="font-semibold mb-3">Documents générés</h3>
          <ul class="space-y-2">
            <li v-for="d in generatedDocs" :key="d.id" class="flex items-center justify-between text-sm border border-gray-100 rounded-lg px-3 py-2">
              <span>{{ d.titre }} <span class="text-gray-400">v{{ d.version }}</span></span>
              <a :href="`/api/documents/${d.id}/download`" target="_blank" class="text-teal-600 hover:underline">Télécharger</a>
            </li>
          </ul>
        </section>
      </div>
    </div>

    <!-- Print layout -->
    <div class="print-only">
      <div class="print-header">
        <h1>Commande {{ commande.numero }}</h1>
        <p>
          {{ clientName }} · {{ form.type === 'export' ? 'Export' : 'Local' }}
          <span v-if="form.incoterm"> · {{ form.incoterm }}</span>
          · {{ form.devise }}
        </p>
        <div class="print-meta">
          <div><strong>Date :</strong> {{ form.date_commande || '—' }}</div>
          <div><strong>Réf. client :</strong> {{ form.reference_client || '—' }}</div>
          <div><strong>Commercial :</strong> {{ form.commercial || '—' }}</div>
          <div><strong>Date souhaitée :</strong> {{ form.date_souhaitee || '—' }}</div>
          <div><strong>Paiement :</strong> {{ form.mode_paiement || '—' }}</div>
        </div>
      </div>
      <table class="print-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Désignation</th>
            <th>Qté</th>
            <th>Prix</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(ligne, idx) in form.lignes" :key="'pl-'+idx">
            <td>{{ articleLabel(ligne.article_id) }}</td>
            <td>{{ ligne.designation || '—' }}</td>
            <td>{{ ligne.quantite }} {{ ligne.unite }}</td>
            <td>{{ formatMoney(ligne.prix) }}</td>
            <td>{{ formatMoney(ligneHt(ligne)) }}</td>
          </tr>
        </tbody>
      </table>
      <div class="print-totals">
        <div>HT : <strong>{{ formatMoney(totalHt) }}</strong></div>
        <div>TVA : <strong>{{ formatMoney(totalTva) }}</strong></div>
        <div>TTC : <strong>{{ formatMoney(totalTtc) }} {{ form.devise }}</strong></div>
      </div>
    </div>
  </div>
  <div v-else class="p-10 text-center text-gray-500">Chargement…</div>
</template>

<script>
import axios from 'axios';

const PROCESSUS = [
  { key: 'brouillon', label: 'Brouillon' },
  { key: 'en_attente', label: 'En attente' },
  { key: 'confirmee', label: 'Confirmée' },
  { key: 'en_preparation', label: 'En préparation' },
  { key: 'en_production', label: 'En production' },
  { key: 'partiellement_livree', label: 'Partiellement livrée' },
  { key: 'livree', label: 'Livrée' },
  { key: 'cloturee', label: 'Clôturée' },
];

const LIV_STATUS_UI = {
  a_preparer: { label: 'À préparer', class: 'bg-gray-100 text-gray-700' },
  en_preparation: { label: 'À préparer', class: 'bg-gray-100 text-gray-700' },
  pret: { label: 'À charger', class: 'bg-amber-100 text-amber-800' },
  charge: { label: 'Chargé', class: 'bg-emerald-100 text-emerald-800' },
  livre: { label: 'Chargé', class: 'bg-emerald-100 text-emerald-800' },
  expedie: { label: 'Chargé', class: 'bg-emerald-100 text-emerald-800' },
  annule: { label: 'Annulé', class: 'bg-red-100 text-red-700' },
};

const DOCUMENT_CARDS = [
  {
    type: 'solas_vgm',
    title: 'VGM',
    description: 'Généré depuis le volet logistique + liste de colisage.',
    button: 'Générer VGM',
  },
  {
    type: 'fiche_chauffeur',
    title: 'Fiche Transporteur',
    description: 'Chauffeur, chargement, BL, marchandises et poids repris automatiquement.',
    button: 'Générer fiche',
  },
  {
    type: 'attestation_conditionnement',
    title: 'Attestation de conditionnement',
    description: 'Tableau automatique de la liste de colisage du conteneur.',
    button: 'Générer attestation',
  },
  {
    type: 'instructions_bl',
    title: 'Instructions de BL',
    description: 'Les champs seront préparés automatiquement. Le modèle Excel sera appliqué dès réception de votre fichier.',
    button: 'Générer instructions BL',
  },
];

export default {
  name: 'CommandeDetail',
  data() {
    return {
      commande: null,
      clients: [],
      articles: [],
      form: {},
      newStatut: '',
      saving: false,
      creatingLiv: false,
      creatingBl: false,
      creatingColisage: false,
      markingFacture: false,
      generatingDoc: null,
      generatedDocs: [],
      error: '',
      message: '',
      processus: PROCESSUS,
      activeTab: 'commande',
      tabs: [
        { key: 'commande', label: 'Commande' },
        { key: 'chargement', label: 'Chargement' },
        { key: 'facturation', label: 'Facturation' },
        { key: 'documents', label: 'Documents' },
      ],
      documentCards: DOCUMENT_CARDS,
    };
  },
  computed: {
    clientName() {
      const c = this.clients.find(x => x.id === this.form.client_id);
      return c?.nom || this.commande?.client?.nom || '—';
    },
    totalHt() {
      return (this.form.lignes || []).reduce((s, l) => s + this.ligneHt(l), 0);
    },
    totalTva() {
      return (this.form.lignes || []).reduce((s, l) => s + this.ligneTva(l), 0);
    },
    totalTtc() {
      return this.totalHt + this.totalTva;
    },
    isFacturee() {
      return Number(this.form.montant_facture || this.commande?.montant_facture || 0) > 0;
    },
    conteneurCards() {
      const cards = [];
      const livraisons = this.commande?.livraisons || [];
      for (const liv of livraisons) {
        const conteneurs = Array.isArray(liv.conteneurs) ? liv.conteneurs : [];
        if (conteneurs.length) {
          for (const c of conteneurs) {
            cards.push(this.buildConteneurCard(liv, c));
          }
        } else if (liv.numero_conteneur || liv.reservation_booking) {
          cards.push(this.buildConteneurCard(liv, {
            id: null,
            numero_conteneur: liv.numero_conteneur,
            numero_plomb: liv.numero_plomb,
            tare_conteneur: liv.tare_conteneur,
            transporteur: liv.transporteur,
            matricule_camion: liv.matricule_camion || liv.vehicule,
            chauffeur: liv.chauffeur,
          }));
        }
      }
      return cards;
    },
  },
  watch: {
    activeTab(val) {
      if (val === 'documents') this.loadDocuments();
    },
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    buildConteneurCard(liv, c) {
      const ui = LIV_STATUS_UI[liv.statut] || LIV_STATUS_UI.a_preparer;
      const transporteur = c.transporteur || liv.transporteur || '—';
      const matricule = c.matricule_camion || liv.matricule_camion || liv.vehicule || '—';
      const tare = Number(String(c.tare_conteneur || liv.tare_conteneur || '').replace(',', '.'));
      return {
        key: c.id ? `c-${c.id}` : `l-${liv.id}-legacy`,
        conteneur_id: c.id || null,
        livraison_id: liv.id,
        numero_conteneur: c.numero_conteneur || '',
        plomb: c.numero_plomb || liv.numero_plomb || '',
        booking: liv.numero_booking || liv.numero_reservation || '',
        transport: `${transporteur} / ${matricule}`,
        statutLabel: ui.label,
        badgeClass: ui.class,
        typeLabel: "40' HC",
        capacite: 28,
        palettesChargees: 0,
        poidsEstime: Number.isFinite(tare) && tare > 0 ? Math.round(tare).toLocaleString('fr-FR') : '0',
        articlesCount: 0,
      };
    },
    articleLabel(id) {
      const a = this.articles.find(x => x.id === id);
      return a ? a.code_article : '—';
    },
    ligneHsCode(ligne) {
      if (ligne.hs_code) return ligne.hs_code;
      const a = this.articles.find(x => x.id === ligne.article_id);
      return a?.hs_code || '';
    },
    ligneHt(l) {
      const brut = (Number(l.quantite) || 0) * (Number(l.prix) || 0);
      return Math.round(brut * (1 - (Number(l.remise) || 0) / 100) * 100) / 100;
    },
    ligneTva(l) {
      return Math.round(this.ligneHt(l) * ((Number(l.tva_taux) || 0) / 100) * 100) / 100;
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    printCommande() {
      this.$nextTick(() => window.print());
    },
    async load() {
      const { data } = await axios.get(`/api/commandes/${this.$route.params.id}`);
      this.commande = data;
      this.newStatut = data.statut;
      this.form = {
        date_commande: data.date_commande?.slice?.(0, 10) || data.date_commande,
        client_id: data.client_id,
        reference_client: data.reference_client || '',
        commercial: data.commercial || '',
        type: data.type || 'local',
        devise: data.devise || 'MAD',
        mode_paiement: data.mode_paiement || '',
        incoterm: data.incoterm || '',
        destination: data.destination || '',
        adresse: data.adresse || '',
        date_souhaitee: data.date_souhaitee?.slice?.(0, 10) || data.date_souhaitee || '',
        priorite: data.priorite || 'normale',
        observations: data.observations || '',
        montant_facture: Number(data.montant_facture || 0),
        montant_regle: Number(data.montant_regle || 0),
        lignes: (data.lignes || []).map(l => ({
          article_id: l.article_id,
          designation: l.designation || '',
          hs_code: l.article?.hs_code || '',
          calibre: l.calibre || '',
          type_emballage_primaire: l.type_emballage_primaire || '',
          reference_emballage: l.reference_emballage || '',
          type_emballage_secondaire: l.type_emballage_secondaire || '',
          unites_par_colis: l.unites_par_colis,
          colis_par_palette: l.colis_par_palette,
          nombre_total_par_palette: l.nombre_total_par_palette,
          poids_net_egoutte: l.poids_net_egoutte,
          quantite: Number(l.quantite),
          unite: l.unite || '',
          prix: Number(l.prix),
          remise: Number(l.remise || 0),
          tva_taux: Number(l.tva_taux ?? 20),
          date_production: l.date_production ? String(l.date_production).slice(0, 10) : '',
          lot: l.lot || '',
          cout_unitaire: Number(l.cout_unitaire || 0),
          quantite_disponible: Number(l.quantite_disponible || 0),
          quantite_reservee: Number(l.quantite_reservee || 0),
          quantite_a_produire: Number(l.quantite_a_produire || 0),
          quantite_livree: Number(l.quantite_livree || 0),
        })),
      };
      if (!this.form.lignes.length) this.addLigne();
    },
    async loadLookups() {
      const [c, a] = await Promise.all([
        axios.get('/api/clients', { params: { per_page: 500 } }),
        axios.get('/api/articles', { params: { per_page: 500 } }),
      ]);
      this.clients = c.data.data || c.data;
      this.articles = a.data.data || a.data;
    },
    async loadDocuments() {
      try {
        const { data } = await axios.get(`/api/commandes/${this.commande.id}/documents`);
        this.generatedDocs = data.data || data || [];
      } catch {
        this.generatedDocs = [];
      }
    },
    addLigne() {
      this.form.lignes.push({
        article_id: null, designation: '', hs_code: '', calibre: '', type_emballage_primaire: '',
        reference_emballage: '', type_emballage_secondaire: '',
        unites_par_colis: null, colis_par_palette: null, nombre_total_par_palette: null,
        poids_net_egoutte: null, quantite: 1, unite: 'kg', prix: 0,
        remise: 0, tva_taux: 20, date_production: '', lot: '',
        cout_unitaire: 0, quantite_disponible: 0,
        quantite_reservee: 0, quantite_a_produire: 0, quantite_livree: 0,
      });
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (!a) return;
      ligne.designation = a.designation || '';
      ligne.hs_code = a.hs_code || '';
      ligne.calibre = a.calibre || '';
      ligne.type_emballage_primaire = a.type_emballage_primaire || '';
      ligne.reference_emballage = a.type_palette || '';
      ligne.type_emballage_secondaire = a.type_emballage_secondaire || '';
      ligne.unites_par_colis = a.unites_par_colis ?? null;
      ligne.colis_par_palette = a.colis_par_palette ?? null;
      ligne.nombre_total_par_palette = a.nombre_total_par_palette ?? null;
      ligne.poids_net_egoutte = a.poids_net_egoutte ?? null;
      ligne.date_production = a.date_production ? String(a.date_production).slice(0, 10) : (ligne.date_production || '');
      ligne.lot = a.lot || ligne.lot || '';
      ligne.unite = a.unite_facturation || ligne.unite || 'kg';
      ligne.prix = Number(a.prix_vente || 0);
      ligne.cout_unitaire = Number(a.prix_achat || a.prix_achat_matiere || 0);
      ligne.tva_taux = Number(a.taux_tva ?? ligne.tva_taux ?? 20);
    },
    async save() {
      this.saving = true;
      this.error = '';
      this.message = '';
      try {
        const { data } = await axios.put(`/api/commandes/${this.commande.id}`, this.form);
        this.commande = data;
        this.message = 'Commande enregistrée';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
    async changeStatut() {
      try {
        await axios.post(`/api/commandes/${this.commande.id}/statut`, { statut: this.newStatut });
        this.commande.statut = this.newStatut;
        this.message = 'Statut mis à jour';
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur statut';
      }
    },
    async createLivraison() {
      this.creatingLiv = true;
      this.error = '';
      try {
        await axios.post('/api/livraisons', {
          commande_id: this.commande.id,
          type_livraison: this.commande.type === 'export' ? 'export' : 'locale',
          adresse: this.commande.adresse,
          quantite_a_livrer: this.commande.quantite_restante || 0,
          date_prevue: this.commande.date_souhaitee?.slice?.(0, 10) || null,
          statut: 'a_preparer',
          reservation_booking: true,
          conteneurs: [{}],
        });
        this.message = 'Livraison créée — complétez le booking en logistique';
        this.activeTab = 'chargement';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur création livraison';
      } finally {
        this.creatingLiv = false;
      }
    },
    ouvrirLivraison(livraisonId) {
      this.$router.push({ path: '/ventes/commandes/logistique', query: { open: livraisonId } });
    },
    async ouvrirListeColisage() {
      this.creatingColisage = true;
      this.error = '';
      try {
        const exports = this.commande.exportations || [];
        let exportId = exports[0]?.id;
        if (!exportId) {
          const { data } = await axios.post(`/api/exportations/from-commande/${this.commande.id}`);
          exportId = data.id;
          await this.load();
        }
        this.$router.push(`/exportations/${exportId}`);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur ouverture liste de colisage';
      } finally {
        this.creatingColisage = false;
      }
    },
    async supprimerConteneur(card) {
      if (!card.conteneur_id || !card.livraison_id) return;
      if (!confirm('Supprimer ce conteneur ?')) return;
      this.error = '';
      try {
        await axios.delete(`/api/livraisons/${card.livraison_id}/conteneurs/${card.conteneur_id}`);
        this.message = 'Conteneur supprimé';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur suppression conteneur';
      }
    },
    async marquerAFacturer() {
      this.markingFacture = true;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/commandes/${this.commande.id}/marquer-a-facturer`);
        this.commande = data;
        this.form.montant_facture = Number(data.montant_facture || 0);
        this.message = 'Commande marquée à facturer';
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur facturation';
      } finally {
        this.markingFacture = false;
      }
    },
    async generateDocument(type) {
      this.generatingDoc = type;
      this.error = '';
      this.message = '';
      try {
        const first = this.conteneurCards[0];
        const { data } = await axios.post(`/api/commandes/${this.commande.id}/documents`, {
          type,
          conteneur_id: first?.conteneur_id || null,
        });
        this.message = `${data.titre || 'Document'} généré`;
        await this.loadDocuments();
        if (data.id) {
          window.open(`/api/documents/${data.id}/download`, '_blank');
        }
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur génération document';
      } finally {
        this.generatingDoc = null;
      }
    },
    async transformerEnBl() {
      if (!confirm('Transformer cette commande en bon de livraison ? Elle disparaîtra de la liste des commandes.')) {
        return;
      }
      this.creatingBl = true;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/bons-livraison/from-commande/${this.commande.id}`, {
          date_heure_livraison: this.form.date_souhaitee ? `${this.form.date_souhaitee}T00:00` : null,
          informations_additionnelles: this.form.observations || null,
        });
        this.message = `Bon de livraison ${data.numero} créé`;
        this.$router.push('/ventes/locales/bons-livraison');
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur transformation en BL';
      } finally {
        this.creatingBl = false;
      }
    },
  },
};
</script>

<style scoped>
.print-only {
  display: none;
}

@media print {
  .no-print {
    display: none !important;
  }

  .print-only {
    display: block !important;
    color: #111;
    font-family: Georgia, 'Times New Roman', serif;
    padding: 12px;
  }

  .print-header h1 {
    margin: 0 0 4px;
    font-size: 22px;
  }

  .print-header p {
    margin: 0 0 12px;
    color: #444;
  }

  .print-meta {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px 16px;
    margin-bottom: 18px;
    font-size: 12px;
  }

  .print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
  }

  .print-table th,
  .print-table td {
    border: 1px solid #ccc;
    padding: 6px 8px;
    text-align: left;
  }

  .print-table th {
    background: #f3f4f6;
    font-weight: 600;
  }

  .print-totals {
    margin-top: 16px;
    display: flex;
    gap: 24px;
    font-size: 13px;
    justify-content: flex-end;
  }
}
</style>
