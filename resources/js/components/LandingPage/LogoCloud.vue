<script setup lang="ts">
const logos = [
  { name: 'Acme Co', src: 'https://placehold.co/140x48' },
  { name: 'Globex', src: 'https://placehold.co/140x48' },
  { name: 'Initech', src: 'https://placehold.co/140x48' },
  { name: 'Umbrella', src: 'https://placehold.co/140x48' },
  { name: 'Hooli', src: 'https://placehold.co/140x48' },
  { name: 'Soylent', src: 'https://placehold.co/140x48' },
]
</script>

<template>
  <section class="mx-auto max-w-6xl px-6 py-12">
    <p class="mb-6 text-center text-sm uppercase tracking-wider text-muted-foreground">Trusted by teams worldwide</p>
    <div class="grid grid-cols-2 items-center gap-6 sm:grid-cols-3 md:grid-cols-6">
      <img
        v-for="(l, i) in logos"
        :key="i"
        :src="l.src"
        :alt="l.name"
        class="mx-auto h-12 w-auto object-contain opacity-80 hover:opacity-100 transition-opacity"
      />
    </div>
  </section>
</template>

<style scoped>
</style>
