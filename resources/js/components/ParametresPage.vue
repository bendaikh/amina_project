<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Paramètres</h1>
        <p class="text-gray-600">Tous les paramètres de l'application sur une seule page.</p>
      </div>
      <nav class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-8 flex flex-wrap gap-2">
        <a v-for="s in sections" :key="s.id" :href="'#' + s.id" @click.prevent="goTo(s.id)"
           class="px-3 py-1.5 rounded-lg text-sm bg-gray-50 text-gray-700 hover:bg-teal-50 hover:text-teal-700">{{ s.label }}</a>
      </nav>
      <section id="param-exportateurs" class="mb-10 scroll-mt-4">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Exportateurs</h2>
        <Exportateurs embedded />
      </section>
      <section v-for="(label, type) in types" :key="type" :id="'param-' + type" class="mb-10 scroll-mt-4">
        <Settings :fixed-type="type" />
      </section>
    </div>
  </div>
</template>

<script>
import Settings, { PARAMETRE_TYPES } from './Settings.vue';
import Exportateurs from './Exportateurs.vue';

export default {
  name: 'ParametresPage',
  components: { Settings, Exportateurs },
  data() {
    return { types: PARAMETRE_TYPES };
  },
  computed: {
    sections() {
      return [{ id: 'param-exportateurs', label: 'Exportateurs' },
        ...Object.entries(this.types).map(([t, label]) => ({ id: 'param-' + t, label }))];
    },
  },
  watch: { '$route.hash'(h) { if (h) this.goTo(h.slice(1)); } },
  mounted() {
    if (this.$route.hash) setTimeout(() => this.goTo(this.$route.hash.slice(1)), 400);
  },
  methods: {
    goTo(id) {
      const el = document.getElementById(id);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
  },
};
</script>
