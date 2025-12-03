<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';
import { computed } from 'vue';

type Booking = {
  id: number;
  timeslot_id: number;
  user_id: number | null;
  horse_id: number | null;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed' | 'no_show';
  payment_status: 'unpaid' | 'paid' | 'refunded';
  cancel_reason: string | null;
};

type Timeslot = { id: number; title: string | null; start_at: string };
type User = { id: number; name: string };
type Horse = { id: number; name: string };

interface Props {
  booking: Booking;
  users: User[];
  timeslots: Timeslot[];
  horses: Horse[];
}

const props = defineProps<Props>();

const form = useForm<{
  timeslot_id: number;
  user_id: number | null;
  horse_id: number | null;
  status: Booking['status'];
  payment_status: Booking['payment_status'];
  cancel_reason: string | null;
}>({
  timeslot_id: props.booking.timeslot_id,
  user_id: props.booking.user_id,
  horse_id: props.booking.horse_id,
  status: props.booking.status,
  payment_status: props.booking.payment_status,
  cancel_reason: props.booking.cancel_reason ?? null,
});

const isCancelled = computed(() => form.status === 'cancelled');

function submit() {
  // Simple PUT
  // Note: payment paid_at and cancellation timestamps handled server-side
  form.put(`/bookings/${props.booking.id}`);
}

function timeslotLabel(t: Timeslot) {
  const when = new Date(t.start_at);
  const day = when.toLocaleString();
  return t.title ? `${t.title} — ${day}` : day;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Timeslots', href: '/dashboard/timeslots' },
  { title: 'Edit Booking', href: `/dashboard/bookings/${props.booking.id}/edit` },
];
</script>

<template>
  <Head :title="`Edit Booking #${props.booking.id}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-2xl p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Booking</h1>
        <a href="/dashboard/timeslots" class="text-sm text-blue-600 hover:underline">Back to Timeslots</a>
      </div>
      <p class="mt-2 text-muted-foreground">Reschedule or adjust details for this booking.</p>

      <form class="mt-6 grid gap-5" @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="timeslot_id">Timeslot</Label>
          <select
            id="timeslot_id"
            name="timeslot_id"
            v-model.number="form.timeslot_id"
            class="h-10 w-full rounded-md border px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            required
          >
            <option v-for="t in props.timeslots" :key="t.id" :value="t.id">{{ timeslotLabel(t) }}</option>
          </select>
          <InputError :message="form.errors.timeslot_id" />
        </div>

        <div class="grid gap-2">
          <Label for="user_id">Customer</Label>
          <select
            id="user_id"
            name="user_id"
            v-model.number="(form.user_id as any)"
            class="h-10 w-full rounded-md border px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option :value="null">— Unassigned —</option>
            <option v-for="u in props.users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
          <InputError :message="form.errors.user_id" />
        </div>

        <div class="grid gap-2">
          <div class="flex items-center justify-between">
            <Label for="horse_id">Horse</Label>
            <span class="text-xs text-amber-700">Be careful of double schedules!</span>
          </div>
          <select
            id="horse_id"
            name="horse_id"
            v-model.number="(form.horse_id as any)"
            class="h-10 w-full rounded-md border px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option :value="null">— Unassigned —</option>
            <option v-for="h in props.horses" :key="h.id" :value="h.id">{{ h.name }}</option>
          </select>
          <InputError :message="form.errors.horse_id" />
        </div>

        <div class="grid gap-2">
          <Label for="status">Status</Label>
          <select
            id="status"
            name="status"
            v-model="form.status"
            class="h-10 w-full rounded-md border px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            required
          >
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="cancelled">Cancelled</option>
            <option value="completed">Completed</option>
            <option value="no_show">No Show</option>
          </select>
          <InputError :message="form.errors.status" />
        </div>

        <div class="grid gap-2">
          <Label for="payment_status">Payment Status</Label>
          <select
            id="payment_status"
            name="payment_status"
            v-model="form.payment_status"
            class="h-10 w-full rounded-md border px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
            required
          >
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="refunded">Refunded</option>
          </select>
          <InputError :message="form.errors.payment_status" />
          <p class="text-xs text-muted-foreground">If set to Paid, the paid timestamp will be set automatically.</p>
        </div>

        <div class="grid gap-2">
          <Label for="cancel_reason">Cancel Reason</Label>
          <textarea
            id="cancel_reason"
            name="cancel_reason"
            v-model="form.cancel_reason"
            :disabled="!isCancelled"
            class="min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:cursor-not-allowed disabled:bg-gray-100"
            placeholder="Reason for cancellation (only when status is Cancelled)"
          />
          <InputError :message="form.errors.cancel_reason" />
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
