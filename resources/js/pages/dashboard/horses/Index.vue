<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

interface Horse {
    id: number;
    name: string;
    description: string;
    breed: string | null;
    is_bookable: boolean;
    notes: string | null;
    created_at?: string;
    updated_at?: string;
}

interface Props {
    horses: Horse[];
}

const props = defineProps<Props>();

const grid = ref(true);

const sorted = computed(() => {
    return [...props.horses].sort((a, b) => a.name.localeCompare(b.name));
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Horses', href: '/dashboard/horses' },
];
</script>

<template>
    <Head title="Dashboard — Horses" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="mx-auto max-w-6xl p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Horses</h1>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-md border px-3 py-2 text-sm"
                        @click="grid = !grid"
                        :aria-pressed="grid ? 'true' : 'false'"
                        :title="grid ? 'Switch to rows' : 'Switch to grid'"
                    >
                        {{ grid ? 'Rows' : 'Grid' }} View
                    </button>
                    <a
                        href="/dashboard/horses/create"
                        class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        New Horse
                    </a>
                </div>
            </div>

            <p class="mt-2 text-muted-foreground">Add and manage horses available for booking.</p>

            <!-- Grid View -->
            <div v-if="grid" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="h in sorted" :key="h.id" class="rounded-lg border bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">{{ h.name }}</h3>
                            <p v-if="h.breed" class="text-sm text-muted-foreground">{{ h.breed }}</p>
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-xs"
                            :class="h.is_bookable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                        >
                            {{ h.is_bookable ? 'Bookable' : 'Not bookable' }}
                        </span>
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm text-gray-700">{{ h.description }}</p>
                    <p v-if="h.notes" class="mt-2 line-clamp-2 text-xs text-muted-foreground">Notes: {{ h.notes }}</p>
                </div>
            </div>

            <!-- Rows View -->
            <div v-else class="mt-6 overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Breed</th>
                            <th class="px-4 py-3">Bookable</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in sorted" :key="h.id" class="border-t">
                            <td class="px-4 py-3 font-medium">{{ h.name }}</td>
                            <td class="px-4 py-3">{{ h.breed || '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="h.is_bookable ? 'text-green-700' : 'text-red-700'">
                                    {{ h.is_bookable ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ h.description }}</td>
                            <td class="px-4 py-3">{{ h.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
