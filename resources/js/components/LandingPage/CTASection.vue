<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue'
</script>

<template>
  <section class="mx-auto max-w-4xl px-6 py-14 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100">Ready to book your next getaway?</h2>
    <p class="mt-3 text-lg text-muted-foreground">
      Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vitae lorem non lorem vestibulum
      posuere. Start exploring thousands of unique stays right now.
    </p>
    <div class="mt-6 flex items-center justify-center gap-4">
      <Button variant="default" size="lg">Explore stays</Button>
      <Button variant="secondary" size="lg">Become a host</Button>
    </div>
  </section>
</template>

<style scoped>
</style>
