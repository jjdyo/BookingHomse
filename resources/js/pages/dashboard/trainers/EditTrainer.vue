<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';
import { ref, watch } from 'vue';
import MediaPicker from '@/components/media/MediaPicker.vue'

type Trainer = {
  id: number;
  name: string;
  title: string | null;
  bio: string | null;
  photo_path: string | null;
};

interface Props {
  trainer: Trainer;
}

const props = defineProps<Props>();

type FormData = {
  name: string;
  title: string | null;
  bio: string | null;
  photo: File | null;
  photo_path: string | null;
};

const form = useForm<FormData>({
  name: props.trainer.name ?? '',
  title: props.trainer.title ?? null,
  bio: props.trainer.bio ?? null,
  photo: null,
  photo_path: props.trainer.photo_path ?? null,
});

const previewUrl = ref<string | null>(props.trainer.photo_path ? `/storage/${props.trainer.photo_path}` : null);
const showPicker = ref(false);

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0] ?? null;
  form.photo = file as any;
  if (file) form.photo_path = null; // prefer the selected file and clear library value
  if (file) {
    previewUrl.value = URL.createObjectURL(file);
  }
}

function onMediaSelected(media: { path: string; url: string; thumbnails_urls?: Record<string, string> }) {
  form.photo_path = media.path
  form.photo = null as any
  previewUrl.value = media.thumbnails_urls?.['256'] ?? media.url
  showPicker.value = false
}

watch(() => props.trainer.photo_path, (val) => {
  if (!form.photo) {
    previewUrl.value = val ? `/storage/${val}` : null;
  }
});

function submit() {
  // Ensure method spoofing is included in the body for Laravel to detect PUT
  form.transform((data) => ({ ...data, _method: 'PUT' }));
  form.post(`/trainers/${props.trainer.id}`, {
    forceFormData: true,
    onSuccess: () => {
      // Revoke object URL if we created one
      if (form.photo && previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
      }
      // Clean up file field after successful submit
      form.reset('photo');
    },
    onFinish: () => {
      // Reset transform so it won't affect unrelated future submissions
      form.transform((data) => data as any);
    },
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Trainers', href: '/dashboard/trainers' },
  { title: 'Edit Trainer', href: `/dashboard/trainers/${props.trainer.id}/edit` },
];
</script>

<template>
  <Head :title="`Edit Trainer — ${props.trainer.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-2xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Trainer</h1>
        <a href="/dashboard/trainers" class="text-sm text-blue-600 hover:underline">Back to Trainers</a>
      </div>
      <p class="mt-2 text-muted-foreground">Update trainer details. These fields mirror creation fields.</p>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="flex items-center gap-4">
          <div class="h-20 w-20 overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200">
            <img v-if="previewUrl" :src="previewUrl" alt="Preview" class="h-full w-full object-cover" />
          </div>
          <div class="grid gap-2">
            <Label for="photo">Photo</Label>
            <Input id="photo" name="photo" type="file" accept="image/*" @change="onFileChange" />
            <InputError :message="form.errors.photo" />
            <p class="text-xs text-muted-foreground">Square images look best. Max 5 MB.</p>
            <div class="flex items-center gap-2">
              <Button type="button" variant="secondary" @click="showPicker = true">Choose from library…</Button>
              <span class="text-xs text-muted-foreground" v-if="form.photo_path">Using library image</span>
            </div>
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="name">Name</Label>
          <Input id="name" name="name" v-model="form.name" required placeholder="e.g., Jamie Rivera" />
          <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
          <Label for="title">Job Title</Label>
          <Input id="title" name="title" v-model="form.title" placeholder="e.g., Head Trainer" />
          <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
          <Label for="bio">Bio</Label>
          <textarea
            id="bio"
            name="bio"
            v-model="form.bio"
            class="min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            placeholder="Experience, specialties, certifications, etc."
          />
          <InputError :message="form.errors.bio" />
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save Changes' }}
          </Button>
        </div>
      </form>
    </section>
  </AppLayout>

  <!-- Media Picker Modal -->
  <MediaPicker v-if="showPicker" :context-dir="'trainers'" @close="showPicker = false" @select="onMediaSelected" />
</template>
