<template>
  <div class="min-h-screen p-4 md:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Paramètres</h1>
        <p class="text-gray-600">Configurez les paramètres et données de référence du système</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <nav class="flex flex-wrap border-b border-gray-200">
          <button v-for="t in tabs" :key="t.key" type="button" @click="select(t.key)"
            :class="['px-4 py-3 text-sm font-medium whitespace-nowrap -mb-px',
              active === t.key ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700']">
            {{ t.label }}
          </button>
        </nav>
      </div>
      <Exportateurs v-if="active === 'exportateurs'" embedded />
      <Settings v-else :key="active" :fixed-type="active" />
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
    return { active: 'exportateurs' };
  },
  computed: {
    tabs() {
      return [{ key: 'exportateurs', label: 'Exportateurs' },
        ...Object.entries(PARAMETRE_TYPES).map(([key, label]) => ({ key, label }))];
    },
  },
  watch: { '$route.fullPath'() { this.fromRoute(); } },
  created() { this.fromRoute(); },
  methods: {
    fromRoute() {
      const q = this.$route.query.tab || (this.$route.hash || '').replace(/^#(param-)?/, '');
      if (q && this.tabs.some(t => t.key === q)) this.active = q;
    },
    select(key) {
      this.active = key;
      if (this.$route.query.tab !== key) this.$router.replace({ path: '/parametre', query: { tab: key } });
    },
  },
};
</script>
