<script setup lang="ts">
import { computed } from 'vue';

type Horse = {
  id: number;
  name: string;
  breed?: string | null;
  photo_url?: string | null;
  notes?: string | null;
};

interface Props {
  horse: Horse;
  removable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  removable: true,
});

const emit = defineEmits<{
  (e: 'remove', id: number): void;
}>();

const initials = computed(() => {
  const n = props.horse?.name ?? '';
  const parts = n.split(/\s+/).filter(Boolean);
  const chars = parts.slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '');
  return chars.join('');
});
</script>

<template>
  <div
    class="group relative flex items-center gap-3 rounded-xl border border-gray-200 bg-white/70 px-3 py-2 shadow-sm hover:shadow transition-shadow dark:bg-gray-900/60 dark:border-gray-800"
  >
    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
      <img
        v-if="horse.photo_url || horse.photo_path"
        :src="horse.photo_url ?? (`/storage/${horse.photo_path}`)"
        :alt="horse.name"
        class="h-full w-full object-cover"
      />
      <div v-else class="flex h-full w-full items-center justify-center text-sm font-semibold text-gray-500">
        {{ initials }}
      </div>
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2">
        <span class="truncate font-medium">{{ horse.name }}</span>
      </div>
      <div v-if="horse.breed" class="truncate text-xs text-muted-foreground">{{ horse.breed }}</div>
      <div v-if="horse.notes" class="truncate text-xs text-muted-foreground">{{ horse.notes }}</div>
    </div>

    <button
      v-if="removable"
      type="button"
      class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-gray-800"
      aria-label="Remove horse"
      @click="emit('remove', horse.id)"
    >
      <span aria-hidden="true">×</span>
    </button>
  </div>
</template>
