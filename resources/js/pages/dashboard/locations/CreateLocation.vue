<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';
import { ref } from 'vue';
import MediaPicker from '@/components/media/MediaPicker.vue'

type FormData = {
  name: string;
  slug: string | null;
  description: string;
  address: string | null;
  notes: string | null;
  photo_path: string | null;
  is_active: boolean;
};

const form = useForm<FormData>({
  name: '',
  slug: null,
  description: '',
  address: null,
  notes: null,
  photo_path: null,
  is_active: true,
});

const showPicker = ref(false);

function onMediaSelected(media: { path: string; url: string; thumbnails_urls?: Record<string, string> }) {
  form.photo_path = media.path
  showPicker.value = false
}

function submit() {
  form.post('/locations');
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Locations', href: '/dashboard/locations' },
  { title: 'New Location', href: '/dashboard/locations/create' },
];
</script>

<template>
  <Head title="New Location" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-2xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">New Location</h1>
        <a href="/dashboard/locations" class="text-sm text-blue-600 hover:underline">Back to Locations</a>
      </div>
      <p class="mt-2 text-muted-foreground">Create a location that can be assigned to timeslots.</p>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="name">Name</Label>
          <Input id="name" name="name" v-model="form.name" required placeholder="e.g., North Arena" />
          <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
          <Label for="slug">Slug (optional)</Label>
          <Input id="slug" name="slug" v-model="form.slug" placeholder="e.g., north-arena" />
          <InputError :message="(form.errors as any).slug" />
        </div>

        <div class="grid gap-2">
          <Label for="description">Description</Label>
          <textarea id="description" name="description" v-model="form.description" required class="min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Client-visible description..." />
          <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
          <Label for="address">Address (optional)</Label>
          <textarea id="address" name="address" v-model="form.address" class="min-h-20 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Address or directions" />
          <InputError :message="(form.errors as any).address" />
        </div>

        <div class="grid gap-2">
          <Label for="notes">Notes (optional)</Label>
          <textarea id="notes" name="notes" v-model="form.notes" class="min-h-20 w-full rounded-md border px-3 py-2 text-sm text-muted-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Internal notes (not client-visible)" />
          <InputError :message="(form.errors as any).notes" />
        </div>

        <div class="grid gap-2">
          <Label>Photo (optional)</Label>
          <div class="flex items-center gap-2">
            <Button type="button" variant="secondary" @click="showPicker = true">Choose from media…</Button>
            <span class="text-xs text-muted-foreground" v-if="form.photo_path">Using library image</span>
          </div>
          <InputError :message="(form.errors as any).photo_path" />
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4" />
          <Label for="is_active">Active</Label>
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Create Location' }}</Button>
        </div>
      </form>

      <MediaPicker v-if="showPicker" context-dir="locations" @close="showPicker = false" @select="onMediaSelected" />
    </section>
  </AppLayout>
</template>
