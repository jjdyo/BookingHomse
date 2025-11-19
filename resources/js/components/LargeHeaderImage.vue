<script setup lang="ts">
interface Props {
    image: string;
    title?: string;
    subtitle?: string;
    heightClass?: string; // allow overriding height e.g., 'h-[60vh]'
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    subtitle: '',
    heightClass: 'h-[48vh] md:h-[60vh] lg:h-[70vh]',
});
</script>

<template>
    <section
        class="relative w-full overflow-hidden rounded-none"
        :class="heightClass"
    >
        <!-- Background image -->
        <img
            :src="props.image"
            alt="Header background"
            class="absolute inset-0 h-full w-full object-cover"
        />

        <!-- Soft dark overlay to improve text contrast -->
        <div class="absolute inset-0 bg-black/40" aria-hidden="true"></div>

        <!-- Centered content with subtle backdrop blur and shadow -->
        <div class="relative z-10 flex h-full items-center justify-center p-6">
            <div
                class="max-w-3xl text-center text-white/95 backdrop-blur-sm"
            >
                <div
                    class="inline-block rounded-xl bg-black/20 px-6 py-4 shadow-xl ring-1 ring-white/10"
                >
                    <h1 v-if="title" class="text-3xl font-bold sm:text-5xl">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="mt-3 text-base sm:text-lg md:text-xl text-white/90">
                        {{ subtitle }}
                    </p>
                    <slot />
                </div>
            </div>
        </div>
    </section>
</template>
