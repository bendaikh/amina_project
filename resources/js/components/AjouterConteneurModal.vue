<template>
  <div v-if="show" class="app-modal-overlay">
    <div class="app-modal app-modal--lg" @click.stop>
      <div class="app-modal__header">
        <h2 class="app-modal__title">Ajouter un conteneur</h2>
        <button type="button" class="app-modal__close" @click="close">&times;</button>
      </div>
      <div class="app-modal__body space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div><label class="text-xs text-gray-500">N° conteneur</label><input v-model="f.numero_conteneur" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Type</label>
            <select v-model="f.type_conteneur" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
              <option v-for="t in TYPES" :key="t.key" :value="t.key">{{ t.label }}</option>
            </select></div>
          <div><label class="text-xs text-gray-500">Tare (kg)</label><input v-model="f.tare_conteneur" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">N° plomb</label><input v-model="f.numero_plomb" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Booking</label><input v-model="f.numero_booking" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Navire</label><input v-model="f.navire" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Port départ</label><input v-model="f.port_depart" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Port arrivée</label><input v-model="f.port_arrivee" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">ETD</label><input type="date" v-model="f.etd" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">ETA</label><input type="date" v-model="f.eta" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          <div><label class="text-xs text-gray-500">Statut</label>
            <select v-model="f.statut" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white">
              <option v-for="s in STATUTS" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select></div>
          <div><label class="text-xs text-gray-500">Date enlèvement</label><input type="date" v-model="f.date_enlevement" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
        </div>
        <p class="text-xs text-gray-500">Booking, navire, ports, ETD/ETA et statut sont partagés par tous les conteneurs de la livraison de cette commande.</p>

        <div class="bg-teal-50 border border-teal-100 text-sm text-gray-700 rounded-lg px-4 py-3">
          Ce conteneur de type <span class="font-semibold text-teal-700">{{ typeLabel }}</span> peut contenir
          <span class="font-semibold text-teal-700">{{ capacite }} palettes</span>. Les informations de la liste de colisage
          sont reprises automatiquement depuis la commande.
        </div>

        <section class="border border-gray-200 rounded-lg p-4">
          <h3 class="font-semibold text-gray-900">Articles à charger dans ce conteneur</h3>
          <p class="text-xs text-gray-500 mb-3">Cochez uniquement les articles à charger. Le code article et la désignation viennent directement de la commande. Les autres informations de la liste seront générées automatiquement.</p>
          <p v-if="!rows.length" class="text-sm text-gray-500">Aucune ligne dans cette commande.</p>
          <div v-for="r in rows" :key="r.id" class="grid grid-cols-12 gap-2 items-center py-3 border-t border-gray-100 first:border-t-0">
            <input type="checkbox" v-model="r.checked" :disabled="r.disponible <= 0" class="col-span-1 h-4 w-4" @change="onCheck(r)" />
            <div class="col-span-4"><p class="font-semibold text-sm text-gray-900">{{ r.code }}</p><p class="text-xs text-gray-500">{{ r.designation }}</p></div>
            <div class="col-span-2"><p class="text-xs text-gray-500">Disponible</p><p class="text-sm font-semibold">{{ r.disponible }} cartons</p></div>
            <div class="col-span-2"><p class="text-xs text-gray-500">Colis/palette</p><p class="text-sm font-semibold">{{ r.cpp || '—' }}</p></div>
            <div class="col-span-3"><input type="number" min="0" :max="r.disponible" v-model.number="r.cartons" :disabled="!r.checked" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm disabled:bg-gray-50" placeholder="0" /></div>
          </div>
        </section>

        <section class="border border-gray-200 rounded-lg p-4">
          <h3 class="font-semibold text-gray-900 mb-1">Résumé chargement</h3>
          <p class="text-sm" :class="overCapacity ? 'text-red-600 font-semibold' : 'text-gray-600'">
            <span class="font-semibold">{{ totalPalettes }} / {{ capacite }} palettes</span> utilisées · {{ selected.length }} article(s) sélectionné(s)
          </p>
          <p v-if="overCapacity" class="text-xs text-red-600 mt-1">Capacité du conteneur dépassée.</p>
        </section>

        <section class="border border-gray-200 rounded-lg p-4">
          <h3 class="font-semibold text-gray-900 mb-3">Transport routier</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><label class="text-xs text-gray-500">Transporteur</label><input v-model="f.transporteur" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
            <div><label class="text-xs text-gray-500">Camion</label><input v-model="f.matricule_camion" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
            <div><label class="text-xs text-gray-500">Chauffeur</label><input v-model="f.chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
            <div><label class="text-xs text-gray-500">CIN chauffeur</label><input v-model="f.cin_chauffeur" class="w-full border border-gray-300 rounded-lg px-3 py-2" /></div>
          </div>
        </section>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>
      <div class="app-modal__footer">
        <button type="button" @click="close" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white">Annuler</button>
        <button type="button" @click="save" :disabled="saving || invalid" class="px-4 py-2 bg-teal-700 text-white rounded-lg text-sm disabled:opacity-50">{{ saving ? 'Enregistrement…' : 'Enregistrer' }}</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export const CONTENEUR_TYPES = [
  { key: '20', label: "20'", capacite: 11 },
  { key: '40', label: "40'", capacite: 25 },
  { key: '40HC', label: "40' HC", capacite: 28 },
  { key: '45', label: "45' HC", capacite: 33 },
];
const KG = ['kg', 'kgs', 'kilo', 'kilogramme'];

