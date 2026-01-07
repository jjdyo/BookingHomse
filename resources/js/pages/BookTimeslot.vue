<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';

interface Props {
    timeslot: {
        id: number;
        title: string;
        description?: string;
        start_at: string;
        end_at: string;
        price?: string | number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItemType[]>(() => {
    // Try to determine if we came from dashboard or public booking
    const isFromDashboard = typeof document !== 'undefined' && document.referrer.includes('/dashboard');

    const baseBreadcrumb = isFromDashboard
        ? { title: 'Timeslots', href: '/dashboard/timeslots' }
        : { title: 'Request Booking', href: '/request-booking' };

    return [
        baseBreadcrumb,
        { title: props.timeslot.title }
    ];
});
</script>

<template>
    <Head :title="`Book: ${props.timeslot.title}`" />
    <BasicLayout>
        <section class="mx-auto max-w-2xl p-6">
            <div class="mb-6">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>

            <h1 class="text-2xl font-semibold">Book: {{ props.timeslot.title }}</h1>
            <p class="mt-2 text-muted-foreground">This is a placeholder page. Booking flow will be implemented next.</p>

            <div class="mt-6 rounded-lg border bg-card p-5 text-card-foreground shadow-sm">
                <dl class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div>
                        <dt class="font-medium">Starts</dt>
                        <dd>{{ new Date(props.timeslot.start_at).toLocaleString() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">Ends</dt>
                        <dd>{{ new Date(props.timeslot.end_at).toLocaleString() }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">Price</dt>
                        <dd v-if="props.timeslot.price !== undefined">${{ props.timeslot.price }}</dd>
                        <dd v-else>—</dd>
                    </div>
                </dl>

                <p v-if="props.timeslot.description" class="mt-4 whitespace-pre-wrap text-foreground">{{ props.timeslot.description }}</p>
            </div>

            <div class="mt-6 text-sm text-muted-foreground">
                You are logged in and reached the intended booking URL. In the future, this page will collect horse selection and confirm your booking.
            </div>
        </section>
    </BasicLayout>
</template>
