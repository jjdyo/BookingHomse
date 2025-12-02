<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

import { ref, onMounted } from 'vue';

type EventClickArg = {
    event: any;
};

const selected = ref<null | {
    id: number;
    title: string;
    description?: string;
    start: string;
    end: string;
    capacity?: number;
    price?: number | string;
    trainer_name?: string | null;
    service_name?: string | null;
}>(null);

function onEventClick(arg: EventClickArg) {
    const e = arg.event;
    selected.value = {
        id: Number(e.id),
        title: e.title,
        description: e.extendedProps?.description,
        start: e.start?.toISOString?.() ?? e.startStr,
        end: e.end?.toISOString?.() ?? e.endStr,
        capacity: e.extendedProps?.capacity,
        price: e.extendedProps?.price,
        trainer_name: e.extendedProps?.trainer_name,
        service_name: e.extendedProps?.service_name,
    };
}

const calendarRef = ref<any>(null);

const calendarOptions: any = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    timeZone: import.meta.env.VITE_TZ ?? 'UTC',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    weekends: true,
    selectable: false,
    editable: false,
    events: { url: '/timeslots/feed' },
    eventClick: onEventClick,
    // Built-in options to make events feel larger/more spacious
    contentHeight: 900, // gives more vertical room so timeGrid rows are taller
    expandRows: true,
    dayMaxEventRows: 4, // month view: allow more rows before "+ more"
    dayMaxEvents: true,
    // Defaults; will be overridden by public settings
    slotMinTime: '09:00:00',
    slotMaxTime: '19:00:00',
    scrollTime: '09:00:00',
    height: 'auto',
};

onMounted(async () => {
    try {
        const res = await fetch('/settings/public');
        if (!res.ok) return;
        const s = await res.json();
        if (s?.booking_open_time && s?.booking_close_time) {
            // Prefer using FullCalendar API so changes take effect after mount
            const api = calendarRef.value?.getApi?.();
            if (api) {
                api.setOption('slotMinTime', s.booking_open_time);
                api.setOption('slotMaxTime', s.booking_close_time);
                api.setOption('scrollTime', s.booking_open_time);
                api.setOption('businessHours', {
                    startTime: s.booking_open_time,
                    endTime: s.booking_close_time,
                    daysOfWeek: [0,1,2,3,4,5,6],
                });
            } else {
                // Fallback: mutate options before first render
                calendarOptions.slotMinTime = s.booking_open_time;
                calendarOptions.slotMaxTime = s.booking_close_time;
                calendarOptions.scrollTime = s.booking_open_time;
                calendarOptions.businessHours = {
                    startTime: s.booking_open_time,
                    endTime: s.booking_close_time,
                    daysOfWeek: [0,1,2,3,4,5,6],
                };
            }
        }
    } catch (e) {
        // ignore
    }
});
</script>

<template>
    <Head title="Request Booking" />
    <BasicLayout>
        <section class="mx-auto max-w-6xl p-6">
            <h1 class="text-2xl font-semibold">Request Booking</h1>
            <p class="mt-2 text-muted-foreground">Select a timeslot to view details and book.</p>

            <div class="mt-6 rounded-lg border bg-background p-2 shadow-sm booking-calendar">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>

            <!-- Details Modal -->
            <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="selected = null"></div>
                <div class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                    <h2 class="text-xl font-semibold">{{ selected.title }}</h2>
                    <p v-if="selected?.service_name" class="mt-1 text-sm text-muted-foreground">
                        Service: {{ selected.service_name }}
                    </p>
                    <p v-if="selected?.trainer_name" class="text-sm text-muted-foreground">
                        Trainer: {{ selected.trainer_name }}
                    </p>
                    <div class="mt-3 space-y-1 text-sm">
                        <div>
                            <span class="font-medium">Starts:</span>
                            <span>{{ new Date(selected.start).toLocaleString() }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Ends:</span>
                            <span>{{ new Date(selected.end).toLocaleString() }}</span>
                        </div>
                        <div v-if="selected?.capacity !== undefined">
                            <span class="font-medium">Capacity:</span>
                            <span>{{ selected.capacity }}</span>
                        </div>
                        <div v-if="selected?.price !== undefined">
                            <span class="font-medium">Price:</span>
                            <span>${{ selected.price }}</span>
                        </div>
                    </div>
                    <p v-if="selected?.description" class="mt-4 whitespace-pre-wrap">{{ selected.description }}</p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" class="rounded-md border px-4 py-2" @click="selected = null">Close</button>
                        <a
                            class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                            :href="`/book/timeslot/${selected?.id}`"
                        >
                            Book Now!
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </BasicLayout>
</template>
