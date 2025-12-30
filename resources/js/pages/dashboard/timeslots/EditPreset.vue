<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import TrainerMultiTypeahead from '@/components/TrainerMultiTypeahead.vue';
import HorseTypeahead from '@/components/HorseTypeahead.vue';
import type { BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

const props = defineProps<{ preset: any; horse_ids: number[]; trainer_ids: number[] }>();

const form = useForm({
  preset_title: props.preset?.preset_title ?? '',
  preset_description: props.preset?.preset_description ?? '',
  title: props.preset?.title ?? '',
  description: props.preset?.description ?? '',
  capacity: props.preset?.capacity ?? 1,
  is_group: props.preset?.is_group ?? null,
  price: props.preset?.price ?? 0,
  service_name: props.preset?.service_name ?? null,
  trainer_ids: props.trainer_ids ?? [],
  trainer_id: props.preset?.trainer_id ?? null,
  trainer_name: props.preset?.trainer_name ?? null,
  location_id: props.preset?.location_id ?? null,
  horse_ids: props.horse_ids ?? [],
  color: props.preset?.color ?? null,
  _method: 'put' as const,
});

function submit() {
  form.post(`/dashboard/timeslots/presets/${props.preset.id}`, { preserveScroll: true });
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Timeslots', href: '/dashboard/timeslots' },
  { title: 'Presets', href: '/dashboard/timeslots/presets' },
  { title: 'Edit', href: `/dashboard/timeslots/presets/${props.preset.id}/edit` },
];
</script>

<template>
  <Head title="Edit Preset" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-4xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Preset</h1>
        <Link href="/dashboard/timeslots/presets" class="text-sm text-muted-foreground hover:underline">Back to Presets</Link>
      </div>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="preset_title">Preset Title</Label>
          <Input id="preset_title" v-model="form.preset_title" required />
          <InputError :message="(form.errors as any).preset_title" />
        </div>

        <div class="grid gap-2">
          <Label for="preset_description">Preset Description</Label>
          <textarea id="preset_description" v-model="(form as any).preset_description" class="min-h-24 w-full rounded-md border px-3 py-2 text-sm" />
          <InputError :message="(form.errors as any).preset_description" />
        </div>

        <div class="grid gap-2">
          <Label for="title">Default Timeslot Title</Label>
          <Input id="title" v-model="form.title" required />
          <InputError :message="(form.errors as any).title" />
        </div>

        <div class="grid gap-2">
          <Label for="description">Default Timeslot Description</Label>
          <textarea id="description" v-model="(form as any).description" class="min-h-24 w-full rounded-md border px-3 py-2 text-sm" />
          <InputError :message="(form.errors as any).description" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="capacity">Capacity</Label>
            <Input id="capacity" v-model.number="(form as any).capacity" type="number" min="1" />
            <InputError :message="(form.errors as any).capacity" />
          </div>
          <div class="grid gap-2">
            <Label for="price">Price</Label>
            <Input id="price" v-model.number="(form as any).price" type="number" min="0" step="0.01" />
            <InputError :message="(form.errors as any).price" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="service_name">Service (optional)</Label>
            <Input id="service_name" v-model="(form as any).service_name" />
            <InputError :message="(form.errors as any).service_name" />
          </div>
          <div class="grid gap-2">
            <TrainerMultiTypeahead
              input-id="trainer_ids"
              label="Default Trainers (optional)"
              placeholder="Type to search and add trainers"
              v-model="(form as any).trainer_ids"
              :initial="(props.preset?.trainers ?? []).map((t: any) => ({
                id: t.id,
                name: t.name,
                title: t.title,
                photo_url: t.photo_url
              }))"
            />
            <InputError :message="(form.errors as any).trainer_ids" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label for="color">Color (optional)</Label>
          <input id="color" name="color" type="color" v-model="(form as any).color" class="h-10 w-24 cursor-pointer rounded-md border p-1" />
          <InputError :message="(form.errors as any).color" />
        </div>

        <div class="grid gap-2">
          <HorseTypeahead
            input-id="horse_ids"
            label="Default Horses (optional)"
            placeholder="Type to search and add horses"
            v-model="(form as any).horse_ids"
            :initial="(props.preset?.horses ?? []).map((h: any) => ({ id: h.id, name: h.name, photo_url: h.photo_url ?? (h.photo_path ? `/storage/${h.photo_path}` : null), breed: h.breed ?? null }))"
          />
          <InputError :message="(form.errors as any)['horse_ids.*']" />
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save Changes' }}</Button>
        </div>
      </form>
    </section>
  </AppLayout>
</template>
