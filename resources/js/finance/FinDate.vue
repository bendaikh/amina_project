<template>
  <label class="fin-field">
    <span>{{ label }}</span>
    <input
      type="text"
      inputmode="numeric"
      autocomplete="off"
      maxlength="10"
      placeholder="jj/mm/aaaa"
      :disabled="disabled"
      :value="text"
      @input="text = $event.target.value"
      @blur="commit"
      @keydown.enter.prevent="commit"
    />
  </label>
</template>

<script>
import { dateFr } from './format';

export default {
  name: 'FinDate',
  props: {
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Date' },
    disabled: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return { text: '' };
  },
  watch: {
    modelValue: {
      immediate: true,
      handler(value) {
        this.text = value ? dateFr(value).replace('—', '') : '';
      },
    },
  },
  methods: {
    commit() {
      const raw = this.text.trim();
      if (!raw) {
        this.$emit('update:modelValue', '');
        this.$emit('change', '');
        return;
      }
      const match = raw.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
      if (!match) {
        this.text = this.modelValue ? dateFr(this.modelValue) : '';
        return;
      }
      const iso = `${match[3]}-${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}`;
      const date = new Date(`${iso}T00:00:00`);
      if (Number.isNaN(date.getTime()) || date.getDate() !== Number(match[1])) {
        this.text = this.modelValue ? dateFr(this.modelValue) : '';
        return;
      }
      this.text = dateFr(iso);
      this.$emit('update:modelValue', iso);
      this.$emit('change', iso);
    },
  },
};
</script>