export function colisCommandes(l) {
  const q = Number(l.quantite) || 0;
  const pne = Number(l.poids_net_egoutte) || 0;
  const u = String(l.unite || '').toLowerCase();
  if (q > 0 && pne > 0 && KG.includes(u)) return Math.round(q / pne);
  if (q > 0 && ![...KG, 'l', 'litre'].includes(u)) return Math.round(q);
  return Number(l.nombre_total_par_palette) || 0;
}

export default {
  name: 'AjouterConteneurModal',
  props: { show: Boolean, commande: { type: Object, default: null } },
  emits: ['close', 'saved'],
  data() {
    return {
      TYPES: CONTENEUR_TYPES,
      STATUTS: [
        { key: 'a_preparer', label: 'À préparer' }, { key: 'en_preparation', label: 'En préparation' },
        { key: 'pret', label: 'Prêt' }, { key: 'charge', label: 'Chargé' },
        { key: 'livre', label: 'Livré' }, { key: 'expedie', label: 'Expédié' },
      ],
      f: {}, rows: [], saving: false, error: '',
    };
  },
  computed: {
    capacite() { return (CONTENEUR_TYPES.find(t => t.key === this.f.type_conteneur) || {}).capacite || 28; },
    typeLabel() { return (CONTENEUR_TYPES.find(t => t.key === this.f.type_conteneur) || {}).label || ''; },
    selected() { return this.rows.filter(r => r.checked && Number(r.cartons) > 0); },
    totalPalettes() { return this.selected.reduce((s, r) => s + (r.cpp > 0 ? Math.ceil(Number(r.cartons) / r.cpp) : 0), 0); },
    overCapacity() { return this.totalPalettes > this.capacite; },
    invalid() { return this.overCapacity || this.rows.some(r => r.checked && Number(r.cartons) > r.disponible); },
  },
  watch: { show(v) { if (v) this.init(); } },
  methods: {
    init() {
      const c = this.commande || {};
      const liv = [...(c.livraisons || [])].sort((a, b) => b.id - a.id)[0] || {};
      const d = v => (v ? String(v).slice(0, 10) : '');
      this.error = '';
      this.f = {
        numero_conteneur: '', type_conteneur: '40HC', tare_conteneur: '0', numero_plomb: '', date_enlevement: '',
        numero_booking: liv.numero_booking || '', navire: liv.navire || '',
        port_depart: liv.port_depart || 'Casablanca', port_arrivee: liv.port_arrivee || c.destination || '',
        etd: d(liv.etd), eta: d(liv.eta), statut: liv.statut || 'a_preparer',
        transporteur: '', matricule_camion: '', chauffeur: '', cin_chauffeur: '',
      };
      const loaded = {};
      for (const l of c.livraisons || []) for (const ct of l.conteneurs || []) for (const a of ct.articles || []) {
        loaded[a.commande_ligne_id] = (loaded[a.commande_ligne_id] || 0) + Number(a.cartons || 0);
      }
      this.rows = (c.lignes || []).map(l => ({
        id: l.id,
        code: l.article?.code_article || '—',
        designation: [l.designation, l.calibre].filter(Boolean).join(' '),
        cpp: Number(l.colis_par_palette) || 0,
        disponible: Math.max(0, colisCommandes(l) - (loaded[l.id] || 0)),
        checked: false, cartons: null,
      }));
    },
    onCheck(r) { if (r.checked && !r.cartons) r.cartons = r.disponible; if (!r.checked) r.cartons = null; },
    close() { this.$emit('close'); },
    async save() {
      this.saving = true; this.error = '';
      try {
        await axios.post(`/api/commandes/${this.commande.id}/conteneurs`, {
          ...this.f,
          articles: this.selected.map(r => ({ commande_ligne_id: r.id, cartons: Number(r.cartons) })),
        });
        this.$emit('saved');
      } catch (e) {
        const errs = e.response?.data?.errors;
        this.error = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message || 'Erreur enregistrement conteneur');
      } finally { this.saving = false; }
    },
  },
};
</script>
