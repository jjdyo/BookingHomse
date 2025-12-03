<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Trainer = {
  id: number;
  name: string;
  title?: string | null;
  photo_url?: string | null;
};

interface Props {
  modelValue: string | null;
  label?: string;
  placeholder?: string;
  inputId?: string;
  disabled?: boolean;
  // Max results to show from backend
  limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  label: 'Trainer',
  placeholder: 'Type a trainer name…',
  inputId: 'trainer_name',
  disabled: false,
  limit: 8,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void;
  (e: 'select', trainer: Trainer): void;
}>();

const query = ref<string>(props.modelValue ?? '');
const open = ref(false);
const loading = ref(false);
const results = ref<Trainer[]>([]);
const highlightedIndex = ref<number>(-1);
let debounceTimer: number | undefined;
const rootEl = ref<HTMLElement | null>(null);

watch(
  () => props.modelValue,
  (val) => {
    if (val !== query.value) query.value = val ?? '';
  }
);

watch(query, (val) => {
  emit('update:modelValue', val || null);
  // debounce search
  if (debounceTimer) window.clearTimeout(debounceTimer);
  if (!val) {
    results.value = [];
    open.value = false;
    return;
  }
  debounceTimer = window.setTimeout(async () => {
    try {
      loading.value = true;
      const url = `/trainers/search?q=${encodeURIComponent(val)}&limit=${props.limit}`;
      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error('Search failed');
      const data = (await res.json()) as Trainer[];
      results.value = data;
      open.value = data.length > 0;
      highlightedIndex.value = data.length ? 0 : -1;
    } catch (e) {
      // swallow; keep free-text usable
      results.value = [];
      open.value = false;
    } finally {
      loading.value = false;
    }
  }, 200);
});

function onKeydown(e: KeyboardEvent) {
  if (!open.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
    open.value = results.value.length > 0;
  }
  if (!open.value) return;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    highlightedIndex.value = (highlightedIndex.value + 1) % results.value.length;
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    highlightedIndex.value = (highlightedIndex.value - 1 + results.value.length) % results.value.length;
  } else if (e.key === 'Enter') {
    if (highlightedIndex.value >= 0 && highlightedIndex.value < results.value.length) {
      e.preventDefault();
      select(results.value[highlightedIndex.value]);
    }
  } else if (e.key === 'Escape') {
    open.value = false;
  }
}

function select(t: Trainer) {
  query.value = t.name;
  emit('update:modelValue', t.name);
  emit('select', t);
  open.value = false;
}

function handleClickOutside(ev: MouseEvent) {
  if (!rootEl.value) return;
  const target = ev.target as Node;
  if (!rootEl.value.contains(target)) {
    open.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="relative grid gap-2" ref="rootEl">
    <Label :for="props.inputId">{{ props.label }}</Label>
    <div class="relative">
      <Input
        :id="props.inputId"
        :placeholder="props.placeholder"
        v-model="query"
        :disabled="props.disabled"
        autocomplete="off"
        @focus="open = results.length > 0"
        @keydown="onKeydown"
      />
      <div v-if="loading" class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-muted-foreground">
        Searching…
      </div>

      <!-- Results dropdown positioned under the input -->
      <ul
        v-if="open"
        class="absolute left-0 top-full z-50 mt-1 max-h-64 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        role="listbox"
      >
        <li
          v-for="(t, i) in results"
          :key="t.id"
          :class="[
            'flex cursor-pointer items-center gap-2 rounded-sm px-2 py-2 text-sm hover:bg-accent hover:text-accent-foreground',
            i === highlightedIndex ? 'bg-accent text-accent-foreground' : ''
          ]"
          role="option"
          @mousedown.prevent="select(t)"
          @mousemove="highlightedIndex = i"
        >
          <img v-if="t.photo_url" :src="t.photo_url" alt="" class="h-6 w-6 rounded-full object-cover" />
          <div class="min-w-0">
            <div class="truncate font-medium">{{ t.name }}</div>
            <div v-if="t.title" class="truncate text-xs text-muted-foreground">{{ t.title }}</div>
          </div>
        </li>
      </ul>
    </div>
  </div>
  <!-- Hidden note: free-text accepted; selection emits both v-model and 'select' -->
</template>

<style scoped>
/* Rely mostly on existing design system classes */
</style>
