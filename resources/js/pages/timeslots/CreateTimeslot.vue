<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useBookingCalendarOptions, type CalendarFilterState } from '@/composables/useBookingCalendar';
import CalendarFilters from '@/components/CalendarFilters.vue';
import { ref, computed, onMounted, watch, defineAsyncComponent } from 'vue';

const FullCalendar = defineAsyncComponent(() => import('@fullcalendar/vue3'));
const TimeslotSidebar = defineAsyncComponent(() => import('@/components/TimeslotSidebar.vue'));

import { normalizeDateTimeToIso } from '@/lib/datetime';
import TrainerMultiTypeahead from '@/components/TrainerMultiTypeahead.vue';
import HorseTypeahead from '@/components/HorseTypeahead.vue';
import LocationTypeahead from '@/components/LocationTypeahead.vue';

type FormData = {
    title: string;
    description: string;
    start_at: string;
    end_at: string;
    capacity: number | null;
    price: number | null;
    service_name: string | null;
    trainer_ids: number[];
    location_id: number | null;
    horse_ids: number[];
    color: string | null;
};

type Warnings = { trainers: boolean; horses: boolean; horse_cooldown: boolean; timeslots: boolean };
const props = defineProps<{
    warnings: Warnings;
    preset?: {
        title: string;
        description: string;
        capacity: number | null;
        price: number | null;
        service_name: string | null;
        location_id: number | null;
        location_name: string | null;
        color: string | null;
        trainer_ids: number[];
        trainers: any[];
        horse_ids: number[];
        horses: any[];
    } | null;
}>();

const checkingConflicts = ref(false);
const conflictCheckError = ref<string | null>(null);
const conflictModalOpen = ref(false);
const conflicts = ref<{ timeslots: any[]; trainers: any[]; horses: any[]; cooldowns: any[] }>({
    timeslots: [],
    trainers: [],
    horses: [],
    cooldowns: [],
});
const presetInitialHorses = ref<any[]>(props.preset?.horses ?? []);
const presetInitialTrainers = ref<any[]>(props.preset?.trainers ?? []);
const presetInitialLocationName = ref<string | null>(props.preset?.location_name ?? null);

const form = useForm<FormData>({
    title: props.preset?.title ?? '',
    description: props.preset?.description ?? '',
    start_at: '',
    end_at: '',
    capacity: props.preset?.capacity ?? 1,
    price: props.preset?.price ?? 0,
    service_name: props.preset?.service_name ?? null,
    trainer_ids: props.preset?.trainer_ids ?? [],
    location_id: props.preset?.location_id ?? null,
    horse_ids: props.preset?.horse_ids ?? [],
    color: props.preset?.color ?? '#3B82F6',
});

function toIso(value: string) {
    return normalizeDateTimeToIso(value);
}

// CSRF helper: fetch the token from the standard Laravel meta tag, with cookie fallback
function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

// Helpers to format and describe overlaps in human language
function toLocal(dt: string | Date | null | undefined): string {
    if (!dt) return '';
    const d = typeof dt === 'string' ? new Date(dt) : (dt as Date);
    return isNaN(d.getTime()) ? '' : d.toLocaleString();
}

function ms(value: string): number {
    const d = new Date(value);
    return d.getTime();
}

function describeOverlap(aStart: string, aEnd: string, bStart: string, bEnd: string): string {
    const as = ms(aStart);
    const ae = ms(aEnd);
    const bs = ms(bStart);
    const be = ms(bEnd);
    if (!(as < be && ae > bs)) return 'does not overlap';

    const startsSame = as === bs;
    const endsSame = ae === be;

    if (startsSame && endsSame) return 'has the exact same time as';
    if (as <= bs && ae >= be) return 'fully covers';
    if (as >= bs && ae <= be) return 'is fully inside';
    if (as < bs && ae > bs && ae < be) return 'starts before and ends during';
    if (as > bs && as < be && ae > be) return 'starts during and ends after';
    if (as === bs) return 'starts at the same time as';
    if (ae === be) return 'ends at the same time as';
    // generic fallback
    return 'overlaps with';
}

