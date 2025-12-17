<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import TrainerTypeahead from '@/components/TrainerTypeahead.vue';
import HorseTypeahead from '@/components/HorseTypeahead.vue';
import type { BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

type FormData = {
  preset_title: string;
  preset_description: string | null;
  title: string;
  description: string | null;
  capacity: number | null;
  is_group: boolean | null;
  price: number | null;
  service_name: string | null;
  trainer_id: number | null;
  trainer_name: string | null;
  location_id: number | null;
  horse_ids: number[];
  color: string | null;
};

const form = useForm<FormData>({
  preset_title: '',
  preset_description: '',
  title: '',
  description: '',
  capacity: 1,
  is_group: null,
  price: 0,
  service_name: null,
  trainer_id: null,
  trainer_name: null,
  location_id: null,
  horse_ids: [],
  color: null,
});

function submit() {
  form.post('/dashboard/timeslots/presets', {
    preserveScroll: true,
  });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Timeslots', href: '/dashboard/timeslots' },
  { title: 'Presets', href: '/dashboard/timeslots/presets' },
  { title: 'Create', href: '/dashboard/timeslots/presets/create' },
];
</script>

<template>
  <Head title="Create Preset" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-4xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Preset</h1>
        <Link href="/dashboard/timeslots/presets" class="text-sm text-muted-foreground hover:underline">Back to Presets</Link>
      </div>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="preset_title">Preset Title</Label>
          <Input id="preset_title" v-model="form.preset_title" required placeholder="e.g., 60-min Private Lesson" />
          <InputError :message="(form.errors as any).preset_title" />
        </div>

        <div class="grid gap-2">
          <Label for="preset_description">Preset Description</Label>
          <textarea id="preset_description" v-model="form.preset_description as any" class="min-h-24 w-full rounded-md border px-3 py-2 text-sm" placeholder="Describe how and when you use this preset" />
          <InputError :message="(form.errors as any).preset_description" />
        </div>

        <div class="grid gap-2">
          <Label for="title">Default Timeslot Title</Label>
          <Input id="title" v-model="form.title" required placeholder="e.g., Private Lesson" />
          <InputError :message="(form.errors as any).title" />
        </div>

        <div class="grid gap-2">
          <Label for="description">Default Timeslot Description</Label>
          <textarea id="description" v-model="form.description as any" class="min-h-24 w-full rounded-md border px-3 py-2 text-sm" placeholder="Notes shown on the timeslot" />
          <InputError :message="(form.errors as any).description" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="capacity">Capacity</Label>
            <Input id="capacity" v-model.number="form.capacity" type="number" min="1" />
            <InputError :message="(form.errors as any).capacity" />
          </div>
          <div class="grid gap-2">
            <Label for="price">Price</Label>
            <Input id="price" v-model.number="form.price" type="number" min="0" step="0.01" />
            <InputError :message="(form.errors as any).price" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="service_name">Service (optional)</Label>
            <Input id="service_name" v-model="form.service_name" placeholder="e.g., Lesson" />
            <InputError :message="(form.errors as any).service_name" />
          </div>
          <div class="grid gap-2">
            <TrainerTypeahead
              input-id="trainer_name"
              label="Trainer (optional)"
              placeholder="Type to search trainers or enter a new name"
              v-model="form.trainer_name"
              @select="(t) => (form.trainer_name = t.name)"
            />
            <InputError :message="(form.errors as any).trainer_name" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="color">Color (optional)</Label>
          <input id="color" name="color" type="color" v-model="(form as any).color" class="h-10 w-24 cursor-pointer rounded-md border p-1" />
          <p class="text-xs text-muted-foreground">Choose a color for calendar tint when deployed. Defaults to blue.</p>
          <InputError :message="(form.errors as any).color" />
        </div>

        <div class="grid gap-2">
          <HorseTypeahead input-id="horse_ids" label="Default Horses (optional)" placeholder="Type to search and add horses" v-model="form.horse_ids" />
          <InputError :message="(form.errors as any)['horse_ids.*']" />
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save Preset' }}</Button>
        </div>
      </form>
    </section>
  </AppLayout>
</template>
