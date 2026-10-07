<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Logistique</h1>
          <p class="text-gray-600 text-sm mt-1">
            À préparer → Préparation → Prêt → Chargé → Livré / Expédié
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvelle livraison
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 overflow-x-auto">
        <div class="flex items-center gap-1 min-w-max">
          <template v-for="(step, i) in statutSteps" :key="step.key">
            <button
              type="button"
              @click="statut = statut === step.key ? '' : step.key; load()"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
              :class="statut === step.key ? 'bg-teal-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-teal-50'"
            >{{ step.label }}</button>
            <span v-if="i < statutSteps.length - 1" class="text-gray-300 px-1">→</span>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-blue-500">
          <p class="text-xs text-gray-500">À préparer</p>
          <p class="text-2xl font-bold">{{ countBy('a_preparer') + countBy('en_preparation') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-amber-500">
          <p class="text-xs text-gray-500">Prêt / Chargé</p>
          <p class="text-2xl font-bold">{{ countBy('pret') + countBy('charge') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-emerald-500">
          <p class="text-xs text-gray-500">Livrées / Expédiées</p>
          <p class="text-2xl font-bold">{{ countBy('livre') + countBy('expedie') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-red-500">
          <p class="text-xs text-gray-500">Annulées</p>
          <p class="text-2xl font-bold">{{ countBy('annule') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N°, commande, client, transporteur…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">Ref commande</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Référence client</th>
              <th class="py-3 px-4">Date de livraison prévue</th>
              <th class="py-3 px-4">Date de chargement <span class="text-red-500">*</span></th>
              <th class="py-3 px-4">Date de cut-off <span class="text-red-500">*</span></th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="7" class="py-10 text-center text-gray-500">Aucune livraison</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4">
                <router-link v-if="item.commande_id" :to="`/ventes/commandes/${item.commande_id}`" class="font-semibold text-teal-700 hover:underline">
                  {{ item.commande?.numero || '—' }}
                </router-link>
                <span v-else class="font-semibold text-gray-400">—</span>
              </td>
              <td class="py-3 px-4">{{ item.commande?.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.commande?.reference_client || '—' }}</td>
              <td class="py-3 px-4">{{ formatDate(dateLivraisonPrevue(item)) }}</td>
              <td class="py-3 px-4">
                <span :class="dateAlertClass(item.date_chargement)">{{ formatDate(item.date_chargement) }}</span>
                <span v-if="!item.date_chargement" class="ml-1 text-amber-600 text-xs font-medium" title="Date importante manquante">⚠</span>
              </td>
              <td class="py-3 px-4">
                <span :class="dateAlertClass(item.date_cutoff)">{{ formatDate(item.date_cutoff) }}</span>
                <span v-if="!item.date_cutoff || isDatePast(item.date_cutoff)" class="ml-1 text-amber-600 text-xs font-medium" :title="!item.date_cutoff ? 'Date importante manquante' : 'Cut-off dépassé'">⚠</span>
              </td>
              <td class="py-3 px-4 text-right">
                <button @click="openEdit(item)" class="text-teal-600 hover:underline font-medium">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="app-modal-overlay">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ form.id ? 'Livraison' : 'Nouvelle livraison' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false">&times;</button>
          </div>
          <div class="app-modal__body space-y-6">
            <!-- Partie 1 -->
            <section class="bg-gray-50 rounded-lg p-5 border border-gray-200">
              <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">1</span>
                Commande
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                  <label class="text-sm text-gray-600">Réf. commande</label>
                  <select v-model="form.commande_id" @change="onCommandeChange" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                    <option :value="null">Sélectionner…</option>
                    <option v-for="c in commandes" :key="c.id" :value="c.id">{{ c.numero }}</option>
                  </select>
                </div>
                <div>
                  <label class="text-sm text-gray-600">Client</label>
                  <input :value="selectedClientNom" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Référence client</label>
                  <input :value="selectedReferenceClient" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Date de livraison prévue</label>
                  <input :value="form.date_prevue || '—'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-100 text-gray-600" />
                  <p class="text-xs text-gray-500 mt-1">Automatique (date souhaitée commande)</p>
                </div>
              </div>
            </section>

            <!-- Partie 2 -->
            <section class="bg-gray-50 rounded-lg p-5 border border-gray-200">
              <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">2</span>
                Réservation de booking
              </h3>

              <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Réservation booking</p>
                <div class="flex gap-4">
                  <label class="inline-flex items-center gap-2 cursor-pointer px-4 py-2 border rounded-lg"
                    :class="form.reservation_booking ? 'border-teal-500 bg-teal-50' : 'border-gray-200'">
                    <input type="radio" :value="true" v-model="form.reservation_booking" class="text-teal-600" />
                    <span class="text-sm font-medium">Oui</span>
                  </label>
                  <label class="inline-flex items-center gap-2 cursor-pointer px-4 py-2 border rounded-lg"
                    :class="!form.reservation_booking ? 'border-teal-500 bg-teal-50' : 'border-gray-200'">
                    <input type="radio" :value="false" v-model="form.reservation_booking" class="text-teal-600" />
                    <span class="text-sm font-medium">Non</span>
                  </label>
                </div>
              </div>

              <div v-if="form.reservation_booking" class="space-y-4">
              <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                <span class="font-bold shrink-0">⚠</span>
                <p>Les dates de <strong>chargement</strong> et de <strong>cut-off</strong> sont obligatoires et critiques pour le suivi logistique.</p>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="text-sm text-gray-600">Numéro de booking</label>
                  <input v-model="form.numero_booking" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Compagnie maritime</label>
                  <input v-model="form.compagnie_maritime" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Maersk, MSC…" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Numéro BL / SWB</label>
                  <input v-model="form.numero_bl_swb" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Date de chargement <span class="text-red-500">*</span></label>
                  <input type="date" v-model="form.date_chargement" required class="w-full border border-gray-300 rounded-lg px-3 py-2" :class="{ 'border-amber-400 ring-1 ring-amber-300': !form.date_chargement }" />
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm text-gray-600">Date de cut-off <span class="text-red-500">*</span> — Date limite de dépôt des documents</label>
                  <input type="date" v-model="form.date_cutoff" required class="w-full border border-gray-300 rounded-lg px-3 py-2" :class="{ 'border-amber-400 ring-1 ring-amber-300': !form.date_cutoff }" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Nom du navire / voyage</label>
                  <input v-model="form.navire" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Port de départ</label>
                  <input v-model="form.port_depart" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Port d'arrivée</label>
                  <input v-model="form.port_arrivee" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">ETA</label>
                  <input type="date" v-model="form.eta" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">ETD</label>
                  <input type="date" v-model="form.etd" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
              </div>

              <div class="pt-2 space-y-4">
                <div class="flex items-center justify-between gap-3">
                  <h4 class="text-sm font-semibold text-gray-800">Conteneurs</h4>
                  <button
                    type="button"
                    @click="addConteneur"
                    class="px-3 py-1.5 text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg"
                  >
                    + Ajouter un conteneur
                  </button>
                </div>

                <div
                  v-for="(conteneur, idx) in form.conteneurs"
                  :key="conteneur._key || conteneur.id || idx"
                  class="bg-white border border-gray-200 rounded-lg p-4 space-y-4"
                >
                  <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-800">Conteneur {{ idx + 1 }}</p>
                    <button
                      v-if="form.conteneurs.length > 1"
                      type="button"
                      @click="removeConteneur(idx)"
                      class="text-sm text-red-600 hover:underline"
                    >
                      Supprimer
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                      <label class="text-sm text-gray-600">N° de conteneur</label>
                      <input v-model="conteneur.numero_conteneur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                    </div>
                    <div>
                      <label class="text-sm text-gray-600">Tare conteneur</label>
                      <input v-model="conteneur.tare_conteneur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                    </div>
                    <div>
                      <label class="text-sm text-gray-600">N° de plomb</label>
                      <input v-model="conteneur.numero_plomb" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                      <div class="flex flex-wrap items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <span class="text-sm font-medium text-gray-700">Changement du plomb</span>
                        <button
                          type="button"
                          @click="conteneur.changement_plomb = !conteneur.changement_plomb"
                          class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
                          :class="conteneur.changement_plomb ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        >
                          {{ conteneur.changement_plomb ? 'Activé' : 'Activer' }}
                        </button>
                      </div>
                    </div>
                    <template v-if="conteneur.changement_plomb">
                      <div class="md:col-span-2">
                        <label class="text-sm text-gray-600">Raisons</label>
                        <textarea v-model="conteneur.raison_changement_plomb" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Motif du changement de plomb…"></textarea>
                      </div>
                      <div>
                        <label class="text-sm text-gray-600">Renseigner le nouveau plomb</label>
                        <input v-model="conteneur.nouveau_plomb" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                      </div>
                    </template>
                  </div>

                  <div class="border-t border-gray-100 pt-4">
                    <h5 class="text-sm font-semibold text-gray-800 mb-3">Transport routier — Conteneur {{ idx + 1 }}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div>
                        <label class="text-sm text-gray-600">Matricule camion</label>
                        <input v-model="conteneur.matricule_camion" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                      </div>
                      <div>
                        <label class="text-sm text-gray-600">Chauffeur</label>
                        <input v-model="conteneur.chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                      </div>
                      <div>
                        <label class="text-sm text-gray-600">Transporteur</label>
                        <input v-model="conteneur.transporteur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                      </div>
                      <div>
                        <label class="text-sm text-gray-600">CIN chauffeur</label>
                        <input v-model="conteneur.cin_chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° CIN" />
                      </div>
                      <div class="md:col-span-2">
                        <label class="text-sm text-gray-600">Scanner CIN chauffeur</label>
                        <div class="flex flex-col sm:flex-row gap-3 items-start">
                          <input
                            type="file"
                            accept="image/*,.pdf"
                            capture="environment"
                            @change="onConteneurCinScanSelect($event, idx)"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                          />
                          <a
                            v-if="conteneurCinScanUrl(conteneur)"
                            :href="conteneurCinScanUrl(conteneur)"
                            target="_blank"
                            class="text-sm text-teal-600 hover:underline whitespace-nowrap"
                          >Voir le scan</a>
                        </div>
                        <p v-if="conteneur._cinScanFile" class="text-xs text-gray-500 mt-1">
                          Fichier sélectionné : {{ conteneur._cinScanFile.name }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              </div>
            </section>

            <!-- Partie 3 — Transport routier (sans booking) -->
            <section v-if="!form.reservation_booking" class="bg-gray-50 rounded-lg p-5 border border-gray-200">
              <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center text-xs">3</span>
                Transport routier
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="text-sm text-gray-600">Matricule camion</label>
                  <input v-model="form.matricule_camion" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Chauffeur</label>
                  <input v-model="form.chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">Transporteur</label>
                  <input v-model="form.transporteur" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                </div>
                <div>
                  <label class="text-sm text-gray-600">CIN chauffeur</label>
                  <input v-model="form.cin_chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="N° CIN" />
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm text-gray-600">Scanner CIN chauffeur</label>
                  <div class="flex flex-col sm:flex-row gap-3 items-start">
                    <input
                      type="file"
                      accept="image/*,.pdf"
                      capture="environment"
                      @change="onCinScanSelect"
                      class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                    />
                    <a
                      v-if="cinScanUrl"
                      :href="cinScanUrl"
                      target="_blank"
                      class="text-sm text-teal-600 hover:underline whitespace-nowrap"
                    >Voir le scan</a>
                  </div>
                  <p v-if="cinScanFile" class="text-xs text-gray-500 mt-1">Fichier sélectionné : {{ cinScanFile.name }}</p>
                </div>
              </div>
            </section>

            <div v-if="form.id" class="p-4 bg-white rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-2">Pièces jointes</p>
              <input type="file" @change="uploadPiece" class="text-sm w-full mb-2" />
              <ul class="space-y-1">
                <li v-for="p in pieces" :key="p.id" class="text-sm text-gray-700 truncate">{{ p.nom_fichier }}</li>
              </ul>
              <p v-if="!pieces.length" class="text-sm text-gray-500">Aucun document</p>
            </div>
          </div>
          <div class="app-modal__footer">
            <p v-if="error" class="text-red-600 text-sm mr-auto">{{ error }}</p>
            <button @click="showModal=false" type="button" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
            <button @click="save" type="button" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg">{{ saving ? 'Enregistrement…' : 'Enregistrer' }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

const STATUTS = [
  { key: 'a_preparer', label: 'À préparer' },
  { key: 'en_preparation', label: 'En préparation' },
  { key: 'pret', label: 'Prêt' },
  { key: 'charge', label: 'Chargé' },
  { key: 'livre', label: 'Livré' },
  { key: 'expedie', label: 'Expédié' },
  { key: 'annule', label: 'Annulé' },
];

function createEmptyConteneur(overrides = {}) {
  return {
    id: null,
    _key: `c-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    numero_conteneur: '',
    tare_conteneur: '',
    numero_plomb: '',
    changement_plomb: false,
    raison_changement_plomb: '',
    nouveau_plomb: '',
    matricule_camion: '',
    chauffeur: '',
    transporteur: '',
    cin_chauffeur: '',
    cin_chauffeur_scan: '',
    _cinScanFile: null,
    ...overrides,
  };
}

function createEmptyForm() {
  return {
    id: null,
    commande_id: null,
    reservation_booking: false,
    type_livraison: 'locale',
    date_prevue: '',
    date_chargement: '',
    date_cutoff: '',
    numero_booking: '',
    numero_bl_swb: '',
    compagnie_maritime: '',
    navire: '',
    port_depart: '',
    port_arrivee: '',
    eta: '',
    etd: '',
    conteneurs: [createEmptyConteneur()],
    numero_conteneur: '',
    tare_conteneur: '',
    numero_plomb: '',
    changement_plomb: false,
    raison_changement_plomb: '',
    nouveau_plomb: '',
    matricule_camion: '',
    chauffeur: '',
    transporteur: '',
    cin_chauffeur: '',
    cin_chauffeur_scan: '',
    statut: 'a_preparer',
    observations: '',
    adresse: '',
  };
}

export default {
  name: 'VentesLogistique',
  data() {
    return {
      items: [],
      allForCounts: [],
      commandes: [],
      pieces: [],
      loading: false,
      saving: false,
      showModal: false,
      search: '',
      statut: '',
      error: '',
      timer: null,
      statutSteps: STATUTS,
      cinScanFile: null,
      form: createEmptyForm(),
    };
  },
  computed: {
    selectedCommande() {
      return this.commandes.find(c => c.id === this.form.commande_id) || null;
    },
    selectedClientNom() {
      return this.selectedCommande?.client?.nom || '—';
    },
    selectedReferenceClient() {
      return this.selectedCommande?.reference_client || '—';
    },
    cinScanUrl() {
      if (!this.form.cin_chauffeur_scan) return null;
      if (String(this.form.cin_chauffeur_scan).startsWith('http')) return this.form.cin_chauffeur_scan;
      return `/storage/${this.form.cin_chauffeur_scan}`;
    },
  },
  async mounted() {
    await Promise.all([this.load(), this.loadLookups()]);
    const openId = Number(this.$route.query.open);
    if (openId) {
      const item = this.items.find(i => i.id === openId);
      if (item) {
        await this.openEdit(item);
      } else {
        try {
          const { data } = await axios.get(`/api/livraisons/${openId}`);
          await this.openEdit(data);
        } catch { /* ignore */ }
      }
    }
  },
  methods: {
    emptyConteneur() {
      return createEmptyConteneur();
    },
    emptyForm() {
      return createEmptyForm();
    },
    addConteneur() {
      this.form.conteneurs.push(createEmptyConteneur());
    },
    removeConteneur(idx) {
      if (this.form.conteneurs.length <= 1) return;
      this.form.conteneurs.splice(idx, 1);
    },
    mapConteneur(c = {}) {
      return createEmptyConteneur({
        id: c.id || null,
        _key: c.id ? `id-${c.id}` : `c-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        numero_conteneur: c.numero_conteneur || '',
        tare_conteneur: c.tare_conteneur || '',
        numero_plomb: c.numero_plomb || '',
        changement_plomb: !!c.changement_plomb,
        raison_changement_plomb: c.raison_changement_plomb || '',
        nouveau_plomb: c.nouveau_plomb || '',
        matricule_camion: c.matricule_camion || '',
        chauffeur: c.chauffeur || '',
        transporteur: c.transporteur || '',
        cin_chauffeur: c.cin_chauffeur || '',
        cin_chauffeur_scan: c.cin_chauffeur_scan || '',
        _cinScanFile: null,
      });
    },
    conteneurCinScanUrl(conteneur) {
      if (!conteneur?.cin_chauffeur_scan) return null;
      if (String(conteneur.cin_chauffeur_scan).startsWith('http')) return conteneur.cin_chauffeur_scan;
      return `/storage/${conteneur.cin_chauffeur_scan}`;
    },
    onConteneurCinScanSelect(e, idx) {
      const file = e.target.files?.[0] || null;
      if (!this.form.conteneurs[idx]) return;
      this.form.conteneurs[idx]._cinScanFile = file;
    },
    sliceDate(v) {
      if (!v) return '';
      return String(v).slice(0, 10);
    },
    formatDate(v) {
      const d = this.sliceDate(v);
      if (!d) return '—';
      const [y, m, day] = d.split('-');
      if (!y || !m || !day) return d;
      return `${day}/${m}/${y}`;
    },
    dateLivraisonPrevue(item) {
      return item.date_prevue || item.commande?.date_souhaitee || null;
    },
    isDatePast(v) {
      const d = this.sliceDate(v);
      if (!d) return false;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const date = new Date(`${d}T00:00:00`);
      return date < today;
    },
    dateAlertClass(v) {
      if (!v) return 'text-amber-700 font-medium';
      if (this.isDatePast(v)) return 'text-red-600 font-medium';
      return '';
    },
    countBy(s) {
      return this.allForCounts.filter(i => i.statut === s).length;
    },
    labelStatut(s) {
      return STATUTS.find(x => x.key === s)?.label || s;
    },
    badgeClass(s) {
      return ({
        a_preparer: 'bg-slate-100 text-slate-700',
        en_preparation: 'bg-blue-100 text-blue-700',
        pret: 'bg-cyan-100 text-cyan-700',
        charge: 'bg-amber-100 text-amber-700',
        livre: 'bg-green-100 text-green-700',
        expedie: 'bg-emerald-100 text-emerald-800',
        annule: 'bg-red-100 text-red-700',
      })[s] || 'bg-gray-100 text-gray-700';
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/livraisons', {
          params: {
            search: this.search,
            statut: this.statut,
            per_page: 50,
          },
        });
        this.items = data.data || data;
        const all = await axios.get('/api/livraisons', { params: { per_page: 200 } });
        this.allForCounts = all.data.data || all.data;
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const { data } = await axios.get('/api/commandes', { params: { per_page: 200 } });
      this.commandes = data.data || data;
    },
    openCreate() {
      this.form = this.emptyForm();
      this.pieces = [];
      this.cinScanFile = null;
      this.error = '';
      this.showModal = true;
    },
    mapItemToForm(item) {
      let conteneurs = Array.isArray(item.conteneurs)
        ? item.conteneurs.map(c => this.mapConteneur(c))
        : [];

      if (!conteneurs.length && (
        item.numero_conteneur || item.tare_conteneur || item.numero_plomb
        || item.matricule_camion || item.chauffeur || item.transporteur || item.cin_chauffeur
      )) {
        conteneurs = [this.mapConteneur({
          numero_conteneur: item.numero_conteneur,
          tare_conteneur: item.tare_conteneur,
          numero_plomb: item.numero_plomb,
          changement_plomb: item.changement_plomb,
          raison_changement_plomb: item.raison_changement_plomb,
          nouveau_plomb: item.nouveau_plomb,
          matricule_camion: item.matricule_camion || item.vehicule,
          chauffeur: item.chauffeur,
          transporteur: item.transporteur,
          cin_chauffeur: item.cin_chauffeur,
          cin_chauffeur_scan: item.cin_chauffeur_scan,
        })];
      }

      if (!conteneurs.length) {
        conteneurs = [this.emptyConteneur()];
      }

      return {
        id: item.id,
        commande_id: item.commande_id,
        reservation_booking: !!item.reservation_booking,
        type_livraison: item.type_livraison || 'locale',
        date_prevue: this.sliceDate(item.date_prevue || item.commande?.date_souhaitee),
        date_chargement: this.sliceDate(item.date_chargement),
        date_cutoff: this.sliceDate(item.date_cutoff),
        numero_booking: item.numero_booking || item.numero_reservation || '',
        numero_bl_swb: item.numero_bl_swb || '',
        compagnie_maritime: item.compagnie_maritime || '',
        navire: item.navire || '',
        port_depart: item.port_depart || '',
        port_arrivee: item.port_arrivee || '',
        eta: this.sliceDate(item.eta),
        etd: this.sliceDate(item.etd),
        conteneurs,
        numero_conteneur: item.numero_conteneur || '',
        tare_conteneur: item.tare_conteneur || '',
        numero_plomb: item.numero_plomb || '',
        changement_plomb: !!item.changement_plomb,
        raison_changement_plomb: item.raison_changement_plomb || '',
        nouveau_plomb: item.nouveau_plomb || '',
        matricule_camion: item.matricule_camion || item.vehicule || '',
        chauffeur: item.chauffeur || '',
        transporteur: item.transporteur || '',
        cin_chauffeur: item.cin_chauffeur || '',
        cin_chauffeur_scan: item.cin_chauffeur_scan || '',
        statut: item.statut || 'a_preparer',
        observations: item.observations || '',
        adresse: item.adresse || '',
      };
    },
    async openEdit(item) {
      this.form = this.mapItemToForm(item);
      this.cinScanFile = null;
      this.error = '';
      this.showModal = true;
      try {
        const { data } = await axios.get(`/api/livraisons/${item.id}`);
        this.form = this.mapItemToForm(data);
        this.pieces = data.pieces_jointes || data.piecesJointes || [];
      } catch {
        this.pieces = [];
      }
    },
    onCommandeChange() {
      const c = this.selectedCommande;
      if (!c) {
        this.form.date_prevue = '';
        return;
      }
      this.form.adresse = c.adresse || this.form.adresse;
      this.form.type_livraison = c.type === 'export' ? 'export' : 'locale';
      this.form.date_prevue = this.sliceDate(c.date_souhaitee);
      if (c.destination && !this.form.port_depart) {
        this.form.port_depart = c.destination;
      }
    },
    onCinScanSelect(e) {
      this.cinScanFile = e.target.files?.[0] || null;
    },
    async uploadCinScan(livraisonId, conteneurId = null, file = null) {
      const scanFile = file || this.cinScanFile;
      if (!scanFile || !livraisonId) return null;
      const fd = new FormData();
      fd.append('fichier', scanFile);
      if (conteneurId) fd.append('conteneur_id', conteneurId);
      const { data } = await axios.post(`/api/livraisons/${livraisonId}/cin-scan`, fd);
      if (!conteneurId) {
        this.form.cin_chauffeur_scan = data.cin_chauffeur_scan;
        this.cinScanFile = null;
      }
      return data;
    },
    async uploadConteneurCinScans(livraisonId, savedConteneurs = []) {
      for (let i = 0; i < this.form.conteneurs.length; i++) {
        const c = this.form.conteneurs[i];
        if (!c?._cinScanFile) continue;
        const saved = savedConteneurs[i];
        const conteneurId = saved?.id || c.id || null;
        if (!conteneurId) continue;
        const data = await this.uploadCinScan(livraisonId, conteneurId, c._cinScanFile);
        if (data?.cin_chauffeur_scan) {
          c.cin_chauffeur_scan = data.cin_chauffeur_scan;
          c._cinScanFile = null;
        }
      }
    },
    buildPayload() {
      const payload = { ...this.form };
      delete payload.cin_chauffeur_scan;

      const conteneurs = (this.form.conteneurs || []).map(c => ({
        id: c.id || null,
        numero_conteneur: c.numero_conteneur || null,
        tare_conteneur: c.tare_conteneur || null,
        numero_plomb: c.numero_plomb || null,
        changement_plomb: !!c.changement_plomb,
        raison_changement_plomb: c.raison_changement_plomb || null,
        nouveau_plomb: c.nouveau_plomb || null,
        matricule_camion: c.matricule_camion || null,
        chauffeur: c.chauffeur || null,
        transporteur: c.transporteur || null,
        cin_chauffeur: c.cin_chauffeur || null,
      }));

      if (this.form.reservation_booking) {
        payload.conteneurs = conteneurs;
        const first = conteneurs[0] || {};
        payload.numero_conteneur = first.numero_conteneur || null;
        payload.tare_conteneur = first.tare_conteneur || null;
        payload.numero_plomb = first.numero_plomb || null;
        payload.changement_plomb = !!first.changement_plomb;
        payload.raison_changement_plomb = first.raison_changement_plomb || null;
        payload.nouveau_plomb = first.nouveau_plomb || null;
        payload.matricule_camion = first.matricule_camion || null;
        payload.chauffeur = first.chauffeur || null;
        payload.transporteur = first.transporteur || null;
        payload.cin_chauffeur = first.cin_chauffeur || null;
      } else {
        payload.conteneurs = [];
      }

      if (this.selectedCommande?.date_souhaitee) {
        payload.date_prevue = this.sliceDate(this.selectedCommande.date_souhaitee);
      }

      return payload;
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        if (this.form.reservation_booking && (!this.form.date_chargement || !this.form.date_cutoff)) {
          this.error = 'Date de chargement et date de cut-off sont obligatoires.';
          this.saving = false;
          return;
        }
        const payload = this.buildPayload();
        let id = this.form.id;
        let saved = null;
        if (id) {
          const { data } = await axios.put(`/api/livraisons/${id}`, payload);
          saved = data;
        } else {
          const { data } = await axios.post('/api/livraisons', payload);
          saved = data;
          id = data.id;
          this.form.id = id;
        }

        if (this.form.reservation_booking) {
          await this.uploadConteneurCinScans(id, saved?.conteneurs || []);
        } else if (this.cinScanFile) {
          await this.uploadCinScan(id);
        }

        this.showModal = false;
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
    async uploadPiece(e) {
      const file = e.target.files?.[0];
      if (!file || !this.form.id) return;
      const fd = new FormData();
      fd.append('fichier', file);
      try {
        const { data } = await axios.post(`/api/livraisons/${this.form.id}/pieces`, fd);
        this.pieces.push(data);
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur upload';
      }
      e.target.value = '';
    },
  },
};
</script>