const currentStart = computed(() => (form as any).start_at as string);
const currentEnd = computed(() => (form as any).end_at as string);
const currentTitle = computed(() => (form as any).title as string);

const hasAnyRelevantConflicts = computed(() => {
    const c = conflicts.value;
    return (
        (props.warnings.timeslots && c.timeslots.length > 0) ||
        (props.warnings.trainers && c.trainers.length > 0) ||
        (props.warnings.horses && c.horses.length > 0) ||
        (props.warnings.horse_cooldown && c.cooldowns.length > 0)
    );
});

const dateValidationError = computed(() => {
    const start = (form as any).start_at as string;
    const end = (form as any).end_at as string;
    if (!start || !end) return null;

    const s = new Date(start).getTime();
    const e = new Date(end).getTime();

    if (s >= e) {
        return 'End time must be after start time.';
    }
    return null;
});

const conflictSummary = computed(() => {
    const c = conflicts.value;
    const parts = [];
    if (props.warnings.timeslots && c.timeslots.length > 0) parts.push(`${c.timeslots.length} timeslot overlap(s)`);
    if (props.warnings.trainers && c.trainers.length > 0) parts.push(`${c.trainers.length} trainer overlap(s)`);
    if (props.warnings.horses && c.horses.length > 0) parts.push(`${c.horses.length} horse overlap(s)`);
    if (props.warnings.horse_cooldown && c.cooldowns.length > 0) parts.push(`${c.cooldowns.length} cooldown violation(s)`);
    return parts.join(', ');
});

async function checkConflicts(): Promise<boolean> {
    const start = (form as any).start_at as string;
    const end = (form as any).end_at as string;

    if (!start || !end) {
        return false;
    }

    checkingConflicts.value = true;
    conflictCheckError.value = null;
    try {
        const payload = {
            start_at: toIso(start),
            end_at: toIso(end),
            trainer_ids: (form as any).trainer_ids,
            horse_ids: (form as any).horse_ids,
        } as any;

        const res = await fetch('/timeslots/check-conflicts', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                // Send both forms Laravel accepts: session token (X-CSRF-TOKEN) and cookie token (X-XSRF-TOKEN)
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const text = await res.text();
            console.error('Conflict check failed', res.status, res.statusText, text);
            conflictCheckError.value = 'Unable to run conflict warnings at the moment. Please try again, or save only if you are sure.';
            conflicts.value = { timeslots: [], trainers: [], horses: [], cooldowns: [] };
            return false;
        }
        const data = await res.json();
        conflicts.value = data?.conflicts ?? { timeslots: [], trainers: [], horses: [], cooldowns: [] };
        return hasAnyRelevantConflicts.value;
    } catch (e) {
        // If conflict check fails, do not auto‑proceed; require explicit user action
        console.error('Conflict check error', e);
        conflictCheckError.value = 'Unable to run conflict warnings at the moment. Please try again, or save only if you are sure.';
        conflicts.value = { timeslots: [], trainers: [], horses: [], cooldowns: [] };
        return false;
    } finally {
        checkingConflicts.value = false;
    }
}

async function submit() {
    // Normalize datetime-local (no TZ) -> ISO8601 (UTC) so server stores the correct instant
    const willShowModal = await checkConflicts();
    if (willShowModal) {
        conflictModalOpen.value = true;
        return;
    }
    // If the conflict check failed, do not auto‑proceed; require explicit user action
    if (conflictCheckError.value) {
        return;
    }

    form.transform((data) => {
        const payload = {
            ...data,
            start_at: toIso((data as any).start_at),
            end_at: toIso((data as any).end_at),
        };
        return payload;
    });

    form.post('/timeslots', {
        onFinish: () => {
            form.transform((d) => d as any);
        },
    });
}

// Lightweight reference calendar at the top so creators can see existing bookings
type EventClickArg = { event: any };

const filters = ref<CalendarFilterState>({
    search: '',
    title: '',
    address: '',
    horses: '',
    trainers: '',
});

