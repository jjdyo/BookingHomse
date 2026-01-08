<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';
import { formatIsoToInputDateTime, normalizeDateTimeToIso } from '@/lib/datetime';
import TrainerMultiTypeahead from '@/components/TrainerMultiTypeahead.vue';
import HorseTypeahead from '@/components/HorseTypeahead.vue';
import LocationTypeahead from '@/components/LocationTypeahead.vue';
import { ref, computed } from 'vue';

type Timeslot = {
  id: number;
  title: string;
  description: string;
  start_at: string;
  end_at: string;
  capacity: number | null;
  is_group: boolean;
  price: number | string | null;
  service_name: string | null;
  trainer_ids: number[];
  trainers?: { id: number; name: string; title?: string | null; photo_url?: string | null }[];
  location_id: number | null;
  horse_ids: number[];
  horses?: { id: number; name: string; breed?: string | null; photo_url?: string | null }[];
};

interface Props {
  timeslot: Timeslot;
  warnings: { trainers: boolean; horses: boolean; horse_cooldown: boolean; timeslots: boolean };
}

const props = defineProps<Props>();

// Format incoming ISO strings to the HTML5 datetime-local format (YYYY-MM-DDTHH:mm)
function toInputDateTime(value: string | Date | null | undefined): string {
  return formatIsoToInputDateTime(value);
}

type FormData = {
  title: string;
  description: string;
  start_at: string;
  end_at: string;
  capacity: number | null;
  is_group: boolean;
  price: number | string | null;
  service_name: string | null;
  trainer_ids: number[];
  location_id: number | null;
  horse_ids: number[];
};

const form = useForm<FormData>({
  title: props.timeslot.title ?? '',
  description: props.timeslot.description ?? '',
  start_at: toInputDateTime(props.timeslot.start_at),
  end_at: toInputDateTime(props.timeslot.end_at),
  capacity: props.timeslot.capacity ?? 1,
  is_group: props.timeslot.is_group,
  price: props.timeslot.price ?? 0,
  service_name: props.timeslot.service_name ?? null,
  trainer_ids: Array.isArray(props.timeslot.trainer_ids) ? [...props.timeslot.trainer_ids] : [],
  location_id: props.timeslot.location_id ?? null,
  horse_ids: Array.isArray(props.timeslot.horse_ids) ? [...props.timeslot.horse_ids] : [],
});

const checkingConflicts = ref(false);
const conflictCheckError = ref<string | null>(null);
const conflictModalOpen = ref(false);
const conflicts = ref<{ timeslots: any[]; trainers: any[]; horses: any[]; cooldowns: any[] }>({
    timeslots: [],
    trainers: [],
    horses: [],
    cooldowns: [],
});

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

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

async function checkConflicts(): Promise<boolean> {
    checkingConflicts.value = true;
    conflictCheckError.value = null;
    try {
        const payload = {
            start_at: normalizeDateTimeToIso((form as any).start_at as unknown as string),
            end_at: normalizeDateTimeToIso((form as any).end_at as unknown as string),
            trainer_ids: (form as any).trainer_ids,
            horse_ids: (form as any).horse_ids,
            exclude_id: props.timeslot.id,
        } as any;

        const res = await fetch('/timeslots/check-conflicts', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            conflictCheckError.value = 'Unable to run conflict warnings.';
            conflicts.value = { timeslots: [], trainers: [], horses: [], cooldowns: [] };
            return false;
        }
        const data = await res.json();
        conflicts.value = data?.conflicts ?? { timeslots: [], trainers: [], horses: [], cooldowns: [] };
        return hasAnyRelevantConflicts.value;
    } catch (e) {
        conflictCheckError.value = 'Unable to run conflict warnings.';
        conflicts.value = { timeslots: [], trainers: [], horses: [], cooldowns: [] };
        return false;
    } finally {
        checkingConflicts.value = false;
    }
}

async function submit() {
    const willShowModal = await checkConflicts();
    if (willShowModal) {
        conflictModalOpen.value = true;
        return;
    }
    if (conflictCheckError.value) {
        return;
    }
    actualSubmit();
}

