<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import { useBookingCalendarOptions } from '@/composables/useBookingCalendar';
import { ref, computed, onMounted, watch } from 'vue';
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

const form = useForm<FormData>({
    title: '',
    description: '',
    start_at: '',
    end_at: '',
    capacity: 1,
    price: 0,
    service_name: null,
    trainer_ids: [],
    location_id: null,
    horse_ids: [],
    color: '#3B82F6',
});

type Warnings = { trainers: boolean; horses: boolean; timeslots: boolean };
const props = defineProps<{ warnings: Warnings }>();

const checkingConflicts = ref(false);
const conflictCheckError = ref<string | null>(null);
const conflictModalOpen = ref(false);
const conflicts = ref<{ timeslots: any[]; trainers: any[]; horses: any[] }>({ timeslots: [], trainers: [], horses: [] });
const presetInitialHorses = ref<any[]>([]);

function toIso(value: string) {
    if (!value) return value as any;
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? (value as any) : d.toISOString();
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
        (props.warnings.horses && c.horses.length > 0)
    );
});

async function checkConflicts(): Promise<boolean> {
    checkingConflicts.value = true;
    conflictCheckError.value = null;
    try {
        const payload = {
            start_at: toIso((form as any).start_at as unknown as string),
            end_at: toIso((form as any).end_at as unknown as string),
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
            conflicts.value = { timeslots: [], trainers: [], horses: [] };
            return false;
        }
        const data = await res.json();
        conflicts.value = data?.conflicts ?? { timeslots: [], trainers: [], horses: [] };
        return hasAnyRelevantConflicts.value;
    } catch (e) {
        // If conflict check fails, do not auto‑proceed; require explicit user action
        console.error('Conflict check error', e);
        conflictCheckError.value = 'Unable to run conflict warnings at the moment. Please try again, or save only if you are sure.';
        conflicts.value = { timeslots: [], trainers: [], horses: [] };
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

    form.transform((data) => ({
        ...data,
        start_at: toIso((data as any).start_at),
        end_at: toIso((data as any).end_at),
    }));

    form.post('/timeslots', {
        onFinish: () => {
            form.transform((d) => d as any);
        },
    });
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

const { calendarRef, calendarOptions } = useBookingCalendarOptions({ compact: true, eventClick: onEventClick });

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

    // Prefill from preset when provided as ?preset=ID
    tryPrefillFromPreset();
});

