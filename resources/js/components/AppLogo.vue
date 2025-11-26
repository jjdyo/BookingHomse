<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { onMounted, ref } from 'vue';

const siteName = ref('Booking Homse Website');
const logoUrl = ref<string | null>(null);

onMounted(async () => {
  try {
    const res = await fetch('/settings/public');
    if (!res.ok) return;
    const s = await res.json();
    if (s?.site_name) siteName.value = s.site_name;
    if (s?.logo_url) logoUrl.value = s.logo_url;
  } catch (e) {
    // ignore
  }
});
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <img v-if="logoUrl" :src="logoUrl" alt="Site Logo" class="h-5 w-5 object-contain" />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteName }}</span>
    </div>
</template>
