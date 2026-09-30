<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Production</h1>
          <p class="text-gray-600 text-sm mt-1">
            À planifier → Planifiée → En cours → Terminée · Effets stock matières / PF / déchets
          </p>
        </div>
        <button @click="openCreate" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
          + Nouvel ordre
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
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-amber-500">
          <p class="text-xs text-gray-500">À planifier</p>
          <p class="text-2xl font-bold">{{ countBy('a_planifier') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-blue-500">
          <p class="text-xs text-gray-500">En cours</p>
          <p class="text-2xl font-bold">{{ countBy('en_cours') + countBy('planifiee') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-emerald-500">
          <p class="text-xs text-gray-500">Terminées</p>
          <p class="text-2xl font-bold">{{ countBy('terminee') }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4 border-l-4 border-l-red-500">
          <p class="text-xs text-gray-500">Bloquées</p>
          <p class="text-2xl font-bold">{{ countBy('bloquee') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
        <input
          v-model="search"
          @input="debouncedLoad"
          type="text"
          placeholder="Rechercher N° OF, commande, article, responsable…"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"
        />
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 text-left text-sm text-gray-600">
              <th class="py-3 px-4">Ordre</th>
              <th class="py-3 px-4">Commande</th>
              <th class="py-3 px-4">Article</th>
              <th class="py-3 px-4">Qté à prod.</th>
              <th class="py-3 px-4">Produite</th>
              <th class="py-3 px-4">Restante</th>
              <th class="py-3 px-4">Responsable</th>
              <th class="py-3 px-4">Statut</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="9" class="py-10 text-center text-gray-500">Chargement…</td></tr>
            <tr v-else-if="!items.length"><td colspan="9" class="py-10 text-center text-gray-500">Aucun ordre de production</td></tr>
            <tr v-for="item in items" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-3 px-4 font-semibold text-teal-700">{{ item.numero }}</td>
              <td class="py-3 px-4">
                <router-link v-if="item.commande_id" :to="`/ventes/commandes/${item.commande_id}`" class="text-teal-600 hover:underline">
                  {{ item.commande?.numero || '—' }}
                </router-link>
                <span v-else>—</span>
              </td>
              <td class="py-3 px-4">{{ item.designation || item.article?.designation || '—' }}</td>
              <td class="py-3 px-4">{{ item.quantite_a_produire }}</td>
              <td class="py-3 px-4">{{ item.quantite_produite }}</td>
              <td class="py-3 px-4">{{ item.quantite_restante }}</td>
              <td class="py-3 px-4">{{ item.responsable || '—' }}</td>
              <td class="py-3 px-4">
                <span :class="badgeClass(item.statut)" class="px-2 py-1 rounded text-xs font-medium">{{ labelStatut(item.statut) }}</span>
              </td>
              <td class="py-3 px-4 text-right">
                <button @click="openEdit(item)" class="text-teal-600 hover:underline font-medium">Ouvrir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="app-modal-overlay" @click.self="showModal=false">
        <div class="app-modal app-modal--lg" @click.stop>
          <div class="app-modal__header">
            <h2 class="app-modal__title">{{ form.id ? 'Ordre de production' : 'Nouvel ordre de production' }}</h2>
            <button type="button" class="app-modal__close" @click="showModal=false">&times;</button>
          </div>
          <div class="app-modal__body">
            <div class="achat-header-grid mb-5">
              <div>
                <label class="text-sm text-gray-600">Commande</label>
                <select v-model="form.commande_id" @change="onCommandeChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="c in commandes" :key="c.id" :value="c.id">{{ c.numero }} — {{ c.client?.nom || '' }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Ligne / Article</label>
                <select v-model="form.commande_ligne_id" @change="onLigneChange" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="l in lignesCommande" :key="l.id" :value="l.id">
                    {{ l.designation || l.article?.designation }} ({{ l.quantite_a_produire || l.quantite }})
                  </option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Article (manuel)</label>
                <select v-model="form.article_id" @change="fillArticle" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option :value="null">—</option>
                  <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code_article }} — {{ a.designation }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Désignation</label>
                <input v-model="form.designation" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Qté commandée</label>
                <input type="number" step="0.001" v-model.number="form.quantite_commandee" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Qté à produire</label>
                <input type="number" step="0.001" v-model.number="form.quantite_a_produire" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Qté produite</label>
                <input type="number" step="0.001" v-model.number="form.quantite_produite" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Statut</label>
                <select v-model="form.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option v-for="s in statutSteps" :key="s.key" :value="s.key">{{ s.label }}</option>
                </select>
              </div>
              <div>
                <label class="text-sm text-gray-600">Date planifiée</label>
                <input type="date" v-model="form.date_planifiee" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date début</label>
                <input type="date" v-model="form.date_debut" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Date fin</label>
                <input type="date" v-model="form.date_fin" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Responsable</label>
                <input v-model="form.responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div>
                <label class="text-sm text-gray-600">Équipe</label>
                <input v-model="form.equipe" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div class="achat-span-full">
                <label class="text-sm text-gray-600">Observation</label>
                <textarea v-model="form.observations" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
              <p class="text-sm font-semibold text-gray-700 mb-3">Effets possibles</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.effet_sortie_matieres" /> Sortie matières / emballages</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.effet_entree_pf" /> Entrée produits finis</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.effet_sous_produits" /> Sous-produits</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.effet_dechets" /> Déchets</label>
              </div>
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
  { key: 'a_planifier', label: 'À planifier' },
  { key: 'planifiee', label: 'Planifiée' },
  { key: 'en_cours', label: 'En cours' },
  { key: 'partiellement_terminee', label: 'Partiellement terminée' },
  { key: 'terminee', label: 'Terminée' },
  { key: 'bloquee', label: 'Bloquée' },
  { key: 'annulee', label: 'Annulée' },
];

export default {
  name: 'VentesProduction',
  data() {
    return {
      items: [],
      allForCounts: [],
      commandes: [],
      articles: [],
      lignesCommande: [],
      loading: false,
      saving: false,
      showModal: false,
      search: '',
      statut: '',
      error: '',
      timer: null,
      statutSteps: STATUTS,
      form: this.emptyForm(),
    };
  },
  mounted() {
    this.load();
    this.loadLookups();
  },
  methods: {
    emptyForm() {
      return {
        id: null,
        commande_id: null,
        commande_ligne_id: null,
        article_id: null,
        designation: '',
        quantite_commandee: 0,
        quantite_a_produire: 0,
        quantite_produite: 0,
        date_planifiee: '',
        date_debut: '',
        date_fin: '',
        responsable: '',
        equipe: '',
        statut: 'a_planifier',
        observations: '',
        effet_sortie_matieres: false,
        effet_entree_pf: false,
        effet_sous_produits: false,
        effet_dechets: false,
      };
    },
    countBy(s) {
      return this.allForCounts.filter(i => i.statut === s).length;
    },
    labelStatut(s) {
      return STATUTS.find(x => x.key === s)?.label || s;
    },
    badgeClass(s) {
      return ({
        a_planifier: 'bg-amber-100 text-amber-700',
        planifiee: 'bg-blue-100 text-blue-700',
        en_cours: 'bg-indigo-100 text-indigo-700',
        partiellement_terminee: 'bg-violet-100 text-violet-700',
        terminee: 'bg-green-100 text-green-700',
        bloquee: 'bg-red-100 text-red-700',
        annulee: 'bg-slate-100 text-slate-600',
      })[s] || 'bg-gray-100 text-gray-700';
    },
    debouncedLoad() {
      clearTimeout(this.timer);
      this.timer = setTimeout(this.load, 300);
    },
    async load() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/productions', {
          params: { search: this.search, statut: this.statut, per_page: 50 },
        });
        this.items = data.data || data;
        if (!this.statut && !this.search) {
          this.allForCounts = this.items;
        } else {
          const all = await axios.get('/api/productions', { params: { per_page: 200 } });
          this.allForCounts = all.data.data || all.data;
        }
      } finally {
        this.loading = false;
      }
    },
    async loadLookups() {
      const [c, a] = await Promise.all([
        axios.get('/api/commandes', { params: { per_page: 200 } }),
        axios.get('/api/articles', { params: { per_page: 500 } }),
      ]);
      this.commandes = c.data.data || c.data;
      this.articles = a.data.data || a.data;
    },
    openCreate() {
      this.form = this.emptyForm();
      this.lignesCommande = [];
      this.error = '';
      this.showModal = true;
    },
    openEdit(item) {
      this.form = {
        id: item.id,
        commande_id: item.commande_id,
        commande_ligne_id: item.commande_ligne_id,
        article_id: item.article_id,
        designation: item.designation || '',
        quantite_commandee: Number(item.quantite_commandee || 0),
        quantite_a_produire: Number(item.quantite_a_produire || 0),
        quantite_produite: Number(item.quantite_produite || 0),
        date_planifiee: item.date_planifiee?.slice?.(0, 10) || '',
        date_debut: item.date_debut?.slice?.(0, 10) || '',
        date_fin: item.date_fin?.slice?.(0, 10) || '',
        responsable: item.responsable || '',
        equipe: item.equipe || '',
        statut: item.statut,
        observations: item.observations || '',
        effet_sortie_matieres: !!item.effet_sortie_matieres,
        effet_entree_pf: !!item.effet_entree_pf,
        effet_sous_produits: !!item.effet_sous_produits,
        effet_dechets: !!item.effet_dechets,
      };
      this.error = '';
      this.showModal = true;
      if (item.commande_id) this.loadLignes(item.commande_id);
    },
    async onCommandeChange() {
      this.form.commande_ligne_id = null;
      await this.loadLignes(this.form.commande_id);
    },
    async loadLignes(commandeId) {
      if (!commandeId) {
        this.lignesCommande = [];
        return;
      }
      const { data } = await axios.get(`/api/commandes/${commandeId}`);
      this.lignesCommande = data.lignes || [];
    },
    onLigneChange() {
      const l = this.lignesCommande.find(x => x.id === this.form.commande_ligne_id);
      if (!l) return;
      this.form.article_id = l.article_id;
      this.form.designation = l.designation || l.article?.designation || '';
      this.form.quantite_commandee = Number(l.quantite || 0);
      this.form.quantite_a_produire = Number(l.quantite_a_produire || l.quantite || 0);
    },
    fillArticle() {
      const a = this.articles.find(x => x.id === this.form.article_id);
      if (a) this.form.designation = a.designation || '';
    },
    async save() {
      this.saving = true;
      this.error = '';
      try {
        if (this.form.id) {
          await axios.put(`/api/productions/${this.form.id}`, this.form);
        } else {
          await axios.post('/api/productions', this.form);
        }
        this.showModal = false;
        await this.load();
      } catch (e) {
        this.error = e.response?.data?.message || 'Erreur enregistrement';
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
