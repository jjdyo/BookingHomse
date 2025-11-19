<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Home" />
    <BasicLayout>
        <section class="mx-auto max-w-6xl p-6">
            <h1 class="text-2xl font-semibold">Welcome</h1>
            <p class="mt-2 text-muted-foreground">
                This is the home page.
            </p>
        </section>
    </BasicLayout>
</template>
