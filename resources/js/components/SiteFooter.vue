<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

// The application name is shared from Laravel via HandleInertiaRequests@share
// as `name: config('app.name')`.
const page = usePage<{ name?: string }>();
const appName = page.props.name ?? 'App';
</script>

<template>
    <footer class="w-full border-t border-sidebar-border/70 bg-white py-6 dark:bg-black">
        <div class="mx-auto max-w-6xl px-4 text-center text-sm text-muted-foreground">
            {{ appName }} © 2025 - Designed by <a href="https://jaxonville.com/" target="_blank">Jaxon Dupre</a>
        </div>
    </footer>
</template>
