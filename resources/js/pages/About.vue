<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="About" />
    <BasicLayout>
        <section class="mx-auto max-w-6xl p-6">
            <h1 class="text-2xl font-semibold">About</h1>
            <p class="mt-2 text-muted-foreground">
                This is a simple About page placeholder.
            </p>
        </section>
    </BasicLayout>

</template>
