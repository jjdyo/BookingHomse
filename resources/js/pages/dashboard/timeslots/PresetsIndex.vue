<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

const props = defineProps<{ presets: Array<{ id: number; preset_title: string; preset_description?: string | null; color?: string | null; title?: string; description?: string | null; capacity?: number | null; price?: number | string | null; service_name?: string | null; trainer_name?: string | null; horses?: Array<{ id: number; name: string }>; }> }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Timeslots', href: '/dashboard/timeslots' },
  { title: 'Presets', href: '/dashboard/timeslots/presets' },
];

function tintStyle(color?: string | null) {
  const c = color || '#3B82F6';
  return {
    borderLeft: `6px solid ${c}`,
  } as any;
}
</script>

<template>
  <Head title="Timeslot Presets" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-6xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Timeslot Presets</h1>
        <Link href="/dashboard/timeslots/presets/create" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Create Preset</Link>
      </div>
      <p class="mt-2 text-muted-foreground">Create reusable configurations for commonly offered services.</p>

      <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div v-for="p in props.presets" :key="p.id" class="rounded-lg border bg-background p-4 shadow-sm" :style="tintStyle(p.color)">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold">{{ p.preset_title }}</h2>
              <p v-if="p.preset_description" class="mt-1 text-sm text-muted-foreground">{{ p.preset_description }}</p>
              <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                <div v-if="p.title"><span class="font-medium">Timeslot Title:</span> {{ p.title }}</div>
                <div v-if="p.description"><span class="font-medium">Description:</span> {{ p.description }}</div>
                <div class="grid grid-cols-2 gap-2">
                  <div v-if="p.capacity !== undefined && p.capacity !== null"><span class="font-medium">Capacity:</span> {{ p.capacity }}</div>
                  <div v-if="p.price !== undefined && p.price !== null"><span class="font-medium">Price:</span> ${{ p.price }}</div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div v-if="p.service_name"><span class="font-medium">Service:</span> {{ p.service_name }}</div>
                  <div v-if="p.trainer_name"><span class="font-medium">Trainer:</span> {{ p.trainer_name }}</div>
                </div>
                <div v-if="(p.horses?.length ?? 0) > 0"><span class="font-medium">Default Horses:</span> {{ p.horses?.map(h => h.name).join(', ') }}</div>
              </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <Link :href="`/dashboard/timeslots/presets/${p.id}/edit`" class="rounded-md border px-3 py-1 text-sm hover:bg-accent">Edit Preset</Link>
              <Link :href="`/dashboard/timeslots/presets/${p.id}/deploy`" class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700">Deploy Preset</Link>
              <Link :href="`/dashboard/timeslots/presets/${p.id}`" method="delete" as="button" class="rounded-md border px-3 py-1 text-sm text-red-600 hover:bg-red-50">Delete</Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  </AppLayout>

</template>
