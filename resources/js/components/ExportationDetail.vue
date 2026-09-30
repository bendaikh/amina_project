<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8" v-if="exportItem">
    <div class="max-w-7xl mx-auto pb-24">
      <!-- Header -->
      <div class="mb-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <router-link to="/ventes/export/colisage" class="text-sm text-teal-600 hover:underline">← Liste de colisage</router-link>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1">Liste de colisage / Packing list</h1>
          <p class="text-gray-600 mt-0.5">
            <span class="font-medium text-teal-700">{{ exportItem.numero }}</span>
            <span class="mx-1.5 text-gray-300">·</span>
            {{ form.dest_nom || exportItem.client?.nom || 'Sans destinataire' }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <select v-model="newStatut" @change="changeStatut"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            <option v-for="s in statuts" :key="s" :value="s">{{ labelStatut(s) }}</option>
          </select>
          <button type="button" @click="generate('packing_list')" :disabled="generating || saving"
            class="px-4 py-2 border border-teal-600 text-teal-700 rounded-lg text-sm font-medium hover:bg-teal-50 disabled:opacity-50">
            {{ generating ? 'Génération…' : 'Générer PDF' }}
          </button>
          <button type="button" @click="save" :disabled="saving || generating"
            class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 disabled:opacity-50">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>

      <div v-if="docMsg" class="mb-4 px-4 py-3 rounded-lg bg-teal-50 border border-teal-100 text-sm text-teal-800">{{ docMsg }}</div>
      <div v-if="docErr" class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">{{ docErr }}</div>

      <!-- Parties -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Exportateur &amp; Destinataire</h2>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100">
          <div class="p-5 space-y-3">
            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-1">Exportateur</p>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Fiche société</label>
              <select v-model="form.exportateur_id" @change="onExportateurChange"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option :value="null">Sélectionner un exportateur…</option>
                <option v-for="e in exportateurs" :key="e.id" :value="e.id">
                  {{ e.nom }}{{ e.est_defaut ? ' (primaire)' : '' }}{{ e.ref_foodex ? ` — ${e.ref_foodex}` : '' }}
                </option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <PartyField label="Nom" v-model="form.exp_nom" />
              <PartyField label="Nom de la société" v-model="form.exp_nom_societe" />
              <PartyField label="Réf. Foodex" v-model="form.exp_ref_foodex" />
              <PartyField label="Téléphone" v-model="form.exp_telephone" />
              <PartyField label="E-mail" v-model="form.exp_email" />
              <PartyField label="Site web" v-model="form.exp_web" />
              <div class="sm:col-span-2">
                <PartyField label="Adresse" v-model="form.exp_adresse" multiline />
              </div>
            </div>
          </div>

          <div class="p-5 space-y-3">
            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-1">Destinataire</p>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Fiche client</label>
              <select v-model="form.client_id" @change="onClientChange"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option :value="null">Sélectionner un client…</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <PartyField label="Nom du destinataire" v-model="form.dest_nom" />
              <PartyField label="Nom de la société" v-model="form.dest_societe" />
              <PartyField label="N° TVA" v-model="form.dest_tva" />
              <PartyField label="EORI" v-model="form.dest_eori" />
              <PartyField label="Contact" v-model="form.dest_contact" />
              <div class="sm:col-span-2">
                <PartyField label="Adresse" v-model="form.dest_adresse" multiline />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Expédition -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Informations expédition</h2>
        </div>
        <div class="p-5 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
          <PartyField label="Réf. client" v-model="form.reference_commande_client" />
          <PartyField label="N° Booking" v-model="form.booking" />
          <PartyField label="N° SWB / BL" v-model="form.numero_swb_bl" />
          <PartyField label="N° conteneur" v-model="form.conteneur" />
          <PartyField label="N° plomb" v-model="form.plomb_scelle" />
          <PartyField label="Navire" v-model="form.navire" />
          <PartyField label="ETD" v-model="form.etd" type="date" />
          <PartyField label="ETA" v-model="form.eta" type="date" />
          <PartyField label="Port de départ" v-model="form.port_chargement" />
          <PartyField label="Port d'arrivée" v-model="form.port_destination" />
        </div>
      </div>

      <!-- Totaux poids -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Poids brut</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNum(totals.brut) }} <span class="text-sm font-medium text-gray-500">kg</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Poids net</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNum(totals.net) }} <span class="text-sm font-medium text-gray-500">kg</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Poids net ég.</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNum(totals.eg) }} <span class="text-sm font-medium text-gray-500">kg</span></p>
        </div>
        <div class="bg-white rounded-xl border border-teal-200 bg-teal-50/40 px-4 py-3">
          <p class="text-xs text-teal-700">Total packages</p>
          <p class="text-xl font-bold text-teal-800">{{ totals.packages }}</p>
        </div>
      </div>

      <!-- Articles -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
          <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Détail de la liste de colisage</h2>
          <button type="button" @click="addLigne"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg border border-teal-200">
            + Ajouter une ligne
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 bg-white">
                <th class="py-3 px-3 min-w-[140px]">Article</th>
                <th class="py-3 px-3 min-w-[90px]">HS Code</th>
                <th class="py-3 px-3 min-w-[140px]">Désignation</th>
                <th class="py-3 px-3 min-w-[80px]">Calibre</th>
                <th class="py-3 px-3 min-w-[90px]">N° lot</th>
                <th class="py-3 px-3 min-w-[130px]">Date prod.</th>
                <th class="py-3 px-3 min-w-[80px]">Nb colis</th>
                <th class="py-3 px-3 min-w-[100px]">Poids brut un.</th>
                <th class="py-3 px-3 min-w-[90px]">Total brut</th>
                <th class="py-3 px-3 min-w-[100px]">Poids net un.</th>
                <th class="py-3 px-3 min-w-[90px]">Total net</th>
                <th class="py-3 px-3 min-w-[100px]">Net ég. un.</th>
                <th class="py-3 px-3 min-w-[90px]">Total net ég.</th>
                <th class="py-3 px-2 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-b border-gray-100 hover:bg-gray-50/60 align-top">
                <td class="p-2">
                  <select v-model="ligne.article_id" @change="fillArticle(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm bg-white mb-1">
                    <option :value="null">Choisir…</option>
                    <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }}</option>
                  </select>
                  <input v-model="ligne.code_article" placeholder="Code"
                    class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input v-model="ligne.hs_code" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input v-model="ligne.designation" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input v-model="ligne.calibre" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input v-model="ligne.numero_lot" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input type="date" v-model="ligne.date_production" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input type="number" min="0" v-model.number="ligne.nb_colis" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input type="number" step="0.001" min="0" v-model.number="ligne.poids_brut_unitaire" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2 pt-3 font-semibold text-gray-800 whitespace-nowrap">{{ formatNum(ligneTotal(ligne, 'brut')) }}</td>
                <td class="p-2">
                  <input type="number" step="0.001" min="0" v-model.number="ligne.poids_net_unitaire" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2 pt-3 font-semibold text-gray-800 whitespace-nowrap">{{ formatNum(ligneTotal(ligne, 'net')) }}</td>
                <td class="p-2">
                  <input type="number" step="0.001" min="0" v-model.number="ligne.poids_net_egoutte_unitaire" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2 pt-3 font-semibold text-gray-800 whitespace-nowrap">{{ formatNum(ligneTotal(ligne, 'eg')) }}</td>
                <td class="p-2 pt-2">
                  <button type="button" @click="removeLigne(idx)" title="Supprimer"
                    class="w-8 h-8 inline-flex items-center justify-center rounded-md text-red-500 hover:bg-red-50 hover:text-red-700">
                    ×
                  </button>
                </td>
              </tr>
              <tr v-if="!form.lignes.length">
                <td colspan="14" class="py-12 text-center text-gray-400">
                  Aucune ligne — cliquez sur « Ajouter une ligne »
                </td>
              </tr>
            </tbody>
            <tfoot v-if="form.lignes.length">
              <tr class="bg-gray-50 border-t border-gray-200 font-semibold text-sm">
                <td colspan="6" class="py-3 px-3 text-right text-xs uppercase text-gray-500 tracking-wide">Total</td>
                <td class="py-3 px-3">{{ totals.packages }}</td>
                <td class="py-3 px-3"></td>
                <td class="py-3 px-3">{{ formatNum(totals.brut) }}</td>
                <td class="py-3 px-3"></td>
                <td class="py-3 px-3">{{ formatNum(totals.net) }}</td>
                <td class="py-3 px-3"></td>
                <td class="py-3 px-3">{{ formatNum(totals.eg) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Options + docs -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:col-span-2">
          <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Options dossier</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <PartyField label="Incoterm" v-model="form.incoterm" />
            <PartyField label="Devise" v-model="form.devise" />
            <PartyField label="Commercial" v-model="form.commercial" />
            <PartyField label="Destination" v-model="form.destination" />
            <label class="sm:col-span-2 flex items-center gap-2.5 text-sm text-gray-700 mt-1 cursor-pointer">
              <input type="checkbox" v-model="form.emballages_temporaires"
                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
              Emballages temporaires (régime 52)
            </label>
          </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3">Documents</h3>
          <div class="space-y-1.5 max-h-44 overflow-y-auto mb-4">
            <button v-for="(meta, type) in availableDocs" :key="type"
              type="button" @click="generate(type)" :disabled="generating"
              class="w-full text-left px-3 py-2 text-sm border border-gray-200 rounded-lg hover:border-teal-300 hover:bg-teal-50 disabled:opacity-50 transition-colors">
              {{ meta.titre }}
            </button>
          </div>
          <div v-if="exportItem.documents?.length" class="border-t border-gray-100 pt-3 space-y-1">
            <p class="text-xs font-medium text-gray-500 mb-1">Générés</p>
            <a v-for="d in exportItem.documents" :key="d.id"
              :href="`/api/documents/${d.id}/download`" target="_blank"
              class="block text-sm text-teal-600 hover:underline truncate">
              {{ d.titre }} v{{ d.version }}
            </a>
          </div>
        </section>
      </div>
    </div>

    <!-- Sticky save bar -->
    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-3 lg:pl-[var(--sidebar-w,0)]">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-600">
          <span class="font-semibold text-gray-900">{{ totals.packages }}</span> colis
          <span class="mx-2 text-gray-300">·</span>
          Net <span class="font-semibold text-gray-900">{{ formatNum(totals.net) }} kg</span>
          <span class="mx-2 text-gray-300">·</span>
          Brut <span class="font-semibold text-gray-900">{{ formatNum(totals.brut) }} kg</span>
        </div>
        <button type="button" @click="save" :disabled="saving"
          class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 disabled:opacity-50">
          {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </div>
  <div v-else class="p-16 text-center text-gray-500">
    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-teal-600 mx-auto mb-4"></div>
    Chargement de la liste de colisage…
  </div>
</template>

<script>
import axios from 'axios';
import { h } from 'vue';

const PartyField = {
  props: ['label', 'modelValue', 'type', 'multiline'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('div', { class: 'min-w-0' }, [
      h('label', { class: 'block text-xs font-medium text-gray-500 mb-1' }, props.label),
      props.multiline
        ? h('textarea', {
            class: 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500',
            rows: 2,
            value: props.modelValue ?? '',
            onInput: (e) => emit('update:modelValue', e.target.value),
          })
        : h('input', {
            type: props.type || 'text',
            class: 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500',
            value: props.modelValue ?? '',
            onInput: (e) => emit('update:modelValue', e.target.value),
          }),
    ]);
  },
};

const STATUTS = ['commande','preparation','production','emballage','reservation','chargement','documents','expedition','arrivee','cloturee'];

const STRIP_KEYS = [
  'client', 'exportateur', 'commande', 'documents', 'pieces_jointes', 'lignes',
  'dossier_emballage', 'created_at', 'updated_at',
  'total_quantite', 'total_cartons', 'total_palettes', 'total_packages', 'total_emballage',
  'total_poids_net', 'total_poids_brut', 'total_poids_egoutte', 'total_valeur',
  'total_fob', 'total_cfr',
];

function emptyLigne() {
  return {
    article_id: null,
    code_article: '',
    hs_code: '',
    designation: '',
    calibre: '',
    numero_lot: '',
    date_production: '',
    nb_colis: 0,
    quantite: 0,
    cartons: 0,
    palettes: 0,
    prix_unitaire: 0,
    poids_brut_unitaire: 0,
    poids_net_unitaire: 0,
    poids_net_egoutte_unitaire: 0,
    poids_brut: 0,
    poids_net: 0,
    poids_egoutte: 0,
  };
}

export default {
  name: 'ExportationDetail',
  components: { PartyField },
  data() {
    return {
      exportItem: null,
      articles: [],
      clients: [],
      exportateurs: [],
      docTypes: {},
      form: { lignes: [] },
      newStatut: 'commande',
      saving: false,
      generating: false,
      docMsg: '',
      docErr: '',
      statuts: STATUTS,
    };
  },
  computed: {
    availableDocs() {
      const out = {};
      for (const [type, meta] of Object.entries(this.docTypes)) {
        if (meta.needs_srr && !this.form.emballages_temporaires) continue;
        out[type] = meta;
      }
      return out;
    },
    totals() {
      let packages = 0, brut = 0, net = 0, eg = 0;
      for (const l of this.form.lignes || []) {
        packages += Number(l.nb_colis || 0);
        brut += this.ligneTotal(l, 'brut');
        net += this.ligneTotal(l, 'net');
        eg += this.ligneTotal(l, 'eg');
      }
      return { packages, brut, net, eg };
    },
  },
  async mounted() {
    await Promise.all([
      this.load(),
      this.loadArticles(),
      this.loadClients(),
      this.loadExportateurs(),
      this.loadDocTypes(),
    ]);
  },
  methods: {
    labelStatut(s) {
      const map = {
        commande: 'Commande', preparation: 'Préparation', production: 'Production', emballage: 'Emballage',
        reservation: 'Réservation', chargement: 'Chargement', documents: 'Documents', expedition: 'Expédition',
        arrivee: 'Arrivée', cloturee: 'Clôturée',
      };
      return map[s] || s;
    },
    formatNum(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
    },
    ligneTotal(ligne, kind) {
      const n = Number(ligne.nb_colis || 0);
      if (kind === 'brut') return Number(ligne.poids_brut || 0) || round3(Number(ligne.poids_brut_unitaire || 0) * n);
      if (kind === 'net') return Number(ligne.poids_net || 0) || round3(Number(ligne.poids_net_unitaire || 0) * n);
      return Number(ligne.poids_egoutte || 0) || round3(Number(ligne.poids_net_egoutte_unitaire || 0) * n);
    },
    recalcLigne(ligne) {
      const n = Number(ligne.nb_colis || 0);
      ligne.quantite = n;
      ligne.cartons = n;
      ligne.poids_brut = round3(Number(ligne.poids_brut_unitaire || 0) * n);
      ligne.poids_net = round3(Number(ligne.poids_net_unitaire || 0) * n);
      ligne.poids_egoutte = round3(Number(ligne.poids_net_egoutte_unitaire || 0) * n);
    },
    mapLignes(lignes) {
      return (lignes || []).map(l => ({
        ...emptyLigne(),
        ...l,
        date_production: l.date_production?.substring?.(0, 10) || l.date_production || '',
        nb_colis: Number(l.nb_colis || l.cartons || 0),
        poids_brut_unitaire: Number(l.poids_brut_unitaire || 0),
        poids_net_unitaire: Number(l.poids_net_unitaire || 0),
        poids_net_egoutte_unitaire: Number(l.poids_net_egoutte_unitaire || 0),
        poids_brut: Number(l.poids_brut || 0),
        poids_net: Number(l.poids_net || 0),
        poids_egoutte: Number(l.poids_egoutte || 0),
      }));
    },
    async load() {
      const { data } = await axios.get(`/api/exportations/${this.$route.params.id}`);
      this.exportItem = data;
      this.newStatut = data.statut;
      this.form = {
        ...data,
        date_commande: data.date_commande?.substring?.(0, 10) || data.date_commande || '',
        date_creation: data.date_creation?.substring?.(0, 10) || data.date_creation || '',
        etd: data.etd?.substring?.(0, 10) || data.etd || '',
        eta: data.eta?.substring?.(0, 10) || data.eta || '',
        lignes: this.mapLignes(data.lignes),
        dossier_emballage: data.dossier_emballage ? {
          ...data.dossier_emballage,
          date_dum: data.dossier_emballage.date_dum?.substring?.(0, 10) || '',
          date_limite_reimportation: data.dossier_emballage.date_limite_reimportation?.substring?.(0, 10) || '',
        } : {
          type_emballage: '', type_fut: '', reference_fut: '', quantite_exportee: 0,
          dum_52: '', date_dum: '', date_limite_reimportation: '', facture_concernee: '',
        },
      };
      if (!this.form.exportateur_id && this.exportateurs.length) {
        const defaut = this.exportateurs.find(e => e.est_defaut) || this.exportateurs[0];
        if (defaut && !this.form.exp_nom) {
          this.form.exportateur_id = defaut.id;
          this.onExportateurChange();
        }
      }
    },
    async loadArticles() {
      const { data } = await axios.get('/api/articles', { params: { per_page: 500 } });
      this.articles = data.data || data;
    },
    async loadClients() {
      const { data } = await axios.get('/api/clients', { params: { per_page: 500, statut: 'client' } });
      this.clients = data.data || data;
    },
    async loadExportateurs() {
      const { data } = await axios.get('/api/exportateurs', { params: { per_page: 200, actif: true } });
      this.exportateurs = data.data || data;
    },
    async loadDocTypes() {
      const { data } = await axios.get('/api/document-types');
      this.docTypes = data;
    },
    onExportateurChange() {
      const e = this.exportateurs.find(x => x.id === this.form.exportateur_id);
      if (!e) return;
      this.form.exp_nom = e.nom || '';
      this.form.exp_nom_societe = e.nom_societe || e.nom || '';
      this.form.exp_ref_foodex = e.ref_foodex || '';
      this.form.exp_adresse = e.adresse || '';
      this.form.exp_web = e.web || '';
      this.form.exp_telephone = e.telephone || '';
      this.form.exp_email = e.email || '';
    },
    onClientChange() {
      const c = this.clients.find(x => x.id === this.form.client_id);
      if (!c) return;
      const adresse = [c.adresse_livraison || c.adresse, c.ville, c.pays].filter(Boolean).join(', ');
      this.form.dest_nom = c.nom || '';
      this.form.dest_societe = c.nomination || c.nom || '';
      this.form.dest_adresse = adresse;
      this.form.dest_tva = c.numero_tva || '';
      this.form.dest_eori = c.eori || '';
      this.form.dest_contact = c.contact_nom || '';
      this.form.pays = c.pays || this.form.pays;
      this.form.destination = this.form.destination || c.pays || '';
      this.form.adresse_livraison = c.adresse_livraison || c.adresse || '';
      this.form.incoterm = this.form.incoterm || c.incoterm || '';
      this.form.devise = this.form.devise || c.devise || 'EUR';
      this.form.commercial = this.form.commercial || c.commercial_charge || '';
    },
    addLigne() {
      this.form.lignes.push(emptyLigne());
    },
    removeLigne(idx) {
      this.form.lignes.splice(idx, 1);
    },
    fillArticle(ligne) {
      const a = this.articles.find(x => x.id === ligne.article_id);
      if (!a) return;
      ligne.code_article = a.code_article || '';
      ligne.hs_code = a.hs_code || '';
      ligne.designation = a.designation || '';
      ligne.calibre = a.calibre || '';
      ligne.numero_lot = a.lot || '';
      ligne.date_production = a.date_production?.substring?.(0, 10) || a.date_production || '';
      ligne.poids_brut_unitaire = Number(a.poids_brut_unitaire ?? a.poids_brut ?? 0);
      ligne.poids_net_unitaire = Number(a.poids_net_unitaire ?? a.poids_net ?? 0);
      ligne.poids_net_egoutte_unitaire = Number(a.poids_net_egoutte_unitaire ?? a.poids_net_egoutte ?? 0);
      ligne.prix_unitaire = Number(a.prix_vente || 0);
      if (!ligne.nb_colis) ligne.nb_colis = 1;
      this.recalcLigne(ligne);
    },
    buildPayload() {
      this.form.lignes.forEach(l => this.recalcLigne(l));
      const payload = { ...this.form };
      STRIP_KEYS.forEach(k => delete payload[k]);
      payload.lignes = this.form.lignes.map(l => {
        const row = { ...l };
        delete row.article;
        delete row.id;
        delete row.exportation_id;
        delete row.created_at;
        delete row.updated_at;
        return row;
      });
      if (this.form.emballages_temporaires && this.form.dossier_emballage) {
        const d = { ...this.form.dossier_emballage };
        delete d.id;
        delete d.exportation_id;
        delete d.retours;
        delete d.client;
        delete d.created_at;
        delete d.updated_at;
        delete d.quantite_reimporte;
        delete d.quantite_restante;
        delete d.jours_restants;
        delete d.alerte;
        payload.dossier_emballage = d;
      }
      return payload;
    },
    async save() {
      this.saving = true;
      this.docMsg = '';
      this.docErr = '';
      try {
        const { data } = await axios.put(`/api/exportations/${this.exportItem.id}`, this.buildPayload());
        this.exportItem = data;
        this.form.lignes = this.mapLignes(data.lignes);
        this.docMsg = 'Liste de colisage enregistrée';
      } catch (e) {
        const errors = e.response?.data?.errors;
        this.docErr = errors
          ? Object.values(errors).flat().join(' · ')
          : (e.response?.data?.message || 'Erreur lors de l\'enregistrement');
      } finally {
        this.saving = false;
      }
    },
    async changeStatut() {
      try {
        await axios.post(`/api/exportations/${this.exportItem.id}/statut`, { statut: this.newStatut });
        this.form.statut = this.newStatut;
        this.exportItem.statut = this.newStatut;
      } catch (e) {
        this.docErr = e.response?.data?.message || 'Erreur de changement de statut';
      }
    },
    async generate(type) {
      this.generating = true;
      this.docMsg = '';
      this.docErr = '';
      try {
        await this.save();
        if (this.docErr) return;
        const { data } = await axios.post(`/api/exportations/${this.exportItem.id}/documents`, { type });
        this.docMsg = type === 'pack' ? `${data.count} document(s) généré(s)` : `${data.titre || 'Document'} généré`;
        await this.load();
        if (data.id) window.open(`/api/documents/${data.id}/download`, '_blank');
      } catch (e) {
        this.docErr = e.response?.data?.message || 'Erreur de génération';
      } finally {
        this.generating = false;
      }
    },
  },
};

function round3(n) {
  return Math.round((Number(n) || 0) * 1000) / 1000;
}
</script>
