<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';
import TrainerTypeahead from '@/components/TrainerTypeahead.vue';
import HorseTypeahead from '@/components/HorseTypeahead.vue';
import LocationTypeahead from '@/components/LocationTypeahead.vue';

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
  trainer_name: string | null;
  location_id: number | null;
  horse_ids: number[];
  horses?: { id: number; name: string; breed?: string | null; photo_url?: string | null }[];
};

interface Props {
  timeslot: Timeslot;
}

const props = defineProps<Props>();

// Format incoming ISO strings to the HTML5 datetime-local format (YYYY-MM-DDTHH:mm)
function toInputDateTime(value: string | Date | null | undefined): string {
  if (!value) return '';
  const d = typeof value === 'string' ? new Date(value) : value;
  if (!d || Number.isNaN(d.getTime())) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  const yyyy = d.getFullYear();
  const mm = pad(d.getMonth() + 1);
  const dd = pad(d.getDate());
  const hh = pad(d.getHours());
  const mi = pad(d.getMinutes());
  return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}

const form = useForm<Pick<Timeslot, 'title' | 'description' | 'start_at' | 'end_at' | 'capacity' | 'is_group' | 'price' | 'service_name' | 'trainer_name' | 'location_id' | 'horse_ids'>>({
  title: props.timeslot.title ?? '',
  description: props.timeslot.description ?? '',
  start_at: toInputDateTime(props.timeslot.start_at),
  end_at: toInputDateTime(props.timeslot.end_at),
  capacity: props.timeslot.capacity ?? 1,
  is_group: props.timeslot.is_group,
  price: props.timeslot.price ?? 0,
  service_name: props.timeslot.service_name ?? null,
  trainer_name: props.timeslot.trainer_name ?? null,
  location_id: props.timeslot.location_id ?? null,
  horse_ids: Array.isArray(props.timeslot.horse_ids) ? [...props.timeslot.horse_ids] : [],
});

function submit() {
  // Normalize datetime-local (no TZ) -> ISO8601 (UTC) to preserve correct instant on server
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
      // Reset transform to avoid affecting future submissions
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
    <section class="mx-auto max-w-2xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Timeslot</h1>
        <a href="/dashboard/timeslots" class="text-sm text-blue-600 hover:underline">Back to Timeslots</a>
      </div>
      <p class="mt-2 text-muted-foreground">Update the details for this timeslot.</p>

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
          <div class="grid gap-2">
            <TrainerTypeahead
              input-id="trainer_name"
              label="Trainer (label)"
              placeholder="Type to search trainers or enter a new name"
              v-model="(form.trainer_name as any)"
              @select="(t) => (form.trainer_name = t.name as any)"
            />
            <InputError :message="form.errors.trainer_name" />
          </div>
        </div>

        <div class="grid gap-2">
          <LocationTypeahead
            input-id="location_id"
            label="Location (optional)"
            placeholder="Type to search locations"
            v-model="(form.location_id as any)"
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
