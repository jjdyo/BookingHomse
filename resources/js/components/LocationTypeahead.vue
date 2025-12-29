<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LocationItem = {
  id: number;
  name: string;
  photo_url?: string | null;
};

interface Props {
  modelValue: number | null; // selected location ID
  label?: string;
  placeholder?: string;
  inputId?: string;
  disabled?: boolean;
  // Max results from backend
  limit?: number;
  // Optional initial name to display when preselected
  initialName?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  label: 'Location',
  placeholder: 'Type a location name…',
  inputId: 'location_id',
  disabled: false,
  limit: 8,
  initialName: null,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void;
  (e: 'select', location: LocationItem): void;
}>();

const selectedId = ref<number | null>(props.modelValue);
const query = ref<string>('');
const open = ref(false);
const loading = ref(false);
const results = ref<LocationItem[]>([]);
const highlightedIndex = ref<number>(-1);
let debounceTimer: number | undefined;
const rootEl = ref<HTMLElement | null>(null);
let suppressNextSearch = false;

watch(
  () => props.modelValue,
  (val) => {
    selectedId.value = val;
  }
);

onMounted(() => {
  // Seed the input text when an initial name is provided (e.g., on edit forms)
  if (props.initialName && !query.value) {
    query.value = props.initialName;
  }
});

watch(query, (val) => {
  // User may clear input to clear selection
  if (!val) {
    if (selectedId.value !== null) emit('update:modelValue', null);
  }

  if (suppressNextSearch) {
    suppressNextSearch = false;
    if (debounceTimer) window.clearTimeout(debounceTimer);
    open.value = false;
    return;
  }

  if (debounceTimer) window.clearTimeout(debounceTimer);
  if (!val) {
    results.value = [];
    open.value = false;
    return;
  }
  debounceTimer = window.setTimeout(async () => {
    try {
      loading.value = true;
      const url = `/locations/search?q=${encodeURIComponent(val)}&limit=${props.limit}`;
      const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = (await res.json()) as LocationItem[];
      results.value = data;
      open.value = data.length > 0;
      highlightedIndex.value = data.length ? 0 : -1;
    } catch {
      results.value = [];
      open.value = false;
    } finally {
      loading.value = false;
    }
  }, 200);
});

function select(item: LocationItem) {
  emit('update:modelValue', item.id);
  emit('select', item);
  selectedId.value = item.id;
  query.value = item.name;
  suppressNextSearch = true;
  open.value = false;
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
      select(results.value[highlightedIndex.value]);
    }
  } else if (e.key === 'Escape') {
    open.value = false;
  }
}

function onFocus() {
  if (results.value.length > 0) open.value = true;
}

function onBlur() {
  // Close dropdown if focus leaves component root
  setTimeout(() => {
    if (!rootEl.value?.contains(document.activeElement)) {
      open.value = false;
    }
  }, 120);
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
  <div ref="rootEl" class="grid gap-2">
    <Label :for="inputId">{{ label }}</Label>
    <div class="relative">
      <Input :id="inputId" :placeholder="placeholder" v-model="query" :disabled="disabled" @focus="onFocus" @blur="onBlur" />
      <div v-if="open" class="absolute z-10 mt-1 w-full rounded-md border bg-popover text-popover-foreground shadow-lg">
        <ul class="max-h-64 overflow-auto py-1 text-sm">
          <li v-for="(item, idx) in results" :key="item.id">
            <button
              type="button"
              class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-accent hover:text-accent-foreground"
              :class="{ 'bg-accent text-accent-foreground': idx === highlightedIndex }"
              @mousedown.prevent="select(item)"
            >
              <span class="inline-block h-6 w-6 overflow-hidden rounded bg-muted ring-1 ring-muted">
                <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-full w-full object-cover" loading="lazy" />
              </span>
              <span class="truncate">{{ item.name }}</span>
            </button>
          </li>
          <li v-if="!results.length && !loading" class="px-3 py-2 text-muted-foreground">No results</li>
          <li v-if="loading" class="px-3 py-2 text-muted-foreground">Searching…</li>
        </ul>
      </div>
    </div>
  </div>
  <!-- Note: No inline create; creation via Dashboard locations page -->
</template>
