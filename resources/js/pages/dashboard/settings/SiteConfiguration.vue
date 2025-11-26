<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { dashboard, home } from '@/routes';

type PageProps = {
  config: {
    site_name: string;
    booking_open_time: string; // HH:MM:SS
    booking_close_time: string; // HH:MM:SS
    logo_url?: string | null;
  }
}

const page = usePage<PageProps>();
const form = ref({
  site_name: page.props.config.site_name ?? 'Booking Homse',
  booking_open_time: (page.props.config.booking_open_time ?? '09:00:00').slice(0,5),
  booking_close_time: (page.props.config.booking_close_time ?? '19:00:00').slice(0,5),
  logo: null as File | null,
});

// UX state for upload/progress/feedback
const isSubmitting = ref(false);
const progress = ref(0); // 0-100
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Home', href: home().url },
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Settings', href: '/settings' },
  { title: 'Site Configuration', href: '/dashboard/settings/site' },
];

const isUploadingLogo = computed(() => !!form.value.logo);

const logoPreview = computed(() => {
  if (form.value.logo) return URL.createObjectURL(form.value.logo);
  return page.props.config.logo_url ?? null;
});

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  form.value.logo = file as any;
}

function onSubmit(e: Event) {
  e.preventDefault();
  const data = new FormData();
  // Ensure we always send non-empty strings for required fields
  const siteName = (form.value.site_name ?? '').toString().trim() || 'Booking Homse';
  let openTime = (form.value.booking_open_time ?? '').toString().trim();
  let closeTime = (form.value.booking_close_time ?? '').toString().trim();
  // Normalize accidental seconds (HH:MM:SS -> HH:MM)
  if (/^\d{2}:\d{2}:\d{2}$/.test(openTime)) openTime = openTime.slice(0, 5);
  if (/^\d{2}:\d{2}:\d{2}$/.test(closeTime)) closeTime = closeTime.slice(0, 5);
  if (!/^\d{2}:\d{2}$/.test(openTime)) openTime = (page.props.config.booking_open_time || '09:00:00').slice(0, 5);
  if (!/^\d{2}:\d{2}$/.test(closeTime)) closeTime = (page.props.config.booking_close_time || '19:00:00').slice(0, 5);

  data.append('site_name', siteName);
  data.append('booking_open_time', openTime);
  data.append('booking_close_time', closeTime);
  if (form.value.logo) data.append('logo', form.value.logo);
  // Use method spoofing for robust multipart handling on Laravel
  data.append('_method', 'PATCH');

  successMessage.value = null;
  errorMessage.value = null;
  isSubmitting.value = true;
  progress.value = 0;

  // Fallback fake progress in case native upload progress is not reported
  let fakeTimer: number | null = null;
  if (isUploadingLogo.value) {
    fakeTimer = window.setInterval(() => {
      if (!isSubmitting.value) {
        if (fakeTimer) window.clearInterval(fakeTimer);
        return;
      }
      // Smoothly approach 85% until real progress or finish
      if (progress.value < 85) progress.value += 3;
    }, 200);
  }

  // Prefer POST + _method=PATCH for multipart to avoid edge cases with true PATCH
  router.post('/dashboard/settings/site', data, {
    forceFormData: true,
    preserveScroll: true,
    onStart: () => {
      // ensure we start at 5% so the bar is visible instantly (only for uploads)
      if (isUploadingLogo.value && progress.value < 5) progress.value = 5;
    },
    onProgress: (event) => {
      if (isUploadingLogo.value && event?.percentage != null) {
        progress.value = Math.min(99, Math.max(progress.value, Math.round(event.percentage)));
      }
    },
    onSuccess: () => {
      successMessage.value = 'Settings saved successfully.';
      // reset local file input so preview shows persisted logo from props
      form.value.logo = null;
      // Ensure we fetch the latest config props from server so the UI reflects DB values
      router.reload({ only: ['config'] });
    },
    onError: (errors) => {
      // Show a generic error banner if specific field errors exist; they will show inline too
      errorMessage.value = 'Please fix the errors below and try again.';
      console.error('Site settings save failed', errors);
    },
    onFinish: () => {
      isSubmitting.value = false;
      if (isUploadingLogo.value) {
        progress.value = 100;
        if (fakeTimer) window.clearInterval(fakeTimer);
        // hide the bar after a short delay
        window.setTimeout(() => (progress.value = 0), 400);
      }
    },
  });
}
</script>

<template>
  <Head title="Dashboard — Site Configuration" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <section class="mx-auto max-w-3xl p-6">
      <h1 class="text-2xl font-semibold">Site Configuration</h1>
      <p class="mt-2 text-muted-foreground">Manage global settings such as site name, booking hours, and branding.</p>

      <form class="mt-6 space-y-6" @submit="onSubmit">
        <!-- Global feedback banners -->
        <div v-if="successMessage" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-green-800">
          {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800">
          {{ errorMessage }}
        </div>

        <!-- Upload progress bar (only when a logo file is uploading) -->
        <div v-if="(isSubmitting && isUploadingLogo) || progress > 0" class="h-2 w-full overflow-hidden rounded bg-gray-200">
          <div class="h-2 bg-blue-600 transition-all" :style="{ width: Math.max(progress, 5) + '%' }"></div>
        </div>

        <div class="space-y-2">
          <label class="block text-sm font-medium">Site Name</label>
          <input v-model="form.site_name" type="text" class="w-full rounded-md border px-3 py-2" />
          <p v-if="$page.props.errors?.site_name" class="text-sm text-red-600">{{ $page.props.errors.site_name }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-medium">Booking Opens</label>
            <input v-model="form.booking_open_time" type="time" class="w-full rounded-md border px-3 py-2" />
            <p v-if="$page.props.errors?.booking_open_time" class="text-sm text-red-600">{{ $page.props.errors.booking_open_time }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium">Booking Closes</label>
            <input v-model="form.booking_close_time" type="time" class="w-full rounded-md border px-3 py-2" />
            <p v-if="$page.props.errors?.booking_close_time" class="text-sm text-red-600">{{ $page.props.errors.booking_close_time }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="block text-sm font-medium">Site Logo</label>
          <input type="file" accept="image/*" @change="onFileChange" />
          <p v-if="$page.props.errors?.logo" class="text-sm text-red-600">{{ $page.props.errors.logo }}</p>
          <div v-if="logoPreview" class="mt-2">
            <img :src="logoPreview" alt="Logo preview" class="h-12 w-auto" />
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-60"
            :disabled="isSubmitting"
          >
            <span v-if="!isSubmitting">Save Settings</span>
            <span v-else class="inline-flex items-center gap-2">
              <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
              </svg>
              Saving...
            </span>
          </button>
        </div>
      </form>
    </section>
  </AppLayout>
  </template>
