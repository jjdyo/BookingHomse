<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

type Horse = {
    id: number;
    name: string;
    description: string;
    breed: string | null;
    is_bookable: boolean;
    notes: string | null;
};

interface Props {
    horse: Horse;
}

const props = defineProps<Props>();

const form = useForm<Pick<Horse, 'name' | 'description' | 'breed' | 'is_bookable' | 'notes'>>({
    name: props.horse.name ?? '',
    description: props.horse.description ?? '',
    breed: props.horse.breed ?? null,
    is_bookable: props.horse.is_bookable,
    notes: props.horse.notes ?? null,
});

function submit() {
    form.put(`/horses/${props.horse.id}`);
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Horses', href: '/dashboard/horses' },
    { title: 'Edit Horse', href: `/dashboard/horses/${props.horse.id}/edit` },
];
</script>

<template>
    <Head :title="`Edit Horse — ${props.horse.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="mx-auto max-w-2xl p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Edit Horse</h1>
                <a href="/dashboard/horses" class="text-sm text-blue-600 hover:underline">Back to Horses</a>
            </div>
            <p class="mt-2 text-muted-foreground">Update horse details. These fields mirror creation fields.</p>

            <form class="mt-6 grid gap-5" @submit.prevent="submit">
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
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
