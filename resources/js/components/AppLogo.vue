<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Read once from Inertia shared props to avoid per-navigation fetches that cause flicker
const page = usePage();
const siteName = computed(() => page.props.site?.site_name ?? 'Booking Homse Website');
const logoUrl = computed<string | null>(() => page.props.site?.logo_url ?? null);
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <img v-if="logoUrl" :src="logoUrl" alt="Site Logo" class="h-5 w-5 object-contain" width="20" height="20" decoding="async" />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteName }}</span>
    </div>
</template>