async function tryPrefillFromPreset() {
    const sp = new URLSearchParams(window.location.search);
    const id = sp.get('preset');
    if (!id) return;
    try {
        const res = await fetch(`/dashboard/timeslots/presets/${encodeURIComponent(id)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) return;
        const data = await res.json();
        // Fill relevant fields, leaving start/end times untouched
        (form as any).title = data.title ?? '';
        (form as any).description = data.description ?? '';
        (form as any).capacity = data.capacity ?? 1;
        (form as any).price = data.price ?? 0;
        (form as any).service_name = data.service_name ?? null;
        (form as any).trainer_ids = Array.isArray(data.trainer_ids) ? data.trainer_ids : [];
        (form as any).horse_ids = Array.isArray(data.horse_ids) ? data.horse_ids : [];
        (form as any).color = data.color ?? null;
        presetInitialHorses.value = Array.isArray(data.horses) ? data.horses : [];
    } catch {
        // ignore
    }
}

// Update preview as the user types/changes times or color
watch(() => [(form as any).start_at, (form as any).end_at, (form as any).title, (form as any).color], () => {
    refetchAllEvents();
});

// Proceed with save ignoring conflicts (used by modal "Continue")
function submitAnyway() {
    form.transform((data) => ({
        ...data,
        start_at: toIso((data as any).start_at),
        end_at: toIso((data as any).end_at),
    }));
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
        <section class="mx-auto max-w-5xl p-6">
            <h1 class="text-2xl font-semibold">Create a Timeslot</h1>
            <p class="mt-2 text-muted-foreground">Enter the basic details for this availability window.</p>

            <div v-if="conflictCheckError" class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800">
                {{ conflictCheckError }}
            </div>

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
                        <div v-if="props.warnings.timeslots && conflicts.timeslots.length" class="rounded-md border p-3">
                            <h3 class="font-medium">Overlapping Timeslots ({{ conflicts.timeslots.length }})</h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.timeslots" :key="'ts-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium">
                                        This timeslot {{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }} “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                    </div>
                                    <div class="mt-1 text-muted-foreground">
                                        <span class="font-medium">{{ toLocal(t.start_at) }}</span>
                                        →
                                        <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                                        — your timeslot <span class="font-medium">{{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }}</span> this.
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

                        <div v-if="props.warnings.trainers && conflicts.trainers.length" class="rounded-md border p-3">
                            <h3 class="font-medium">Trainer Overlaps ({{ conflicts.trainers.length }})</h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.trainers" :key="'tr-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium">Trainer conflict in “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”.</div>
                                    <div v-if="t.trainers && t.trainers.length" class="text-sm text-muted-foreground">Trainers: {{ t.trainers.map((x: any) => x.name).join(', ') }}</div>
                                    <div class="mt-1 text-muted-foreground">
                                        <span class="font-medium">{{ toLocal(t.start_at) }}</span>
                                        →
                                        <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                                        — your timeslot <span class="font-medium">{{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }}</span> this.
                                    </div>
                                    <div v-if="t.service_name" class="mt-1 text-muted-foreground">
                                        <span class="font-medium">Service:</span> {{ t.service_name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="props.warnings.horses && conflicts.horses.length" class="rounded-md border p-3">
                            <h3 class="font-medium">Horse Overlaps ({{ conflicts.horses.length }})</h3>
                            <div class="mt-2 grid gap-2">
                                <div v-for="t in conflicts.horses" :key="'h-' + t.id" class="rounded-md border bg-white p-3 text-sm shadow-sm">
                                    <div class="font-medium">
                                        Horse{{ (t.horses?.length ?? 0) > 1 ? 's' : '' }} {{ t.horses?.map((h: any) => h.name).join(', ') || '#' + t.id }}
                                    </div>
                                    <div class="mt-1 text-muted-foreground">
                                        Already assigned to “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                        from <span class="font-medium">{{ toLocal(t.start_at) }}</span> to <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                                        — your timeslot <span class="font-medium">{{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }}</span> this.
                                    </div>
                                    <div v-if="t.service_name" class="mt-1 text-muted-foreground">
                                        <span class="font-medium">Service:</span> {{ t.service_name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" class="rounded-md border px-4 py-2" @click="conflictModalOpen = false">Keep Editing</button>
                        <Button type="button" class="bg-red-600 hover:bg-red-700" @click="() => { conflictModalOpen = false; submitAnyway(); }">Continue</Button>
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
                        <TrainerMultiTypeahead
                            input-id="trainer_ids"
                            label="Trainers (optional)"
                            placeholder="Type to search and add trainers"
                            v-model="(form as any).trainer_ids"
                        />
                        <InputError :message="(form.errors as any)['trainer_ids.*']" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <LocationTypeahead
                        input-id="location_id"
                        label="Location (optional)"
                        placeholder="Type to search locations"
                        v-model="form.location_id"
                    />
                    <InputError :message="(form.errors as any).location_id" />
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
                    />
                    <p class="text-xs text-muted-foreground">Selected horses will be linked to this timeslot and used for overlap warnings.</p>
                    <InputError :message="form.errors['horse_ids.*'] as any" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <div class="mr-auto text-xs text-muted-foreground">
                        <span v-if="!conflictCheckError && !hasAnyRelevantConflicts">No conflicts returned or warnings disabled.</span>
                        <span v-else-if="hasAnyRelevantConflicts">Conflicts detected — you will be prompted before saving.</span>
                        <span v-else-if="conflictCheckError">Conflict check failed — fix and try again, or use Continue in the modal after a successful check.</span>
                    </div>
                    <Button type="submit" :disabled="form.processing || checkingConflicts">
                        {{ (form.processing || checkingConflicts) ? 'Saving…' : 'Save Timeslot' }}
                    </Button>
                </div>
            </form>
        </section>
    </BasicLayout>
</template>

<style>
</style>
