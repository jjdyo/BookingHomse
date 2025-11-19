<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

// Minimal theme toggle: cycles system -> light -> dark
const { appearance, updateAppearance } = useAppearance();

const nextAppearance = () => {
    const order = ['system', 'light', 'dark'] as const;
    const current = appearance.value ?? 'system';
    const idx = order.indexOf(current as (typeof order)[number]);
    const next = order[(idx + 1) % order.length];
    updateAppearance(next);
};

const appearanceLabel = computed(() => {
    switch (appearance.value) {
        case 'light':
            return 'Light';
        case 'dark':
            return 'Dark';
        default:
            return 'System';
    }
});
</script>

<template>
    <header class="w-full border-b border-sidebar-border/70 bg-white dark:bg-black">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 p-4">
            <div class="flex items-center">
                <Link href="/" class="flex items-center gap-2">
                    <AppLogo />
                </Link>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <Button asChild>
                    <Link href="/">Home</Link>
                </Button>
                <Button asChild>
                    <Link href="/about">About</Link>
                </Button>
                <Button asChild>
                    <Link href="/request-booking">Request Booking</Link>
                </Button>
                <!-- Appearance toggle: light/dark/system -->
                <Button
                    type="button"
                    variant="secondary"
                    class="ml-2"
                    title="Toggle appearance (System → Light → Dark)"
                    @click="nextAppearance"
                >
                    {{ appearanceLabel }}
                </Button>
            </nav>
        </div>
    </header>
</template>
