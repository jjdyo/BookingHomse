<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import { useBookingCalendarOptions } from '@/composables/useBookingCalendar';
import { ref } from 'vue';

type FormData = {
    title: string;
    description: string;
    start_at: string;
    end_at: string;
    capacity: number | null;
    price: number | null;
    service_name: string | null;
    trainer_name: string | null;
};

const form = useForm<FormData>({
    title: '',
    description: '',
    start_at: '',
    end_at: '',
    capacity: 1,
    price: 0,
    service_name: null,
    trainer_name: null,
});

function submit() {
    form.post('/timeslots');
}

// Lightweight reference calendar at the top so creators can see existing bookings
type EventClickArg = { event: any };

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

const { calendarRef, calendarOptions } = useBookingCalendarOptions({ compact: true, eventClick: onEventClick });
</script>

<template>
    <Head title="Create Timeslot" />
    <BasicLayout>
        <section class="mx-auto max-w-5xl p-6">
            <h1 class="text-2xl font-semibold">Create a Timeslot</h1>
            <p class="mt-2 text-muted-foreground">Enter the basic details for this availability window.</p>

            <!-- Reference Calendar -->
            <div class="mt-6 rounded-lg border bg-background p-2 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-lg font-medium">Existing Bookings</h2>
                    <p class="text-sm text-muted-foreground">Use this calendar to reference other scheduled items.</p>
                </div>
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>

            <!-- Details Modal (read-only on create page) -->
            <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="selected = null"></div>
                <div class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                    <h2 class="text-xl font-semibold">{{ selected?.title }}</h2>
                    <p v-if="selected?.service_name" class="mt-1 text-sm text-muted-foreground">Service: {{ selected?.service_name }}</p>
                    <p v-if="selected?.trainer_name" class="text-sm text-muted-foreground">Trainer: {{ selected?.trainer_name }}</p>
                    <div class="mt-3 space-y-1 text-sm">
                        <div>
                            <span class="font-medium">Starts:</span>
                            <span>{{ selected?.start ? new Date(selected.start).toLocaleString() : null }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Ends:</span>
                            <span>{{ selected?.end ? new Date(selected.end).toLocaleString() : null }}</span>
                        </div>
                        <div v-if="selected?.capacity !== undefined">
                            <span class="font-medium">Capacity:</span>
                            <span>{{ selected?.capacity }}</span>
                        </div>
                        <div v-if="selected?.price !== undefined">
                            <span class="font-medium">Price:</span>
                            <span>${{ selected?.price }}</span>
                        </div>
                    </div>
                    <p v-if="selected?.description" class="mt-4 whitespace-pre-wrap">{{ selected?.description }}</p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" class="rounded-md border px-4 py-2" @click="selected = null">Close</button>
                    </div>
                </div>
            </div>

            <form class="mt-6 grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input id="title" name="title" v-model="form.title" required placeholder="e.g., Private Lesson" />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        v-model="form.description"
                        required
                        class="min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
                        placeholder="Add any notes or details..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="start_at">Start</Label>
                        <Input id="start_at" name="start_at" v-model="form.start_at" type="datetime-local" required />
                        <InputError :message="form.errors.start_at" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="end_at">End</Label>
                        <Input id="end_at" name="end_at" v-model="form.end_at" type="datetime-local" required />
                        <InputError :message="form.errors.end_at" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="capacity">Capacity</Label>
                        <Input id="capacity" name="capacity" v-model.number="form.capacity" type="number" min="1" />
                        <InputError :message="form.errors.capacity" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="price">Price</Label>
                        <Input id="price" name="price" v-model.number="form.price" type="number" min="0" step="0.01" />
                        <InputError :message="form.errors.price" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="service_name">Service (optional)</Label>
                        <Input id="service_name" name="service_name" v-model="form.service_name" placeholder="e.g., Lesson" />
                        <InputError :message="form.errors.service_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="trainer_name">Trainer (optional)</Label>
                        <Input id="trainer_name" name="trainer_name" v-model="form.trainer_name" placeholder="e.g., Jamie" />
                        <InputError :message="form.errors.trainer_name" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save Timeslot' }}
                    </Button>
                </div>
            </form>
        </section>
    </BasicLayout>
</template>
