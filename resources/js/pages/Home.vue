<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';
// Animation now plays every time; no component logic needed.

interface Props {
    heroImage: string;
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Home" />
    <BasicLayout>
        <section class="mx-auto max-w-6xl p-6">
            <div class="grid items-center gap-8 md:grid-cols-2">
                <!-- Left: Headings -->
                <div class="card text-align-center">
                    <h1 class="text-4xl font-bold leading-tight sm:text-5xl text-black dark:text-black">
                        Welcome to <br />
                        <span class="font-bold">BookingHomse</span>
                    </h1>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Start riding with us today.
                    </p>
                </div>

                <!-- Right: Image -->
                <div class="flex justify-center md:justify-end">
                    <img
                        :src="props.heroImage"
                        alt="BarnHomse169"
                        class="h-auto max-h-[420px] w-full max-w-xl rounded-lg border object-cover shadow-sm bg-white animate-slide-in-right"
                    />
                </div>
            </div>
        </section>
    </BasicLayout>
</template>
