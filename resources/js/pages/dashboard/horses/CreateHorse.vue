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
    description: string;
    breed: string | null;
    is_bookable: boolean;
    notes: string | null;
    photo: File | null;
    photo_path: string | null;
};

const form = useForm<FormData>({
    name: '',
    description: '',
    breed: null,
    is_bookable: true,
    notes: null,
    photo: null,
    photo_path: null,
});

const previewUrl = ref<string | null>(null);
const showPicker = ref(false);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.photo = file as any;
    if (file) {
        // Clear picked media path when uploading a file
        form.photo_path = null;
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
}

function onMediaSelected(media: { path: string; url: string; thumbnails_urls?: Record<string, string> }) {
  form.photo_path = media.path
  form.photo = null as any
  previewUrl.value = media.thumbnails_urls?.['256'] ?? media.url
  showPicker.value = false
}

function submit() {
    form.post('/horses', {
        forceFormData: true,
        onSuccess: () => {
            if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        },
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Horses', href: '/dashboard/horses' },
    { title: 'New Horse', href: '/dashboard/horses/create' },
];
</script>

<template>
    <Head title="New Horse" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="mx-auto max-w-2xl p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">New Horse</h1>
                <a href="/dashboard/horses" class="text-sm text-blue-600 hover:underline">Back to Horses</a>
            </div>
            <p class="mt-2 text-muted-foreground">Create a horse that can be assigned to bookings.</p>

            <form class="mt-6 grid gap-5" @submit.prevent="submit">
                <div class="flex items-center gap-4">
                    <div class="h-20 w-20 overflow-hidden rounded-full bg-gray-100 ring-2 ring-gray-200">
                        <img v-if="previewUrl" :src="previewUrl" alt="Preview" class="h-full w-full object-cover" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="photo">Photo</Label>
                        <Input id="photo" name="photo" type="file" accept="image/*" @change="onFileChange" />
                        <InputError :message="(form.errors as any).photo" />
                        <p class="text-xs text-muted-foreground">Square images look best. Max 5 MB.</p>
                        <div class="flex items-center gap-2">
                          <Button type="button" variant="secondary" @click="showPicker = true">Choose from library…</Button>
                          <span class="text-xs text-muted-foreground" v-if="form.photo_path">Using library image</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" v-model="form.name" required placeholder="e.g., Starfire" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="breed">Breed</Label>
                    <Input id="breed" name="breed" v-model="form.breed" placeholder="e.g., Quarter Horse" />
                    <InputError :message="form.errors.breed" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        v-model="form.description"
                        required
                        class="min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                        placeholder="Temperament, experience level, etc."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_bookable" type="checkbox" v-model="form.is_bookable" class="h-4 w-4" />
                    <Label for="is_bookable">Bookable</Label>
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes (optional)</Label>
                    <textarea
                        id="notes"
                        name="notes"
                        v-model="form.notes"
                        class="min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                        placeholder="Internal notes..."
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Create Horse' }}
                    </Button>
                </div>
            </form>
            <!-- Media Picker Modal -->
            <MediaPicker v-if="showPicker" :context-dir="'horses'" @close="showPicker = false" @select="onMediaSelected" />
        </section>
    </AppLayout>
</template>
