<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Bons de livraison</h1>
          <p class="text-gray-600 text-sm mt-1">
            Transformation d’une commande en BL — N°, client, lignes colis / poids
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Transformer une commande
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-3">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N° BL, client, réf. client, commande…"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
        <select v-model="statut" @change="load" class="border border-gray-300 rounded-lg px-3 py-2">
          <option value="">Tous les statuts</option>
          <option value="brouillon">Brouillon</option>
          <option value="valide">Validé</option>
          <option value="annule">Annulé</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">N° BL</th>
              <th class="py-3 px-4">Date création</th>
              <th class="py-3 px-4">Client</th>
              <th class="py-3 px-4">Réf. client</th>
              <th class="py-3 px-4">Date / heure livraison</th>
              <th class="py-3 px-4">Total colis</th>
              <th class="py-3 px-4">Total poids net</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucun bon de livraison</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">{{ formatDate(item.date_creation) }}</td>
              <td class="py-3 px-4">{{ item.client?.nom || '—' }}</td>
              <td class="py-3 px-4">{{ item.reference_client || '—' }}</td>
              <td class="py-3 px-4">{{ formatDateTime(item.date_heure_livraison) }}</td>
              <td class="py-3 px-4">{{ formatQty(item.total_colis) }}</td>
              <td class="py-3 px-4">{{ formatQty(item.total_poids_net) }}</td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <button type="button" title="Actions" @click.stop="toggleMenu(item, $event)" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <teleport to="body">
        <div v-if="menu.item" class="fixed inset-0 z-[60]" @click="closeMenu" @wheel="closeMenu"></div>
        <div v-if="menu.item" class="fixed z-[61] w-64 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm text-gray-700" :style="{ top: menu.top + 'px', left: menu.left + 'px' }">
          <button type="button" @click="runMenu((it) => openDetail(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-teal-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            <span>Ouvrir</span>
          </button>
          <button type="button" :disabled="convertingId === menu.item.id" @click="runMenu((it) => convertirEnFacture(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-indigo-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span>Convertir en facture</span>
          </button>
          <button type="button" :disabled="downloadingId === menu.item.id" @click="runMenu((it) => downloadPdf(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-sky-700">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            <span>Exporter en PDF</span>
          </button>
          <div class="border-t border-gray-100 my-1"></div>
          <button type="button" @click="runMenu((it) => openDelete(it))" class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-50 disabled:opacity-50 text-red-600">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            <span>Supprimer</span>
          </button>
        </div>
      </teleport>
      <!-- Suppression -->
      <div v-if="deleteTarget" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Supprimer le bon de livraison {{ deleteTarget.numero }}</h2>
            <button type="button" class="app-modal__close" @click="deleteTarget=null" :disabled="deleting">&times;</button>
          </div>
          <div class="app-modal__body space-y-3">
            <p class="text-sm text-gray-600">Choisissez le type de suppression :</p>
            <button type="button" :disabled="deleting" @click="confirmDelete('remettre_commande')" class="w-full text-left border border-amber-300 rounded-lg p-3 hover:bg-amber-50 disabled:opacity-50">
              <div class="font-medium text-amber-700">Supprimer et remettre en commande</div>
              <div class="text-xs text-gray-500 mt-1">Le bon de livraison est supprimé et la commande d'origine revient dans la liste des commandes (statut d'avant transformation).</div>
            </button>
            <button type="button" :disabled="deleting" @click="confirmDelete('definitif')" class="w-full text-left border border-red-300 rounded-lg p-3 hover:bg-red-50 disabled:opacity-50">
              <div class="font-medium text-red-700">Supprimer définitivement</div>
              <div class="text-xs text-gray-500 mt-1">Le bon de livraison ET sa commande d'origine sont supprimés de la base de données.</div>
            </button>
            <p v-if="deleteError" class="text-red-600 text-sm">{{ deleteError }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="deleteTarget=null" :disabled="deleting" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
          </div>
        </div>
      </div>

      <p v-if="actionError" class="mt-3 text-sm text-red-600">{{ actionError }}</p>

      <!-- Create from commande -->
      <div v-if="showCreate" class="app-modal-overlay">
        <div class="app-modal app-modal--md" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">Transformer une commande en BL</h2>
            <button type="button" class="app-modal__close" @click="showCreate=false">&times;</button>
          </div>
          <div class="app-modal__body space-y-4">
            <div>
              <label class="text-sm text-gray-600">Commande</label>
              <select v-model="createForm.commande_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                <option :value="null">Sélectionner…</option>
                <option v-for="c in commandesDispo" :key="c.id" :value="c.id">
                  {{ c.numero }} — {{ c.client?.nom || 'Sans client' }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Seules les commandes non encore transformées sont listées.</p>
            </div>
            <div>
              <label class="text-sm text-gray-600">Date et heure de livraison</label>
              <input type="datetime-local" v-model="createForm.date_heure_livraison" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
            </div>
            <div>
              <label class="text-sm text-gray-600">Informations additionnelles</label>
              <textarea v-model="createForm.informations_additionnelles" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
            </div>
            <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
          </div>
          <div class="app-modal__footer">
            <button type="button" @click="showCreate=false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Annuler</button>
            <button type="button" @click="createFromCommande" :disabled="saving || !createForm.commande_id" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
              {{ saving ? 'Création…' : 'Créer le BL' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Detail / Edit modal -->
      <div v-if="showModal && form" class="app-modal-overlay">
        <div class="app-modal app-modal--xl" @click.stop>
          <div class="app-modal__header no-print">
            <div>
              <h2 class="app-modal__title">Bon de livraison {{ form.numero }}</h2>
              <p class="app-modal__subtitle" v-if="form.commande">
                Issu de la commande {{ form.commande.numero }}
              </p>
            </div>
            <button type="button" class="app-modal__close" @click="closeDetail">&times;</button>
          </div>

          <div class="app-modal__body">
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">N° du BL</label>
                <input :value="form.numero" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 font-semibold text-teal-700" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date de création</label>
                <input type="date" v-model="form.date_creation" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Client</label>
                <input :value="form.client?.nom || '—'" readonly class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Référence client</label>
                <input v-model="form.reference_client" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date et heure de livraison</label>
                <input type="datetime-local" v-model="form.date_heure_livraison" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
                  <option value="brouillon">Brouillon</option>
                  <option value="valide">Validé</option>
                  <option value="annule">Annulé</option>
                </select>
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Informations additionnelles</label>
                <textarea v-model="form.informations_additionnelles" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <h3 class="font-semibold text-gray-900 mb-3">Lignes du bon de livraison</h3>
            <div class="overflow-x-auto border border-gray-100 rounded-lg mb-4">
              <table class="w-full text-sm min-w-max">
                <thead class="bg-gray-50 text-left text-gray-500">
                  <tr>
                    <th class="py-2 px-3 whitespace-nowrap">Réf. article</th>
                    <th class="py-2 px-3 whitespace-nowrap">Article / Désignation</th>
                    <th class="py-2 px-3 whitespace-nowrap">Calibre</th>
                    <th class="py-2 px-3 whitespace-nowrap">Emballage</th>
                    <th class="py-2 px-3 whitespace-nowrap">N° de lot</th>
                    <th class="py-2 px-3 whitespace-nowrap">Total colis</th>
                    <th class="py-2 px-3 whitespace-nowrap">Poids net ég. unitaire</th>
                    <th class="py-2 px-3 whitespace-nowrap">Total poids net</th>
                    <th class="py-2 px-3 whitespace-nowrap">Total poids total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!form.lignes?.length">
                    <td colspan="9" class="py-6 text-center text-gray-400">Aucune ligne</td>
                  </tr>
                  <tr v-for="(ligne, idx) in form.lignes" :key="idx" class="border-t border-gray-50">
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.ref_article || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.designation || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.calibre || '—' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ ligne.emballage || '—' }}</td>
                    <td class="py-2 px-3">
                      <input v-model="ligne.numero_lot" class="border border-gray-300 rounded px-2 py-1 w-28 text-xs" />
                    </td>
                    <td class="py-2 px-3">
                      <input type="number" step="0.001" v-model.number="ligne.total_colis" class="border border-gray-300 rounded px-2 py-1 w-20 text-xs" />
                    </td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ formatQty(ligne.poids_net_eg_unitaire) }}</td>
                    <td class="py-2 px-3">
                      <input type="number" step="0.001" v-model.number="ligne.total_poids_net" class="border border-gray-300 rounded px-2 py-1 w-24 text-xs" />
                    </td>
                    <td class="py-2 px-3">
                      <input type="number" step="0.001" v-model.number="ligne.total_poids_total" class="border border-gray-300 rounded px-2 py-1 w-24 text-xs" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex flex-wrap gap-6 text-sm text-gray-800 justify-end border-t border-gray-100 pt-4">
              <span>Total colis : <strong class="text-teal-700">{{ formatQty(sumColis) }}</strong></span>
              <span>Total poids net : <strong class="text-teal-700">{{ formatQty(sumPoidsNet) }}</strong></span>
            </div>

            <p v-if="error" class="text-red-600 text-sm mt-3">{{ error }}</p>
            <p v-if="message" class="text-teal-600 text-sm mt-3">{{ message }}</p>
          </div>

          <div class="app-modal__footer app-modal__footer--between no-print">
            <button type="button" @click="printBl" class="px-4 py-2 border border-gray-300 rounded-lg bg-white inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Imprimer
            </button>
            <div class="flex gap-3">
              <button type="button" @click="closeDetail" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">Fermer</button>
              <button type="button" @click="save" :disabled="saving" class="px-5 py-2 bg-teal-600 text-white rounded-lg disabled:opacity-50">
                {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
              </button>
            </div>
          </div>

          <!-- Print layout -->
          <div class="print-only">
            <div class="print-header">
              <h1>Bon de livraison {{ form.numero }}</h1>
              <div class="print-meta">
                <div><strong>Date de création :</strong> {{ form.date_creation || '—' }}</div>
                <div><strong>Client :</strong> {{ form.client?.nom || '—' }}</div>
                <div><strong>Réf. client :</strong> {{ form.reference_client || '—' }}</div>
                <div><strong>Date / heure livraison :</strong> {{ formatDateTime(form.date_heure_livraison) }}</div>
              </div>
              <p v-if="form.informations_additionnelles"><strong>Informations additionnelles :</strong> {{ form.informations_additionnelles }}</p>
            </div>
            <table class="print-table">
              <thead>
                <tr>
                  <th>Réf. article</th>
                  <th>Article / Désignation</th>
                  <th>Calibre</th>
                  <th>Emballage</th>
                  <th>N° de lot</th>
                  <th>Total colis</th>
                  <th>Poids net ég. unitaire</th>
                  <th>Total poids net</th>
                  <th>Total poids total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ligne, idx) in form.lignes" :key="'p-'+idx">
                  <td>{{ ligne.ref_article || '—' }}</td>
                  <td>{{ ligne.designation || '—' }}</td>
                  <td>{{ ligne.calibre || '—' }}</td>
                  <td>{{ ligne.emballage || '—' }}</td>
                  <td>{{ ligne.numero_lot || '—' }}</td>
                  <td>{{ formatQty(ligne.total_colis) }}</td>
                  <td>{{ formatQty(ligne.poids_net_eg_unitaire) }}</td>
                  <td>{{ formatQty(ligne.total_poids_net) }}</td>
                  <td>{{ formatQty(ligne.total_poids_total) }}</td>
                </tr>
              </tbody>
            </table>
            <div class="print-totals">
              <div>Total colis : <strong>{{ formatQty(sumColis) }}</strong></div>
              <div>Total poids net : <strong>{{ formatQty(sumPoidsNet) }}</strong></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BonsLivraison',
  data() {
    return {
      menu: { item: null, top: 0, left: 0 },
      deleteTarget: null,
      deleting: false,
      deleteError: '',
      items: [],
      commandesDispo: [],
      loading: false,
      saving: false,
      convertingId: null,
      downloadingId: null,
      actionError: '',
      search: '',
      statut: '',
      timer: null,
      showCreate: false,
      showModal: false,
      form: null,
      createForm: {
        commande_id: null,
        date_heure_livraison: '',
        informations_additionnelles: '',
      },
      error: '',
      message: '',
    };
  },
  computed: {
    sumColis() {
      return (this.form?.lignes || []).reduce((s, l) => s + (Number(l.total_colis) || 0), 0);
    },
    sumPoidsNet() {
      return (this.form?.lignes || []).reduce((s, l) => s + (Number(l.total_poids_net) || 0), 0);
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    toggleMenu(item, event) {
      if (this.menu.item && this.menu.item.id === item.id) { this.closeMenu(); return; }
      const r = event.currentTarget.getBoundingClientRect();
      const h = 200;
      let top = r.bottom + 4;
      if (top + h > window.innerHeight) top = Math.max(8, r.top - h - 4);
      this.menu = { item, top, left: Math.max(8, r.right - 256) };
    },
    closeMenu() {
      this.menu = { item: null, top: 0, left: 0 };
    },
    runMenu(fn) {
      const it = this.menu.item;
      this.closeMenu();
      if (it) fn(it);
    },
    openDelete(item) {
      this.deleteError = '';
      this.deleteTarget = item;
    },
    async confirmDelete(mode) {
      const item = this.deleteTarget;
      if (!item) return;
      const msg = mode === 'definitif'
        ? `Supprimer DÉFINITIVEMENT ${item.numero} ? Le bon de livraison et sa commande d'origine seront supprimés. Cette action est irréversible.`
        : `Supprimer ${item.numero} et remettre le document d'origine ?`;
      if (!confirm(msg)) return;
      this.deleting = true;
      this.deleteError = '';
      try {
        const { data } = await axios.delete(`/api/bons-livraison/${item.id}`, { params: { mode } });
        this.deleteTarget = null;
        await this.load();
        if (data?.message) alert(data.message);
      } catch (e) {
        this.deleteError = e.response?.data?.message || 'Erreur lors de la suppression';
      } finally {
        this.deleting = false;
      }
    },
    labelStatut(s) {
      return ({ brouillon: 'Brouillon', valide: 'Validé', annule: 'Annulé' })[s] || s;
    },
    badgeClass(s) {
      return ({
        brouillon: 'bg-slate-100 text-slate-700',
        valide: 'bg-green-100 text-green-700',
        annule: 'bg-red-100 text-red-700',
      })[s] || 'bg-gray-100 text-gray-700';
    },
    formatDate(d) {
      if (!d) return '—';
      return String(d).slice(0, 10);
    },
    formatDateTime(d) {
      if (!d) return '—';
      const s = String(d).replace('T', ' ').slice(0, 16);
      return s;
    },
    formatQty(v) {
      if (v === null || v === undefined || v === '') return '—';
      return Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 3 });
    },
    toDatetimeLocal(d) {
      if (!d) return '';
      const s = String(d).replace(' ', 'T');
      return s.slice(0, 16);
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/bons-livraison', {
          params: { search: this.search, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
      } finally {
        this.loading = false;
      }
    },
    async openCreate() {
      this.error = '';
      this.createForm = {
        commande_id: null,
        date_heure_livraison: '',
        informations_additionnelles: '',
      };
      try {
        const { data } = await axios.get('/api/commandes', { params: { per_page: 200 } });
        this.commandesDispo = data.data || data;
      } catch {
        this.commandesDispo = [];
      }
      this.showCreate = true;
    },
    async createFromCommande() {
      if (!this.createForm.commande_id) return;
      this.saving = true;
      this.error = '';
      try {
        const payload = {
          date_heure_livraison: this.createForm.date_heure_livraison || null,
          informations_additionnelles: this.createForm.informations_additionnelles || null,
        };
        const { data } = await axios.post(
          `/api/bons-livraison/from-commande/${this.createForm.commande_id}`,
          payload,
        );
        this.showCreate = false;
        await this.load();
        this.openDetail(data);
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur lors de la création du BL';
      } finally {
        this.saving = false;
      }
    },
    async openDetail(item) {
      this.error = '';
      this.message = '';
      try {
        const { data } = await axios.get(`/api/bons-livraison/${item.id}`);
        this.form = {
          ...data,
          date_creation: data.date_creation?.slice?.(0, 10) || data.date_creation,
          date_heure_livraison: this.toDatetimeLocal(data.date_heure_livraison),
          lignes: (data.lignes || []).map(l => ({ ...l })),
        };
        this.showModal = true;
      } catch (e) {
        this.error = e.response?.data?.message || 'Impossible de charger le BL';
      }
    },
    closeDetail() {
      this.showModal = false;
      this.form = null;
    },
    async save() {
      if (!this.form?.id) return;
      this.saving = true;
      this.error = '';
      this.message = '';
      try {
        const payload = {
          date_creation: this.form.date_creation,
          reference_client: this.form.reference_client,
          date_heure_livraison: this.form.date_heure_livraison || null,
          informations_additionnelles: this.form.informations_additionnelles,
          statut: this.form.statut,
          lignes: this.form.lignes,
        };
        const { data } = await axios.put(`/api/bons-livraison/${this.form.id}`, payload);
        this.form = {
          ...data,
          date_creation: data.date_creation?.slice?.(0, 10) || data.date_creation,
          date_heure_livraison: this.toDatetimeLocal(data.date_heure_livraison),
          lignes: (data.lignes || []).map(l => ({ ...l })),
        };
        this.message = 'Bon de livraison enregistré';
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
    printBl() {
      window.print();
    },
    async downloadPdf(item) {
      this.downloadingId = item.id;
      this.actionError = '';
      try {
        const filename = `Bon_de_livraison_${item.numero || item.id}.pdf`.replace(/[^\w.\-]+/g, '_');
        const { data } = await axios.get(`/api/bons-livraison/${item.id}/pdf`, {
          responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (e) {
        this.actionError = await this.readError(e, 'Impossible d\'exporter le bon de livraison');
      } finally {
        this.downloadingId = null;
      }
    },
    async readError(e, fallback) {
      const data = e.response?.data;
      if (data instanceof Blob) {
        try {
          const json = JSON.parse(await data.text());
          return json.message || fallback;
        } catch {
          return fallback;
        }
      }
      return data?.message || fallback;
    },
    async convertirEnFacture(item) {
      if (item.facture_locale?.id) {
        this.$router.push({ path: '/ventes/locales/facturation', query: { open: item.facture_locale.id } });
        return;
      }
      if (!confirm(`Convertir le bon de livraison ${item.numero} en facture locale ?`)) {
        return;
      }
      this.convertingId = item.id;
      this.error = '';
      try {
        const { data } = await axios.post(`/api/bons-livraison/${item.id}/to-facture`);
        this.$router.push({ path: '/ventes/locales/facturation', query: { open: data.id } });
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur conversion en facture';
        alert(this.error);
      } finally {
        this.convertingId = null;
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
    margin: 0 0 12px;
    font-size: 22px;
  }

  .print-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 16px;
    margin-bottom: 14px;
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
