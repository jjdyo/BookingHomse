<script setup lang="ts">
interface Props {
  title?: string;
  imageSrc: string;
  imageAlt?: string;
  imageRight?: boolean; // if true, image on right for md+
  maxImageHeightClass?: string; // e.g., 'max-h-[420px]'
  containerClass?: string; // extra classes for outer section
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  imageAlt: 'Illustration',
  imageRight: true,
  maxImageHeightClass: 'max-h-[420px]',
  containerClass: 'mx-auto max-w-6xl p-6',
});
</script>

<template>
  <section :class="containerClass">
    <div class="grid items-center gap-8 md:grid-cols-2">
      <!-- Text column -->
      <div :class="imageRight ? '' : 'md:order-2'">
        <h2 v-if="title" class="text-3xl font-semibold tracking-tight">{{ title }}</h2>
        <div class="mt-4 text-muted-foreground">
          <slot />
        </div>
      </div>

      <!-- Image column -->
      <div class="flex justify-center md:justify-end" :class="imageRight ? '' : 'md:order-1'">
        <img
          :src="imageSrc"
          :alt="imageAlt"
          class="h-auto w-full max-w-xl rounded-lg border object-cover shadow-sm bg-white"
          :class="maxImageHeightClass"
        />
      </div>
    </div>
  </section>
</template>
