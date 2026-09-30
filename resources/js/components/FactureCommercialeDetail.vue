<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8" v-if="exportItem">
    <div class="max-w-7xl mx-auto pb-24">
      <!-- Header -->
      <div class="mb-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
          <router-link to="/ventes/export/facturation" class="text-sm text-teal-600 hover:underline">← Facturation export</router-link>
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1">Facture commerciale Export</h1>
          <p class="text-gray-600 mt-0.5">
            <span class="font-medium text-teal-700">{{ form.numero_facture || exportItem.numero }}</span>
            <span class="mx-1.5 text-gray-300">·</span>
            {{ form.dest_nom || exportItem.client?.nom || 'Sans destinataire' }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <button type="button" @click="generate" :disabled="generating || saving"
            class="px-4 py-2 border border-teal-600 text-teal-700 rounded-lg text-sm font-medium hover:bg-teal-50 disabled:opacity-50">
            {{ generating ? 'Génération…' : 'Générer facture' }}
          </button>
          <button type="button" @click="save" :disabled="saving || generating"
            class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 disabled:opacity-50">
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>

      <div v-if="msg" class="mb-4 px-4 py-3 rounded-lg bg-teal-50 border border-teal-100 text-sm text-teal-800">{{ msg }}</div>
      <div v-if="err" class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">{{ err }}</div>

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
                <option :value="null">Sélectionner…</option>
                <option v-for="e in exportateurs" :key="e.id" :value="e.id">
                  {{ e.nom }}{{ e.est_defaut ? ' (primaire)' : '' }}
                </option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Field label="Nom" v-model="form.exp_nom" />
              <Field label="Société" v-model="form.exp_nom_societe" />
              <Field label="Tél." v-model="form.exp_telephone" />
              <Field label="E-mail" v-model="form.exp_email" />
              <div class="sm:col-span-2">
                <Field label="Adresse" v-model="form.exp_adresse" multiline />
              </div>
            </div>
          </div>

          <div class="p-5 space-y-3">
            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-1">Client / Destinataire</p>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Fiche client</label>
              <select v-model="form.client_id" @change="onClientChange"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option :value="null">Sélectionner…</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Field label="Nom" v-model="form.dest_nom" />
              <Field label="Société" v-model="form.dest_societe" />
              <Field label="N° TVA" v-model="form.dest_tva" />
              <Field label="EORI" v-model="form.dest_eori" />
              <div class="sm:col-span-2">
                <Field label="Adresse" v-model="form.dest_adresse" multiline />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Infos facture -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Informations facture</h2>
        </div>
        <div class="p-5 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
          <Field label="N° facture" v-model="form.numero_facture" />
          <Field label="Date" v-model="form.date_creation" type="date" />
          <Field label="Réf. client" v-model="form.reference_commande_client" />
          <div class="min-w-0">
            <label class="block text-xs font-medium text-gray-500 mb-1">Commande</label>
            <select v-model="form.commande_id" @change="onCommandeChange"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 mb-1.5">
              <option :value="null">Sans commande</option>
              <option v-for="c in commandes" :key="c.id" :value="c.id">{{ c.numero }}</option>
            </select>
            <input v-model="form.numero_commande" placeholder="N° commande"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
          </div>
          <Field label="N° liste de colisage" v-model="form.numero_liste_colisage" />
          <Field label="Booking" v-model="form.booking" />
          <Field label="SWB / BL" v-model="form.numero_swb_bl" />
          <Field label="Devise" v-model="form.devise" />
          <Field label="Incoterm" v-model="form.incoterm" />
          <Field label="Conditions de paiement" v-model="form.conditions_paiement" />
          <Field label="Port chargement" v-model="form.port_chargement" />
          <Field label="Port destination" v-model="form.port_destination" />
          <div class="min-w-0">
            <Field label="Compagnie maritime" v-model="form.compagnie_maritime" />
            <p class="text-[11px] text-gray-400 mt-0.5">Automatique depuis la fiche logistique</p>
          </div>
          <Field label="Conteneur" v-model="form.conteneur" />
        </div>
      </div>

      <!-- Totaux résumé -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Total colis</p>
          <p class="text-xl font-bold text-gray-900">{{ totals.colis }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Total emballage</p>
          <p class="text-xl font-bold text-gray-900">{{ totals.emballage }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
          <p class="text-xs text-gray-500">Poids net total</p>
          <p class="text-xl font-bold text-gray-900">{{ formatNum(totals.net) }} <span class="text-sm font-medium text-gray-500">kg</span></p>
        </div>
        <div class="bg-white rounded-xl border border-teal-200 bg-teal-50/40 px-4 py-3">
          <p class="text-xs text-teal-700">Total FOB</p>
          <p class="text-xl font-bold text-teal-800">{{ formatMoney(totals.fob) }} <span class="text-sm font-medium">{{ form.devise }}</span></p>
        </div>
      </div>

      <!-- Articles -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
          <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Tableau articles</h2>
          <button type="button" @click="addLigne"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg border border-teal-200">
            + Ajouter une ligne
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 bg-white">
                <th class="py-3 px-3 min-w-[130px]">Article</th>
                <th class="py-3 px-3 min-w-[90px]">HS Code</th>
                <th class="py-3 px-3 min-w-[130px]">Désignation</th>
                <th class="py-3 px-3 min-w-[80px]">Calibre</th>
                <th class="py-3 px-3 min-w-[90px]">Lot</th>
                <th class="py-3 px-3 min-w-[100px]">Réf. emb.</th>
                <th class="py-3 px-3 min-w-[70px]">Colis</th>
                <th class="py-3 px-3 min-w-[80px]">Nb/colis</th>
                <th class="py-3 px-3 min-w-[80px]">Total emb.</th>
                <th class="py-3 px-3 min-w-[110px]">Poids net ég.</th>
                <th class="py-3 px-3 min-w-[90px]">PU</th>
                <th class="py-3 px-3 min-w-[100px]">Montant</th>
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
                <td class="p-2"><input v-model="ligne.hs_code" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" /></td>
                <td class="p-2"><input v-model="ligne.designation" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" /></td>
                <td class="p-2"><input v-model="ligne.calibre" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" /></td>
                <td class="p-2"><input v-model="ligne.numero_lot" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" /></td>
                <td class="p-2"><input v-model="ligne.reference_emballage" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" /></td>
                <td class="p-2">
                  <input type="number" min="0" v-model.number="ligne.nb_colis" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2">
                  <input type="number" min="0" v-model.number="ligne.nombre_par_colis" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2 pt-3 font-semibold text-gray-800">{{ ligne.total_emballage || ligne.nb_colis || 0 }}</td>
                <td class="p-2">
                  <input type="number" step="0.001" min="0" v-model.number="ligne.poids_net_egoutte_unitaire" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                  <div class="text-[11px] text-gray-500 mt-0.5">Σ {{ formatNum(ligne.poids_egoutte) }}</div>
                </td>
                <td class="p-2">
                  <input type="number" step="0.01" min="0" v-model.number="ligne.prix_unitaire" @input="recalcLigne(ligne)"
                    class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" />
                </td>
                <td class="p-2 pt-3 font-semibold text-gray-800 whitespace-nowrap">{{ formatMoney(ligne.montant) }}</td>
                <td class="p-2 pt-2">
                  <button type="button" @click="removeLigne(idx)" title="Supprimer"
                    class="w-8 h-8 inline-flex items-center justify-center rounded-md text-red-500 hover:bg-red-50 hover:text-red-700">×</button>
                </td>
              </tr>
              <tr v-if="!form.lignes.length">
                <td colspan="13" class="py-12 text-center text-gray-400">
                  Aucune ligne — liez une commande ou ajoutez des articles
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FOB / Fret / CFR -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
        <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Totaux facture</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs text-gray-500">Total facture FOB</p>
            <p class="text-2xl font-bold text-teal-800">{{ formatMoney(totals.fob) }} <span class="text-sm font-medium text-gray-600">{{ form.devise }}</span></p>
          </div>
          <div class="rounded-lg border border-gray-100 px-4 py-3">
            <label class="flex items-center gap-2.5 text-sm text-gray-700 mb-2 cursor-pointer">
              <input type="checkbox" v-model="form.transport_applique"
                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
              Transport (fret)
            </label>
            <input v-if="form.transport_applique" type="number" step="0.01" min="0" v-model.number="form.montant_transport"
              placeholder="Montant fret"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" />
            <p v-else class="text-sm text-gray-400">Optionnel — cochez pour ajouter le fret</p>
          </div>
          <div v-if="form.transport_applique" class="rounded-lg border border-teal-200 bg-teal-50/50 px-4 py-3">
            <p class="text-xs text-teal-700">Total facture CFR</p>
            <p class="text-2xl font-bold text-teal-800">{{ formatMoney(totals.cfr) }} <span class="text-sm font-medium text-teal-700">{{ form.devise }}</span></p>
          </div>
        </div>
      </div>

      <!-- Complémentaires + Banque -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Informations complémentaires</h2>
          </div>
          <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Field label="Origine de la marchandise" v-model="form.origine_marchandise" />
            <Field label="Conditions de paiement" v-model="form.conditions_paiement" />
            <div class="min-w-0">
              <label class="block text-xs font-medium text-gray-500 mb-1">Assurance</label>
              <select v-model="form.assurance"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option value="">—</option>
                <option v-for="a in assurances" :key="a" :value="a">{{ a }}</option>
              </select>
            </div>
            <div class="min-w-0">
              <label class="block text-xs font-medium text-gray-500 mb-1">Emballage</label>
              <select v-model="form.type_emballage_doc"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option value="">—</option>
                <option v-for="(label, key) in typesEmballage" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <div class="min-w-0">
              <Field label="Transitaire" v-model="form.transitaire" />
              <p class="text-[11px] text-gray-400 mt-0.5">Automatique depuis la fiche client</p>
            </div>
            <div class="min-w-0">
              <Field label="Compagnie maritime" v-model="form.compagnie_maritime" />
              <p class="text-[11px] text-gray-400 mt-0.5">Automatique depuis la fiche logistique</p>
            </div>
            <div class="sm:col-span-2 min-w-0">
              <label class="block text-xs font-medium text-gray-500 mb-1">Envoi des documents</label>
              <select v-model="form.envoi_documents"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option value="">—</option>
                <option v-for="e in envoisDocs" :key="e" :value="e">{{ e }}</option>
              </select>
            </div>
          </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Coordonnées bancaires</h2>
            <p class="text-xs text-gray-500 mt-0.5">Automatiques depuis le volet exportateur (fiche société)</p>
          </div>
          <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Field label="Banque" v-model="form.banque" />
            <Field label="Agence" v-model="form.agence" />
            <Field label="Bénéficiaire" v-model="form.beneficiaire" />
            <div class="sm:col-span-2"><Field label="N° de compte / IBAN" v-model="form.iban" /></div>
            <Field label="SWIFT / BIC" v-model="form.swift" />
            <Field label="RIB" v-model="form.rib" />
          </div>
        </section>
      </div>

      <div v-if="exportItem.documents?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3">Documents générés</h3>
        <div class="space-y-1">
          <a v-for="d in exportItem.documents" :key="d.id"
            :href="`/api/documents/${d.id}/download`" target="_blank"
            class="block text-sm text-teal-600 hover:underline truncate">
            {{ d.titre }} v{{ d.version }}
          </a>
        </div>
      </div>
    </div>

    <!-- Sticky bar -->
    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-3 lg:pl-[var(--sidebar-w,0)]">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-600">
          <span class="font-semibold text-gray-900">{{ totals.colis }}</span> colis
          <span class="mx-2 text-gray-300">·</span>
          FOB <span class="font-semibold text-teal-800">{{ formatMoney(totals.fob) }} {{ form.devise }}</span>
          <template v-if="form.transport_applique">
            <span class="mx-2 text-gray-300">·</span>
            CFR <span class="font-semibold text-teal-800">{{ formatMoney(totals.cfr) }} {{ form.devise }}</span>
          </template>
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
    Chargement de la facture…
  </div>
</template>

<script>
import axios from 'axios';
import { h } from 'vue';

const Field = {
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

const STRIP_KEYS = [
  'client', 'exportateur', 'commande', 'documents', 'pieces_jointes', 'lignes',
  'dossier_emballage', 'created_at', 'updated_at',
  'total_quantite', 'total_cartons', 'total_palettes', 'total_packages', 'total_emballage',
  'total_poids_net', 'total_poids_brut', 'total_poids_egoutte', 'total_valeur',
  'total_fob', 'total_cfr',
];

function emptyLigne() {
  return {
    article_id: null, code_article: '', hs_code: '', designation: '', calibre: '',
    numero_lot: '', reference_emballage: '', conditionnement: '',
    nb_colis: 0, nombre_par_colis: 0, total_emballage: 0, quantite: 0, cartons: 0,
    prix_unitaire: 0, montant: 0,
    poids_brut_unitaire: 0, poids_net_unitaire: 0, poids_net_egoutte_unitaire: 0,
    poids_brut: 0, poids_net: 0, poids_egoutte: 0,
  };
}

export default {
  name: 'FactureCommercialeDetail',
  components: { Field },
  data() {
    return {
      exportItem: null,
      articles: [],
      clients: [],
      exportateurs: [],
      commandes: [],
      form: { lignes: [] },
      saving: false,
      generating: false,
      msg: '',
      err: '',
      assurances: ['Aucune', 'CIF', 'Client', 'Exportateur'],
      typesEmballage: {
        perdu: 'Perdu',
        sous_reserve: 'Sous réserve de retour',
        les_deux: 'Perdu + sous réserve de retour',
      },
      envoisDocs: ['Original', 'Copie', 'Scan email', 'Courier', 'Transitaire', 'Banque'],
    };
  },
  computed: {
    totals() {
      let colis = 0, emballage = 0, net = 0, fob = 0;
      for (const l of this.form.lignes || []) {
        colis += Number(l.nb_colis || 0);
        emballage += Number(l.total_emballage || l.nb_colis || 0);
        net += Number(l.poids_net || 0);
        fob += Number(l.montant || 0);
      }
      const freight = this.form.transport_applique ? Number(this.form.montant_transport || 0) : 0;
      return { colis, emballage, net, fob, cfr: fob + freight };
    },
  },
  async mounted() {
    await Promise.all([this.loadRefs(), this.load()]);
  },
  methods: {
    formatNum(v) {
      return Number(v || 0).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    formatMoney(v) {
      return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    async loadRefs() {
      const [articles, clients, exportateurs, commandes, meta] = await Promise.all([
        axios.get('/api/articles', { params: { per_page: 500 } }),
        axios.get('/api/clients', { params: { per_page: 500, statut: 'client' } }),
        axios.get('/api/exportateurs', { params: { per_page: 200, actif: true } }),
        axios.get('/api/commandes', { params: { per_page: 200, type: 'export' } }),
        axios.get('/api/exportations-meta').catch(() => ({ data: {} })),
      ]);
      this.articles = articles.data.data || articles.data;
      this.clients = clients.data.data || clients.data;
      this.exportateurs = exportateurs.data.data || exportateurs.data;
      this.commandes = commandes.data.data || commandes.data;
      if (meta.data?.assurances) this.assurances = meta.data.assurances;
      if (meta.data?.types_emballage_doc) this.typesEmballage = meta.data.types_emballage_doc;
      if (meta.data?.envois_documents) this.envoisDocs = meta.data.envois_documents;
    },
    mapLignes(lignes) {
      return (lignes || []).map(l => ({
        ...emptyLigne(),
        ...l,
        nb_colis: Number(l.nb_colis || l.cartons || 0),
        nombre_par_colis: Number(l.nombre_par_colis || 0),
        total_emballage: Number(l.total_emballage || l.nb_colis || 0),
        prix_unitaire: Number(l.prix_unitaire || 0),
        montant: Number(l.montant || 0),
        quantite: Number(l.quantite || 0),
        poids_net_egoutte_unitaire: Number(l.poids_net_egoutte_unitaire || 0),
        poids_net_unitaire: Number(l.poids_net_unitaire || 0),
        poids_brut_unitaire: Number(l.poids_brut_unitaire || 0),
        poids_egoutte: Number(l.poids_egoutte || 0),
        poids_net: Number(l.poids_net || 0),
        poids_brut: Number(l.poids_brut || 0),
      }));
    },
    async load() {
      const { data } = await axios.get(`/api/exportations/${this.$route.params.id}`);
      this.exportItem = data;
      this.form = {
        ...data,
        date_creation: data.date_creation?.substring?.(0, 10) || data.date_creation || '',
        date_commande: data.date_commande?.substring?.(0, 10) || '',
        etd: data.etd?.substring?.(0, 10) || '',
        eta: data.eta?.substring?.(0, 10) || '',
        transport_applique: !!data.transport_applique,
        montant_transport: Number(data.montant_transport || 0),
        lignes: this.mapLignes(data.lignes),
      };
      this.applyAutoFills();
      if (this.form.commande_id) {
        await this.applyLogistiqueFromCommande(this.form.commande_id, false);
      }
    },
    applyBankFromExportateur(force = false) {
      const e = this.exportateurs.find(x => x.id === this.form.exportateur_id)
        || this.exportateurs.find(x => x.est_defaut)
        || this.exportateurs[0];
      if (!e) return;
      if (!this.form.exportateur_id) this.form.exportateur_id = e.id;
      const set = (key, val) => {
        if (force || !this.form[key]) this.form[key] = val || '';
      };
      set('banque', e.banque);
      set('agence', e.agence);
      set('beneficiaire', e.beneficiaire || e.nom_societe || e.nom);
      set('iban', e.iban);
      set('swift', e.swift);
      set('rib', e.rib);
    },
    applyTransitaireFromClient(force = false) {
      const c = this.clients.find(x => x.id === this.form.client_id);
      if (!c) return;
      if (force || !this.form.transitaire) {
        this.form.transitaire = c.transitaire || '';
      }
    },
    applyAutoFills() {
      this.applyBankFromExportateur(false);
      this.applyTransitaireFromClient(false);
    },
    async applyLogistiqueFromCommande(commandeId, force = false) {
      if (!commandeId) return;
      try {
        const { data } = await axios.get(`/api/commandes/${commandeId}`);
        const liv = (data.livraisons || []).slice().sort((a, b) => (b.id || 0) - (a.id || 0))[0];
        if (!liv) return;
        const set = (key, val) => {
          if (val == null || val === '') return;
          if (force || !this.form[key]) this.form[key] = val;
        };
        set('compagnie_maritime', liv.compagnie_maritime);
        set('booking', liv.numero_booking || liv.numero_reservation);
        set('numero_swb_bl', liv.numero_bl_swb);
        set('navire', liv.navire);
        set('port_chargement', liv.port_depart);
        set('port_destination', liv.port_arrivee);
        set('conteneur', liv.numero_conteneur);
        set('plomb_scelle', liv.numero_plomb);
        set('transporteur', liv.transporteur);
        if (liv.etd) set('etd', String(liv.etd).substring(0, 10));
        if (liv.eta) set('eta', String(liv.eta).substring(0, 10));
      } catch {
        /* ignore */
      }
    },
    onExportateurChange() {
      const e = this.exportateurs.find(x => x.id === this.form.exportateur_id);
      if (!e) return;
      this.form.exp_nom = e.nom || '';
      this.form.exp_nom_societe = e.nom_societe || e.nom || '';
      this.form.exp_adresse = e.adresse || '';
      this.form.exp_telephone = e.telephone || '';
      this.form.exp_email = e.email || '';
      this.form.exp_web = e.web || '';
      this.form.exp_ref_foodex = e.ref_foodex || '';
      this.applyBankFromExportateur(true);
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
      this.form.incoterm = this.form.incoterm || c.incoterm || '';
      this.form.devise = this.form.devise || c.devise || 'EUR';
      this.applyTransitaireFromClient(true);
      this.form.port_chargement = this.form.port_chargement || c.port_chargement || '';
      const cond = [c.delai_paiement, c.delai_paiement_type].filter(Boolean).join(' ');
      this.form.conditions_paiement = cond || this.form.conditions_paiement || '';
    },
    async onCommandeChange() {
      const c = this.commandes.find(x => x.id === this.form.commande_id);
      if (!c) return;
      this.form.numero_commande = c.numero || '';
      this.form.reference_commande_client = c.reference_client || this.form.reference_commande_client;
      this.form.incoterm = c.incoterm || this.form.incoterm;
      this.form.devise = c.devise || this.form.devise;
      this.form.destination = c.destination || this.form.destination;
      if (c.client_id && c.client_id !== this.form.client_id) {
        this.form.client_id = c.client_id;
        this.onClientChange();
      }
      await this.applyLogistiqueFromCommande(c.id, true);
      if (!this.form.lignes.length) {
        try {
          const { data } = await axios.get(`/api/commandes/${c.id}`);
          this.form.lignes = (data.lignes || []).map(l => {
            const a = l.article || this.articles.find(x => x.id === l.article_id) || {};
            const unites = Number(l.unites_par_colis || a.unites_par_colis || 0);
            const nb = unites > 0 ? Math.ceil(Number(l.quantite || 0) / unites) : Number(l.quantite || 0);
            const ligne = {
              ...emptyLigne(),
              article_id: l.article_id,
              code_article: a.code_article || '',
              hs_code: a.hs_code || '',
              designation: l.designation || a.designation || '',
              calibre: l.calibre || a.calibre || '',
              numero_lot: l.lot || '',
              reference_emballage: l.reference_emballage || a.type_palette || '',
              conditionnement: l.type_emballage_primaire || a.type_emballage_primaire || '',
              nb_colis: nb || 0,
              nombre_par_colis: unites,
              quantite: Number(l.quantite || 0),
              prix_unitaire: Number(l.prix_unitaire || 0),
              poids_net_egoutte_unitaire: Number(l.poids_net_egoutte || a.poids_net_egoutte_unitaire || 0),
              poids_net_unitaire: Number(a.poids_net_unitaire || a.poids_net || 0),
              poids_brut_unitaire: Number(a.poids_brut_unitaire || a.poids_brut || 0),
            };
            this.recalcLigne(ligne);
            return ligne;
          });
        } catch (e) {
          this.err = 'Impossible de charger les lignes de commande';
        }
      }
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
      ligne.reference_emballage = a.type_palette || a.emballage_ref || '';
      ligne.conditionnement = a.type_emballage_primaire || a.type_emballage || '';
      ligne.nombre_par_colis = Number(a.unites_par_colis || a.unites_par_carton || 0);
      ligne.prix_unitaire = Number(a.prix_vente || 0);
      ligne.poids_brut_unitaire = Number(a.poids_brut_unitaire ?? a.poids_brut ?? 0);
      ligne.poids_net_unitaire = Number(a.poids_net_unitaire ?? a.poids_net ?? 0);
      ligne.poids_net_egoutte_unitaire = Number(a.poids_net_egoutte_unitaire ?? a.poids_net_egoutte ?? 0);
      if (!ligne.nb_colis) ligne.nb_colis = 1;
      this.recalcLigne(ligne);
      if (a.sous_reserve_retour && !this.form.type_emballage_doc) {
        this.form.type_emballage_doc = 'sous_reserve';
      }
    },
    recalcLigne(ligne) {
      const n = Number(ligne.nb_colis || 0);
      ligne.quantite = Number(ligne.quantite || 0) || n;
      ligne.cartons = n;
      ligne.total_emballage = n;
      ligne.poids_brut = round3(Number(ligne.poids_brut_unitaire || 0) * n);
      ligne.poids_net = round3(Number(ligne.poids_net_unitaire || 0) * n);
      ligne.poids_egoutte = round3(Number(ligne.poids_net_egoutte_unitaire || 0) * n);
      const qty = Number(ligne.quantite || n);
      ligne.montant = Math.round(qty * Number(ligne.prix_unitaire || 0) * 100) / 100;
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
      return payload;
    },
    async save() {
      this.saving = true;
      this.msg = '';
      this.err = '';
      try {
        const { data } = await axios.put(`/api/exportations/${this.exportItem.id}`, this.buildPayload());
        this.exportItem = data;
        this.form.lignes = this.mapLignes(data.lignes);
        this.msg = 'Facture enregistrée';
      } catch (e) {
        const errors = e.response?.data?.errors;
        this.err = errors
          ? Object.values(errors).flat().join(' · ')
          : (e.response?.data?.message || 'Erreur lors de l\'enregistrement');
      } finally {
        this.saving = false;
      }
    },
    async generate() {
      this.generating = true;
      this.msg = '';
      this.err = '';
      try {
        await this.save();
        if (this.err) return;
        const { data } = await axios.post(`/api/exportations/${this.exportItem.id}/documents`, {
          type: 'facture_commerciale',
        });
        this.msg = `${data.titre || 'Facture'} générée`;
        await this.load();
        if (data.id) window.open(`/api/documents/${data.id}/download`, '_blank');
      } catch (e) {
        this.err = e.response?.data?.message || 'Erreur de génération';
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
