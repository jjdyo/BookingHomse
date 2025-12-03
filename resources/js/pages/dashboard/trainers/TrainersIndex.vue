<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

interface Trainer {
    id: number;
    name: string;
    title: string | null;
    bio: string | null;
    photo_path: string | null;
    created_at?: string;
    updated_at?: string;
}

interface Props {
    trainers: Trainer[];
}

const props = defineProps<Props>();

const grid = ref(true);

const sorted = computed(() => {
    return [...props.trainers].sort((a, b) => a.name.localeCompare(b.name));
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Trainers', href: '/dashboard/trainers' },
];

function photoUrl(path?: string | null) {
    if (!path) return null;
    // With `storage:link`, public disk files are served from /storage
    return `/storage/${path}`;
}
</script>

<template>
    <Head title="Dashboard — Trainers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <section class="mx-auto max-w-6xl p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">Trainers</h1>
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
                        href="/dashboard/trainers/create"
                        class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        Add a Trainer
                    </a>
                </div>
            </div>

            <p class="mt-2 text-muted-foreground">Add and manage trainers with photos and bios.</p>

            <!-- Grid View -->
            <div v-if="grid" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="t in sorted" :key="t.id" class="rounded-lg border bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <img
                            v-if="photoUrl(t.photo_path)"
                            :src="photoUrl(t.photo_path)!"
                            alt="Trainer photo"
                            class="h-16 w-16 rounded-full object-cover ring-2 ring-gray-200"
                        />
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold">{{ t.name }}</h3>
                            <p v-if="t.title" class="text-sm text-muted-foreground">{{ t.title }}</p>
                        </div>
                    </div>
                    <p v-if="t.bio" class="mt-3 line-clamp-4 text-sm text-gray-700">{{ t.bio }}</p>
                    <div class="mt-4 flex justify-end">
                        <a
                            class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50"
                            :href="`/dashboard/trainers/${t.id}/edit`"
                        >
                            Edit
                        </a>
                    </div>
                </div>
            </div>

            <!-- Rows View -->
            <div v-else class="mt-6 overflow-hidden rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Photo</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Bio</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in sorted" :key="t.id" class="border-t">
                            <td class="px-4 py-3">
                                <img
                                    v-if="photoUrl(t.photo_path)"
                                    :src="photoUrl(t.photo_path)!"
                                    alt="Trainer photo"
                                    class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-200"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium">{{ t.name }}</td>
                            <td class="px-4 py-3">{{ t.title || '—' }}</td>
                            <td class="px-4 py-3">{{ t.bio || '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50"
                                    :href="`/dashboard/trainers/${t.id}/edit`"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>

</template>
