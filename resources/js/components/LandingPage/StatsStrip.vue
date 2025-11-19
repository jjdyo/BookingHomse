<script setup lang="ts">
const stats = [
  { label: 'Nights booked', value: '1.2M+' },
  { label: 'Active listings', value: '85k+' },
  { label: 'Average rating', value: '4.8/5' },
  { label: 'Countries', value: '40+' },
]
</script>

<template>
  <section class="w-full bg-muted/40 py-10">
    <div class="mx-auto max-w-6xl px-6">
      <ul class="grid gap-6 sm:grid-cols-2 md:grid-cols-4">
        <li v-for="(s, i) in stats" :key="i" class="rounded-xl border bg-background p-6 text-center shadow-sm">
          <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ s.value }}</div>
          <div class="mt-1 text-sm text-muted-foreground">{{ s.label }}</div>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
</style>