const selected = ref<null | {
    id: number;
    title: string;
    description?: string;
    start: string;
    end: string;
    capacity?: number;
    price?: number | string;
    trainer_label?: string | null;
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
        trainer_label: e.extendedProps?.trainer_label,
        service_name: e.extendedProps?.service_name,
    };
}

const { calendarRef, calendarOptions, publicSettings } = useBookingCalendarOptions({
    compact: true,
    eventClick: onEventClick,
    filters: filters,
});

function onFilter() {
    const api = calendarRef.value?.getApi();
    if (api) {
        api.refetchEvents();
    }
}

// --- Dynamic draft preview on the calendar ---
// Build a client-only event that follows the form's start/end/title without affecting save logic
const draftSourceId = 'draft-preview';

function getDraftEvent() {
    const start = (form as any).start_at as string;
    const end = (form as any).end_at as string;
    if (!start || !end) return null;
    const startIso = toIso(start);
    const endIso = toIso(end);
    // basic guard: ensure end is after start
    if (!startIso || !endIso || new Date(startIso).getTime() >= new Date(endIso).getTime()) return null;

    const title = (form as any).title as string;
    // Use selected color or default blue
    const color = (form as any).color || '#3B82F6';

    return {
        id: 'draft',
        title: title ? `Preview: ${title}` : 'Preview timeslot',
        start: startIso,
        end: endIso,
        display: 'block',
        backgroundColor: color,
        borderColor: color,
        classNames: ['bh-draft-preview', 'animate-pulse'],
        editable: false,
    } as any;
}

function refetchAllEvents() {
    const api = calendarRef.value?.getApi?.();
    if (api) api.refetchEvents();
}

onMounted(() => {
    const api = calendarRef.value?.getApi?.();
    if (!api) return;
    // Add a function-based source so we can recalc the draft on each refetch
    api.addEventSource({
        id: draftSourceId,
        events: (_info: any, successCallback: (events: any[]) => void) => {
            const ev = getDraftEvent();
            successCallback(ev ? [ev] : []);
        },
    });
});


// Update preview as the user types/changes times or color
watch(() => [(form as any).start_at, (form as any).end_at, (form as any).title, (form as any).color], () => {
    refetchAllEvents();
});

// Proceed with save ignoring conflicts (used by modal "Continue")
function submitAnyway() {
    form.transform((data) => {
        const payload = {
            ...data,
            start_at: toIso((data as any).start_at),
            end_at: toIso((data as any).end_at),
        };
        return payload;
    });
    form.post('/timeslots', {
        onFinish: () => {
            form.transform((d) => d as any);
        },
    });
}
</script>

