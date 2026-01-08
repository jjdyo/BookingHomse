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
    cooldown_duration: number | null;
    cooldown_unit: 'minutes' | 'hours' | 'days' | null;
};

const form = useForm<FormData>({
    name: '',
    description: '',
    breed: null,
    is_bookable: true,
    notes: null,
    photo: null,
    photo_path: null,
    cooldown_duration: null,
    cooldown_unit: null,
});

const showCooldown = ref(false);

function toggleCooldown(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  if (!checked) {
    form.cooldown_duration = null;
    form.cooldown_unit = null;
  } else {
    form.cooldown_duration = 30;
    form.cooldown_unit = 'minutes';
  }
}

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

                <div class="grid gap-2 rounded-md border bg-card p-3 text-card-foreground">
                    <div class="flex items-center gap-2">
                        <input id="is_bookable" type="checkbox" v-model="form.is_bookable" class="h-4 w-4" />
                        <Label for="is_bookable">Bookable</Label>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        This horse {{ form.is_bookable ? 'will' : 'will not' }} show up when creating or editing timeslots.
                    </p>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center gap-2">
                        <input id="cooldown" type="checkbox" v-model="showCooldown" @change="toggleCooldown" class="h-4 w-4" />
                        <Label for="cooldown">Cooldown</Label>
                    </div>
                    <div v-if="showCooldown" class="mt-2 rounded-md border bg-muted/50 p-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span>{{ form.name || 'This horse' }} should have</span>
                            <Input
                                type="number"
                                v-model="form.cooldown_duration"
                                class="w-20"
                                :min="1"
                                :max="form.cooldown_unit === 'minutes' ? 59 : (form.cooldown_unit === 'hours' ? 23 : 7)"
                            />
                            <select
                                v-model="form.cooldown_unit"
                                class="rounded-md border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                            >
                                <option value="minutes">minutes</option>
                                <option value="hours">hours</option>
                                <option value="days">days</option>
                            </select>
                            <span>to cooldown between booked sessions.</span>
                        </div>
                        <p class="mt-3 text-xs text-muted-foreground">
                            Attempting to book {{ form.name || 'this horse' }} earlier than configured will display a
                            <a href="/dashboard/settings/site" class="text-blue-600 hover:underline">warning</a>
                            if configured.
                        </p>
                        <InputError :message="form.errors.cooldown_duration" />
                        <InputError :message="form.errors.cooldown_unit" />
                    </div>
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
