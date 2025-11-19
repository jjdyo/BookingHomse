<script setup lang="ts">
// Simple 3x2 feature grid with icons/images and text
const features = [
  { title: 'Flexible Booking', text: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', img: 'https://placehold.co/96x96' },
  { title: 'Trusted Hosts', text: 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', img: 'https://placehold.co/96x96' },
  { title: 'Secure Payments', text: 'Ut enim ad minim veniam, quis nostrud exercitation ullamco.', img: 'https://placehold.co/96x96' },
  { title: '24/7 Support', text: 'Duis aute irure dolor in reprehenderit in voluptate velit esse.', img: 'https://placehold.co/96x96' },
  { title: 'Verified Reviews', text: 'Excepteur sint occaecat cupidatat non proident, sunt in culpa.', img: 'https://placehold.co/96x96' },
  { title: 'Best Prices', text: 'Quis autem vel eum iure reprehenderit qui in ea voluptate.', img: 'https://placehold.co/96x96' },
]
</script>

<template>
  <section class="mx-auto max-w-6xl px-6 py-12">
    <div class="text-center mb-8">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">Why choose us</h2>
      <p class="mt-2 text-muted-foreground">Simple features that make booking delightful.</p>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="(f, i) in features" :key="i" class="rounded-xl border bg-white/50 dark:bg-black/20 p-6 shadow-sm">
        <img :src="f.img" :alt="f.title" class="mb-4 h-16 w-16 rounded-md object-cover" />
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ f.title }}</h3>
        <p class="mt-2 text-sm text-muted-foreground">{{ f.text }}</p>
      </article>
    </div>
  </section>

</template>

<style scoped>
</style>