<template>
    <Head title="Create Timeslot" />
    <BasicLayout>
        <section class="mx-auto max-w-[1600px] p-6">
            <h1 class="text-2xl font-semibold">Create a Timeslot</h1>
            <p class="mt-2 text-muted-foreground">Enter the basic details for this availability window.</p>

            <div v-if="conflictCheckError" class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800">
                {{ conflictCheckError }}
            </div>

            <!-- Reference Calendar -->
            <div class="mt-6">
                <CalendarFilters v-model="filters" @filter="onFilter" />
            </div>
            <div class="mt-4 rounded-lg border bg-background p-2 shadow-sm min-w-0">
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
                    <p v-if="selected?.trainer_label" class="text-sm text-muted-foreground">Trainers: {{ selected?.trainer_label }}</p>
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

            <!-- Conflicts Warning Modal -->
            <div v-if="conflictModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="conflictModalOpen = false"></div>
                <div class="relative z-10 w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
                    <h2 class="text-xl font-semibold">Potential conflicts detected</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Review the details below. You can continue anyway or keep editing.</p>

                    <!-- Summary of the timeslot being created -->
                    <div class="mt-3 rounded-md border bg-gray-50 p-3 text-sm">
                        <div class="font-medium">You’re creating:</div>
                        <div>
                            <span class="font-medium">Title:</span>
                            <span>{{ currentTitle || 'Untitled timeslot' }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Start:</span>
                            <span>{{ currentStart ? toLocal(currentStart as any) : '—' }}</span>
                        </div>
                        <div>
                            <span class="font-medium">End:</span>
                            <span>{{ currentEnd ? toLocal(currentEnd as any) : '—' }}</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4 max-h-[60vh] overflow-auto">
                        <div v-if="props.warnings.timeslots && conflicts.timeslots.length" class="rounded-md border border-orange-200 bg-orange-50/30 p-3">
                            <h3 class="font-medium text-orange-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-range"><rect width="16" height="16" x="4" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M4 10h16"/><path d="M17 14h-6"/><path d="M13 18H7"/></svg>
                                Overlapping Timeslots ({{ conflicts.timeslots.length }})
                            </h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.timeslots" :key="'ts-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium">
                                        This timeslot {{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }} “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                    </div>
                                    <div class="mt-1 text-muted-foreground">
                                        <span class="font-medium">{{ toLocal(t.start_at) }}</span>
                                        →
                                        <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                                    </div>
                                    <div v-if="t.trainer_names && t.trainer_names.length" class="mt-1 text-muted-foreground">
                                        <span class="font-medium">Trainers:</span> {{ (t.trainer_names || []).join(', ') }}
                                    </div>
                                    <div v-if="t.service_name" class="text-muted-foreground">
                                        <span class="font-medium">Service:</span> {{ t.service_name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="props.warnings.trainers && conflicts.trainers.length" class="rounded-md border border-orange-200 bg-orange-50/30 p-3">
                            <h3 class="font-medium text-orange-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                Trainer Overlaps ({{ conflicts.trainers.length }})
                            </h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.trainers" :key="'tr-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium text-orange-700">Trainer is already busy during this time.</div>
                                    <div class="mt-1">
                                        Busy in “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                    </div>
                                    <div v-if="t.trainers && t.trainers.length" class="text-sm text-muted-foreground">Trainers: {{ t.trainers.map((x: any) => x.name).join(', ') }}</div>
                                    <div class="mt-1 text-muted-foreground text-xs">
                                        {{ toLocal(t.start_at) }} → {{ toLocal(t.end_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="props.warnings.horses && conflicts.horses.length" class="rounded-md border border-orange-200 bg-orange-50/30 p-3">
                            <h3 class="font-medium text-orange-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                Horse Overlaps ({{ conflicts.horses.length }})
                            </h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.horses" :key="'h-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium text-orange-700">
                                        Horse{{ (t.horses?.length ?? 0) > 1 ? 's' : '' }} {{ t.horses?.map((h: any) => h.name).join(', ') || '#' + t.id }} unavailable.
                                    </div>
                                    <div class="mt-1">
                                        Already assigned to “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                    </div>
                                    <div class="mt-1 text-muted-foreground text-xs">
                                        {{ toLocal(t.start_at) }} → {{ toLocal(t.end_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="props.warnings.horse_cooldown && conflicts.cooldowns.length" class="rounded-md border border-orange-200 bg-orange-50/30 p-3">
                            <h3 class="font-medium text-orange-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-timer"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>
                                Horse Cooldown Violations ({{ conflicts.cooldowns.length }})
                            </h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="(c, idx) in conflicts.cooldowns" :key="'cd-' + idx" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium text-orange-700">
                                        <span class="font-semibold">{{ c.horse.name }}</span> needs more recovery time.
                                    </div>
                                    <div class="mt-1">
                                        Needs <span class="font-semibold">{{ c.cooldown_text }}</span> cooldown after “<span class="font-semibold">{{ c.title || 'Untitled' }}</span>”.
                                    </div>
                                    <div class="mt-1 text-muted-foreground text-xs">
                                        Previous slot: {{ toLocal(c.start_at) }} → {{ toLocal(c.end_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" class="rounded-md border px-4 py-2" @click="conflictModalOpen = false">Keep Editing</button>
                        <Button type="button" class="bg-red-600 hover:bg-red-700 text-white" @click="() => { conflictModalOpen = false; submitAnyway(); }">Continue Anyway</Button>
                    </div>
                </div>
            </div>

            <form class="mt-6 flex flex-col lg:flex-row gap-8" @submit.prevent="submit">
                <div class="grow space-y-5">
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
                            <Label for="start_at" :class="{ 'text-red-600': dateValidationError }">Start</Label>
                            <Input id="start_at" name="start_at" v-model="form.start_at" type="datetime-local" required :class="{ 'border-red-500': dateValidationError }" />
                            <InputError :message="form.errors.start_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_at" :class="{ 'text-red-600': dateValidationError }">End</Label>
                            <Input id="end_at" name="end_at" v-model="form.end_at" type="datetime-local" required :class="{ 'border-red-500': dateValidationError }" />
                            <InputError :message="form.errors.end_at" />
                        </div>
                    </div>
                    <div v-if="dateValidationError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                        {{ dateValidationError }}
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
                            <LocationTypeahead
                                input-id="location_id"
                                label="Location (optional)"
                                placeholder="Type to search locations"
                                v-model="form.location_id"
                                :initial-name="presetInitialLocationName"
                            />
                            <InputError :message="(form.errors as any).location_id" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <TrainerMultiTypeahead
                            input-id="trainer_ids"
                            label="Trainers (optional)"
                            placeholder="Type to search and add trainers"
                            v-model="(form as any).trainer_ids"
                            :initial="presetInitialTrainers"
                            :class="{ 'border-orange-400 rounded-md border-2 p-1': props.warnings.trainers && conflicts.trainers.length }"
                        />
                        <p v-if="props.warnings.trainers && conflicts.trainers.length" class="text-xs text-orange-600 font-medium">Potential trainer conflict detected.</p>
                        <InputError :message="(form.errors as any)['trainer_ids.*']" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="color">Color (optional)</Label>
                        <input id="color" name="color" type="color" v-model="(form as any).color" class="h-10 w-24 cursor-pointer rounded-md border p-1" />
                        <p class="text-xs text-muted-foreground">Choose an event color for calendars. Defaults to blue if left empty.</p>
                        <InputError :message="(form.errors as any).color" />
                    </div>

                    <div class="grid gap-2">
                        <HorseTypeahead
                            input-id="horse_ids"
                            label="Horses (optional)"
                            placeholder="Type to search and add horses"
                            v-model="form.horse_ids"
                            :initial="presetInitialHorses"
                            :class="{ 'border-orange-400 rounded-md border-2 p-1': (props.warnings.horses && conflicts.horses.length) || (props.warnings.horse_cooldown && conflicts.cooldowns.length) }"
                        />
                        <p v-if="(props.warnings.horses && conflicts.horses.length) || (props.warnings.horse_cooldown && conflicts.cooldowns.length)" class="text-xs text-orange-600 font-medium">Potential horse conflict or cooldown violation detected.</p>
                        <p class="text-xs text-muted-foreground">Selected horses will be linked to this timeslot and used for overlap warnings.</p>
                        <InputError :message="form.errors['horse_ids.*'] as any" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <div class="mr-auto text-xs text-muted-foreground">
                            <span v-if="!conflictCheckError && !hasAnyRelevantConflicts">No conflicts returned or warnings disabled.</span>
                            <span v-else-if="hasAnyRelevantConflicts" class="text-orange-600 font-medium">Conflicts detected ({{ conflictSummary }}) — you will be prompted before saving.</span>
                            <span v-else-if="conflictCheckError">Conflict check failed — fix and try again, or use Continue in the modal after a successful check.</span>
                        </div>
                        <Button type="submit" :disabled="form.processing || checkingConflicts || !!dateValidationError">
                            {{ form.processing || checkingConflicts ? 'Saving…' : 'Save Timeslot' }}
                        </Button>
                    </div>
                </div>

                <div v-if="publicSettings?.show_event_feed" class="w-full lg:w-[350px] shrink-0">
                    <TimeslotSidebar />
                </div>
            </form>
        </section>
    </BasicLayout>
</template>

<style>
</style>