function actualSubmit() {
  const toIso = (value: string) => {
    if (!value) return value as any;
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? (value as any) : d.toISOString();
  };

  form.transform((data) => ({
    ...data,
    start_at: toIso((data as any).start_at),
    end_at: toIso((data as any).end_at),
  }));

  form.put(`/timeslots/${props.timeslot.id}`, {
    onFinish: () => {
      form.transform((d) => d as any);
    },
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Timeslots', href: '/dashboard/timeslots' },
  { title: 'Edit Timeslot', href: `/dashboard/timeslots/${props.timeslot.id}/edit` },
];
</script>

<template>
  <Head :title="`Edit Timeslot — ${props.timeslot.title}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:max-w-5xl lg:px-8">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Timeslot</h1>
        <a href="/dashboard/timeslots" class="text-sm text-blue-600 hover:underline">Back to Timeslots</a>
      </div>
      <p class="mt-2 text-muted-foreground">Update the details for this timeslot.</p>

      <div v-if="conflictCheckError" class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800">
          {{ conflictCheckError }}
      </div>

      <!-- Conflicts Warning Modal -->
      <div v-if="conflictModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/40" @click="conflictModalOpen = false"></div>
          <div class="relative z-10 w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl text-foreground">
              <h2 class="text-xl font-semibold">Potential conflicts detected</h2>
              <p class="mt-1 text-sm text-muted-foreground">Review the details below. You can continue anyway or keep editing.</p>

              <!-- Summary of the timeslot being updated -->
              <div class="mt-3 rounded-md border bg-muted/50 p-3 text-sm">
                  <div class="font-medium">You’re updating:</div>
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
                          <div v-for="t in conflicts.timeslots" :key="'ts-' + t.id" class="rounded-md border bg-card p-3 text-sm shadow-sm">
                              <div class="font-medium text-foreground">
                                  This timeslot {{ describeOverlap(currentStart as any, currentEnd as any, t.start_at, t.end_at) }} “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                              </div>
                              <div class="mt-1 text-muted-foreground">
                                  <span class="font-medium">{{ toLocal(t.start_at) }}</span>
                                  →
                                  <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                              </div>
                              <div v-if="t.trainer_names && t.trainer_names.length" class="mt-1 text-muted-foreground text-xs">
                                  <span class="font-medium">Trainers:</span> {{ (t.trainer_names || []).join(', ') }}
                              </div>
                          </div>
                      </div>
                  </div>

                  <div v-if="props.warnings.trainers && conflicts.trainers.length" class="rounded-md border p-3">
                      <h3 class="font-medium">Trainer Overlaps ({{ conflicts.trainers.length }})</h3>
                      <div class="mt-2 grid gap-2">
                          <div v-for="t in conflicts.trainers" :key="'tr-' + t.id" class="rounded-md border bg-card p-3 text-sm shadow-sm">
                              <div class="font-medium text-foreground">Trainer conflict in “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”.</div>
                              <div v-if="t.trainers && t.trainers.length" class="text-xs text-muted-foreground">Trainers: {{ t.trainers.map((x: any) => x.name).join(', ') }}</div>
                              <div class="mt-1 text-muted-foreground">
                                  <span class="font-medium">{{ toLocal(t.start_at) }}</span>
                                  →
                                  <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div v-if="props.warnings.horses && conflicts.horses.length" class="rounded-md border p-3">
                      <h3 class="font-medium">Horse Overlaps ({{ conflicts.horses.length }})</h3>
                      <div class="mt-2 grid gap-2">
                          <div v-for="t in conflicts.horses" :key="'h-' + t.id" class="rounded-md border bg-card p-3 text-sm shadow-sm">
                              <div class="font-medium text-foreground">
                                  Horse{{ (t.horses?.length ?? 0) > 1 ? 's' : '' }} {{ t.horses?.map((h: any) => h.name).join(', ') || '#' + t.id }}
                              </div>
                              <div class="mt-1 text-muted-foreground">
                                  Already assigned to “<span class="font-semibold">{{ t.title || 'Untitled' }}</span>”
                                  from <span class="font-medium">{{ toLocal(t.start_at) }}</span> to <span class="font-medium">{{ toLocal(t.end_at) }}</span>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div v-if="props.warnings.horse_cooldown && conflicts.cooldowns.length" class="rounded-md border p-3">
                      <h3 class="font-medium">Horse Cooldown Violations ({{ conflicts.cooldowns.length }})</h3>
                      <div class="mt-2 grid gap-2">
                          <div v-for="(c, idx) in conflicts.cooldowns" :key="'cd-' + idx" class="rounded-md border bg-card p-3 text-sm shadow-sm">
                              <div class="font-medium text-foreground">
                                  <span class="font-semibold">{{ c.horse.name }}</span> needs a <span class="font-semibold">{{ c.cooldown_text }}</span> cooldown.
                              </div>
                              <div class="mt-1 text-muted-foreground">
                                  Found nearby timeslot “<span class="font-semibold">{{ c.title || 'Untitled' }}</span>”
                                  from <span class="font-medium">{{ toLocal(c.start_at) }}</span> to <span class="font-medium">{{ toLocal(c.end_at) }}</span>.
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

              <div class="mt-6 flex items-center justify-end gap-3">
                  <button type="button" class="rounded-md border px-4 py-2" @click="conflictModalOpen = false">Keep Editing</button>
                  <Button type="button" class="bg-red-600 hover:bg-red-700 text-white" @click="() => { conflictModalOpen = false; actualSubmit(); }">Continue Anyway</Button>
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
            placeholder="What is included, who it's for, etc."
          />
          <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
          <div class="grid gap-2">
            <Label for="start_at">Start</Label>
            <Input id="start_at" name="start_at" type="datetime-local" v-model="form.start_at" required />
            <InputError :message="form.errors.start_at" />
          </div>
          <div class="grid gap-2">
            <Label for="end_at">End</Label>
            <Input id="end_at" name="end_at" type="datetime-local" v-model="form.end_at" required />
            <InputError :message="form.errors.end_at" />
          </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-3">
          <div class="grid gap-2">
            <Label for="capacity">Capacity</Label>
            <Input id="capacity" name="capacity" type="number" min="1" v-model.number="(form.capacity as any)" />
            <InputError :message="form.errors.capacity" />
          </div>
          <div class="grid gap-2">
            <Label for="price">Price</Label>
            <Input id="price" name="price" type="number" step="0.01" min="0" v-model.number="(form.price as any)" />
            <InputError :message="form.errors.price" />
          </div>
          <div class="flex items-center gap-2 pt-6">
            <input id="is_group" type="checkbox" v-model="form.is_group" class="h-4 w-4" />
            <Label for="is_group">Group Session</Label>
          </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
          <div class="grid gap-2">
            <Label for="service_name">Service (optional)</Label>
            <Input id="service_name" name="service_name" v-model="(form.service_name as any)" placeholder="e.g., Beginner Lesson" />
            <InputError :message="form.errors.service_name" />
          </div>
        </div>

          <div class="grid gap-2">
              <TrainerMultiTypeahead
                  input-id="trainer_ids"
                  label="Trainers (optional)"
                  placeholder="Type to search and add trainers"
                  v-model="(form.trainer_ids as any)"
                  :initial="(props.timeslot.trainers as any) || []"
              />
              <p class="text-xs text-muted-foreground">Selected trainers will be linked to this timeslot and considered for booking rules.</p>
              <InputError :message="(form.errors as any)['trainer_ids.*']" />
          </div>

        <div class="grid gap-2">
          <LocationTypeahead
            input-id="location_id"
            label="Location (optional)"
            placeholder="Type to search locations"
            v-model="(form.location_id as any)"
            :initialName="(props.timeslot as any).location_name || null"
          />
          <InputError :message="(form.errors as any).location_id" />
        </div>

        <div class="grid gap-2">
          <HorseTypeahead
            input-id="horse_ids"
            label="Horses (optional)"
            placeholder="Type to search and add horses"
            v-model="(form.horse_ids as any)"
            :initial="(props.timeslot as any).horses || []"
          />
          <p class="text-xs text-muted-foreground">Selected horses will be linked to this timeslot and considered for booking rules.</p>
          <InputError :message="(form.errors as any)['horse_ids.*'] as any" />
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
