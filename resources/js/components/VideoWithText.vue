<script setup lang="ts">
import { computed } from 'vue';
interface Props {
  title?: string;
  videoSrc: string; // full iframe src (YouTube embed URL, etc.)
  textFirstDesktop?: boolean; // on md+ screens, text first then video
  videoFirstMobile?: boolean; // on mobile, show video before text
  containerClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  textFirstDesktop: true,
  videoFirstMobile: true,
  containerClass: 'mx-auto max-w-6xl p-6',
});

const textColOrder = computed(() => {
  // mobile: if videoFirstMobile => text order 2, else 1
  const mobileOrder = props.videoFirstMobile ? 'order-2' : 'order-1';
  // desktop: if textFirstDesktop => order-1 else order-2
  const desktopOrder = props.textFirstDesktop ? 'md:order-1' : 'md:order-2';
  return `${mobileOrder} ${desktopOrder}`;
});

const videoColOrder = computed(() => {
  const mobileOrder = props.videoFirstMobile ? 'order-1' : 'order-2';
  const desktopOrder = props.textFirstDesktop ? 'md:order-2' : 'md:order-1';
  return `${mobileOrder} ${desktopOrder}`;
});
</script>

<template>
  <section :class="containerClass">
    <div class="grid items-start gap-8 md:grid-cols-2">
      <!-- Text column -->
      <div :class="textColOrder">
        <h2 v-if="title" class="text-3xl font-semibold tracking-tight">{{ title }}</h2>
        <div class="mt-4 text-muted-foreground">
          <slot />
        </div>
      </div>

      <!-- Video column -->
      <div :class="videoColOrder">
        <div class="aspect-video overflow-hidden rounded-lg border bg-black shadow-sm">
          <iframe
            class="h-full w-full"
            :src="videoSrc"
            title="Video"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
            loading="lazy"
          ></iframe>
        </div>
      </div>
    </div>
  </section>
</template>
