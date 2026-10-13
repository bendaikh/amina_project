<template>
  <div ref="root" class="relative">
    <input
      ref="input"
      type="text"
      :class="inputClass"
      :placeholder="open ? 'Rechercher…' : (selected ? '' : '—')"
      :value="open ? query : label(selected)"
      :title="selected ? label(selected) : ''"
      autocomplete="off"
      @focus="openList"
      @click="openList"
      @input="onInput"
      @keydown.down.prevent="move(1)"
      @keydown.up.prevent="move(-1)"
      @keydown.enter.prevent="pickActive"
      @keydown.esc.prevent="close"
      @keydown.tab="close"
    />
    <Teleport to="body">
      <ul
        v-if="open"
        ref="list"
        class="fixed z-[9999] bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-auto text-sm py-1"
        :style="listStyle"
        @mousedown.prevent
      >
        <li class="px-3 py-1.5 cursor-pointer text-gray-400" :class="{ 'bg-teal-50': active === -1 }" @click="pick(null)">—</li>
        <li
          v-for="(a, i) in filtered"
          :key="a.id"
          :data-idx="i"
          class="px-3 py-1.5 cursor-pointer whitespace-nowrap"
          :class="[i === active ? 'bg-teal-600 text-white' : 'hover:bg-teal-50', a.id === modelValue && i !== active ? 'font-semibold' : '']"
          @mouseenter="active = i"
          @click="pick(a)"
        >
          <span class="font-medium">{{ a.code_article }}</span>
          <span v-if="a.designation" :class="i === active ? 'text-teal-50' : 'text-gray-500'"> — {{ a.designation }}</span>
        </li>
        <li v-if="!filtered.length" class="px-3 py-2 text-gray-400 italic">Aucun article</li>
      </ul>
    </Teleport>
  </div>
</template>

<script>
const norm = (s) => String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

export default {
  name: 'ArticleSelect',
  props: {
    modelValue: { default: null },
    articles: { type: Array, default: () => [] },
    inputClass: { type: String, default: 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm' },
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return { open: false, query: '', active: -1, listStyle: {} };
  },
  computed: {
    selected() {
      return this.articles.find((a) => a.id === this.modelValue) || null;
    },
    filtered() {
      const terms = norm(this.query).split(/\s+/).filter(Boolean);
      if (!terms.length) return this.articles;
      return this.articles.filter((a) => {
        const hay = norm(`${a.code_article} ${a.designation || ''} ${a.name || ''}`);
        return terms.every((t) => hay.includes(t));
      });
    },
  },
  methods: {
    label(a) {
      if (!a) return '';
      return a.designation ? `${a.code_article} — ${a.designation}` : a.code_article;
    },
    position() {
      const r = this.$refs.input?.getBoundingClientRect();
      if (!r) return;
      const below = window.innerHeight - r.bottom;
      const style = { left: `${r.left}px`, minWidth: `${Math.max(r.width, 260)}px`, maxWidth: '90vw' };
      if (below < 270 && r.top > below) style.bottom = `${window.innerHeight - r.top + 2}px`;
      else style.top = `${r.bottom + 2}px`;
      this.listStyle = style;
    },
    openList() {
      if (this.open) return;
      this.query = '';
      this.open = true;
      this.active = Math.max(-1, this.filtered.findIndex((a) => a.id === this.modelValue));
      this.position();
      this.$nextTick(() => { this.$refs.input?.select(); this.scrollActive(); });
    },
    close() {
      this.open = false;
      this.query = '';
    },
    onInput(e) {
      this.query = e.target.value;
      if (!this.open) { this.open = true; this.position(); }
      this.active = this.filtered.length ? 0 : -1;
    },
    move(d) {
      if (!this.open) return this.openList();
      const n = this.filtered.length;
      if (!n) return;
      this.active = Math.min(n - 1, Math.max(-1, this.active + d));
      this.$nextTick(this.scrollActive);
    },
    scrollActive() {
      const el = this.$refs.list?.querySelector(`[data-idx="${this.active}"]`);
      el?.scrollIntoView({ block: 'nearest' });
    },
    pickActive() {
      if (!this.open) return this.openList();
      this.pick(this.active >= 0 ? this.filtered[this.active] : null);
    },
    pick(a) {
      const id = a ? a.id : null;
      this.close();
      this.$refs.input?.blur();
      if (id !== this.modelValue) {
        this.$emit('update:modelValue', id);
        this.$emit('change', id);
      }
    },
    onDocDown(e) {
      if (!this.open) return;
      if (this.$refs.root?.contains(e.target) || this.$refs.list?.contains(e.target)) return;
      this.close();
    },
    onReflow() { if (this.open) this.position(); },
  },
  mounted() {
    document.addEventListener('mousedown', this.onDocDown, true);
    window.addEventListener('resize', this.onReflow);
    window.addEventListener('scroll', this.onReflow, true);
  },
  beforeUnmount() {
    document.removeEventListener('mousedown', this.onDocDown, true);
    window.removeEventListener('resize', this.onReflow);
    window.removeEventListener('scroll', this.onReflow, true);
  },
};
</script>
