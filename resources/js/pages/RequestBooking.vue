<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    timeZone: import.meta.env.VITE_TZ ?? 'UTC',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    weekends: true,
    selectable: false,
    editable: false,
    events: [], // No events yet — backend will provide later
    height: 'auto',
};
</script>

<template>
    <Head title="Request Booking" />
    <BasicLayout>
        <section class="mx-auto max-w-6xl p-6">
            <h1 class="text-2xl font-semibold">Request Booking</h1>
            <p class="mt-2 text-muted-foreground">Select a date to see availability. This calendar is currently blank and will be populated from the backend in a later step.</p>

            <div class="mt-6 rounded-lg border bg-background p-2 shadow-sm">
                <FullCalendar :options="calendarOptions" />
            </div>
        </section>
    </BasicLayout>
</template>
