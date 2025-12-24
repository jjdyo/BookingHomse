<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

const props = defineProps<{ locations: Array<{ id: number; name: string; address?: string | null; photo_url?: string | null; is_active: boolean }>; }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Locations', href: '/dashboard/locations' },
];
</script>

<template>
  <Head title="Locations" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-6xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Locations</h1>
        <a href="/dashboard/locations/create" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">New Location</a>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="l in props.locations" :key="l.id" class="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 overflow-hidden rounded bg-muted">
              <img v-if="l.photo_url" :src="l.photo_url" alt="" class="h-full w-full object-cover" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="truncate font-medium">{{ l.name }}</h3>
                <span v-if="!l.is_active" class="rounded bg-rose-100 px-2 py-0.5 text-xs text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Inactive</span>
              </div>
              <p class="truncate text-sm text-muted-foreground">{{ l.address || '—' }}</p>
            </div>
          </div>
          <div class="mt-4 flex justify-end">
            <a :href="`/dashboard/locations/${l.id}/edit`" class="text-sm text-blue-600 hover:underline">Edit</a>
          </div>
      </div>
      </div>
    </section>
  </AppLayout>

</template>
