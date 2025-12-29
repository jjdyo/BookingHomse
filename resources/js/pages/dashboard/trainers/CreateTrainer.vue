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
    title: string | null;
    bio: string | null;
    photo: File | null;
    photo_path: string | null;
    is_bookable: boolean;
};

const form = useForm<FormData>({
    name: '',
    title: null,
    bio: null,
    photo: null,
    photo_path: null,
    is_bookable: true,
});

const previewUrl = ref<string | null>(null);
const showPicker = ref(false);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.photo = file as any;
    // Clear selected library path when user chooses a file
    if (file) form.photo_path = null;
    if (file) {
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
    form.post('/trainers', {
        forceFormData: true,
        onSuccess: () => {
            if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        },
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Trainers', href: '/dashboard/trainers' },
    { title: 'Add Trainer', href: '/dashboard/trainers/create' },
];
</script>

<template>
    <Head title="Add Trainer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="mx-auto max-w-2xl p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Add a Trainer</h1>
                <a href="/dashboard/trainers" class="text-sm text-blue-600 hover:underline">Back to Trainers</a>
            </div>
            <p class="mt-2 text-muted-foreground">Upload a photo and add basic details for this trainer.</p>

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

                <!-- Bookable toggle -->
                <div class="flex items-center gap-3 rounded-md border bg-card px-3 py-2 text-card-foreground">
                    <input
                        id="is_bookable"
                        name="is_bookable"
                        type="checkbox"
                        class="h-4 w-4"
                        v-model="form.is_bookable"
                    />
                    <Label for="is_bookable" class="select-none">Bookable?</Label>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Create Trainer' }}
                    </Button>
                </div>
            </form>

            <!-- Media Picker Modal -->
            <MediaPicker v-if="showPicker" :context-dir="'trainers'" @close="showPicker = false" @select="onMediaSelected" />
        </section>
    </AppLayout>
</template>
