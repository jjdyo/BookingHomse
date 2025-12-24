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

type LocationPayload = {
  id: number;
  name: string;
  slug: string | null;
  description: string;
  address: string | null;
  notes: string | null;
  photo_path: string | null;
  is_active: boolean;
  photo_url?: string | null;
};

const props = defineProps<{ location: LocationPayload }>();

const form = useForm({
  name: props.location.name,
  slug: props.location.slug,
  description: props.location.description,
  address: props.location.address,
  notes: props.location.notes,
  photo_path: props.location.photo_path,
  is_active: props.location.is_active,
});

const showPicker = ref(false);

function onMediaSelected(media: { path: string; url: string; thumbnails_urls?: Record<string, string> }) {
  form.photo_path = media.path
  showPicker.value = false
}

function submit() {
  form.put(`/locations/${props.location.id}`);
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Locations', href: '/dashboard/locations' },
  { title: 'Edit Location', href: `/dashboard/locations/${props.location.id}/edit` },
];
</script>

<template>
  <Head :title="`Edit: ${props.location.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-2xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Location</h1>
        <a href="/dashboard/locations" class="text-sm text-blue-600 hover:underline">Back to Locations</a>
      </div>
      <p class="mt-2 text-muted-foreground">Update location details.</p>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="name">Name</Label>
          <Input id="name" name="name" v-model="form.name" required />
          <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
          <Label for="slug">Slug (optional)</Label>
          <Input id="slug" name="slug" v-model="form.slug" />
          <InputError :message="(form.errors as any).slug" />
        </div>

        <div class="grid gap-2">
          <Label for="description">Description</Label>
          <textarea id="description" name="description" v-model="form.description" required class="min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" />
          <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
          <Label for="address">Address (optional)</Label>
          <textarea id="address" name="address" v-model="form.address" class="min-h-20 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" />
          <InputError :message="(form.errors as any).address" />
        </div>

        <div class="grid gap-2">
          <Label for="notes">Notes (optional)</Label>
          <textarea id="notes" name="notes" v-model="form.notes" class="min-h-20 w-full rounded-md border px-3 py-2 text-sm text-muted-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600" />
          <InputError :message="(form.errors as any).notes" />
        </div>

        <div class="grid gap-2">
          <Label>Photo (optional)</Label>
          <div class="flex items-center gap-2">
            <div class="h-12 w-12 overflow-hidden rounded bg-muted">
              <img v-if="props.location.photo_url" :src="props.location.photo_url" alt="" class="h-full w-full object-cover" />
            </div>
            <button type="button" class="rounded-md border bg-background px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" @click="showPicker = true">Choose from media…</button>
            <span class="text-xs text-muted-foreground" v-if="form.photo_path">Using library image</span>
          </div>
          <InputError :message="(form.errors as any).photo_path" />
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" type="checkbox" v-model="form.is_active" class="h-4 w-4" />
          <Label for="is_active">Active</Label>
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save Changes' }}</Button>
        </div>
      </form>

      <MediaPicker v-if="showPicker" context-dir="locations" @close="showPicker = false" @select="onMediaSelected" />
    </section>
  </AppLayout>
</template>
