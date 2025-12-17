<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Horse = { id: number; name: string };

interface Props {
  modelValue: number[]; // selected horse IDs
  label?: string;
  placeholder?: string;
  inputId?: string;
  disabled?: boolean;
  limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  label: 'Horses',
  placeholder: 'Type to search horses…',
  inputId: 'horse_search',
  disabled: false,
  limit: 8,
});

const emit = defineEmits<{
  (e: 'update:modelValue', v: number[]): void;
}>();

const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref<Horse[]>([]);
const highlightedIndex = ref(-1);
const selected = ref<number[]>([...props.modelValue]);
let debounceTimer: number | undefined;
const rootEl = ref<HTMLElement | null>(null);

watch(
  () => props.modelValue,
  (val) => {
    if (JSON.stringify(val) !== JSON.stringify(selected.value)) {
      selected.value = [...val];
    }
  }
);

watch(selected, (val) => emit('update:modelValue', val));

watch(query, (val) => {
  if (debounceTimer) window.clearTimeout(debounceTimer);
  if (!val) {
    results.value = [];
    open.value = false;
    return;
  }
  debounceTimer = window.setTimeout(async () => {
    try {
      loading.value = true;
      const url = `/horses/search?q=${encodeURIComponent(val)}&limit=${props.limit}`;
      const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = (await res.json()) as Horse[];
      // Filter out already selected IDs
      results.value = data.filter((h) => !selected.value.includes(h.id));
      open.value = results.value.length > 0;
      highlightedIndex.value = results.value.length ? 0 : -1;
    } catch {
      results.value = [];
      open.value = false;
    } finally {
      loading.value = false;
    }
  }, 200);
});

function addHorse(h: Horse) {
  if (!selected.value.includes(h.id)) selected.value = [...selected.value, h.id];
  query.value = '';
  results.value = [];
  open.value = false;
}

function removeHorse(id: number) {
  selected.value = selected.value.filter((x) => x !== id);
}

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
      addHorse(results.value[highlightedIndex.value]);
    }
  } else if (e.key === 'Escape') {
    open.value = false;
  }
}

function onClickOutside(ev: MouseEvent) {
  if (!rootEl.value) return;
  if (!rootEl.value.contains(ev.target as Node)) open.value = false;
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
  <div ref="rootEl" class="w-full">
    <Label :for="props.inputId">{{ props.label }}</Label>
    <div class="mt-1">
      <div class="flex flex-wrap gap-2 mb-2">
        <template v-for="id in selected" :key="id">
          <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-700 border border-blue-200">
            #{{ id }}
            <button type="button" class="ml-1 text-blue-700 hover:text-blue-900" @click="removeHorse(id)" aria-label="Remove">
              ×
            </button>
          </span>
        </template>
        <span v-if="!selected.length" class="text-sm text-muted-foreground">No horses selected</span>
      </div>

      <div class="relative">
        <Input
          :id="props.inputId"
          v-model="query"
          type="text"
          :placeholder="props.placeholder"
          :disabled="props.disabled"
          @keydown="onKeydown"
        />

        <div v-if="open" class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow">
          <div v-if="loading" class="px-3 py-2 text-sm text-muted-foreground">Searching…</div>
          <button
            v-for="(h, idx) in results"
            :key="h.id"
            type="button"
            class="flex w-full cursor-pointer items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50"
            :class="idx === highlightedIndex ? 'bg-gray-50' : ''"
            @click="addHorse(h)"
          >
            <span>{{ h.name }}</span>
            <span class="text-xs text-muted-foreground">#{{ h.id }}</span>
          </button>
          <div v-if="!loading && results.length === 0" class="px-3 py-2 text-sm text-muted-foreground">No matches</div>
        </div>
      </div>
    </div>
  </div>
</template>
