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

type FormData = {
    name: string;
    title: string | null;
    bio: string | null;
    photo: File | null;
};

const form = useForm<FormData>({
    name: '',
    title: null,
    bio: null,
    photo: null,
});

const previewUrl = ref<string | null>(null);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.photo = file as any;
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
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
                        {{ form.processing ? 'Saving…' : 'Create Trainer' }}
                    </Button>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
