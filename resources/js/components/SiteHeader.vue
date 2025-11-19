<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
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
            </nav>
        </div>
    </header>
</template>
