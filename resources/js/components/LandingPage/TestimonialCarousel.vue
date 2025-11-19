<script setup lang="ts">
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/components/ui/carousel'

const testimonials = [
  {
    id: 1,
    name: 'Alex Johnson',
    quote:
      'BookingHomse made it super easy to find a last-minute cabin for the weekend. The whole process took minutes!',
    img: 'https://placehold.co/600x400',
  },
  {
    id: 2,
    name: 'Priya Sharma',
    quote:
      'Great selection, helpful filters, and clear pricing. I felt confident booking right away.',
    img: 'https://placehold.co/600x400',
  },
  {
    id: 3,
    name: 'Diego Martinez',
    quote:
      'Support was quick to respond when I had a question about check-in. Five stars from me!',
    img: 'https://placehold.co/600x400',
  },
]
</script>
<template>
    <section class="mx-auto max-w-6xl px-6 py-12">
        <div class="mb-6 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">What our guests say</h2>
            <p class="mt-2 text-muted-foreground">Real feedback from recent stays.</p>
        </div>

        <Carousel class="relative overflow-hidden rounded-xl border bg-white/50 dark:bg-black/20">
            <CarouselContent class="flex">
                <CarouselItem v-for="t in testimonials" :key="t.id" class="min-w-0 shrink-0 grow-0 basis-full p-0">
                    <div class="grid gap-6 p-6 md:grid-cols-2 md:items-center">
                        <img :src="t.img" :alt="`Photo of ${t.name}`" class="w-full rounded-lg object-cover" />
                        <blockquote>
                            <p class="text-lg text-gray-900 dark:text-gray-100">"{{ t.quote }}"</p>
                            <footer class="mt-4 font-semibold text-gray-700 dark:text-gray-300">— {{ t.name }}</footer>
                        </blockquote>
                    </div>
                </CarouselItem>
            </CarouselContent>
            <CarouselPrevious class="absolute left-3 top-1/2 -translate-y-1/2" />
            <CarouselNext class="absolute right-3 top-1/2 -translate-y-1/2" />
        </Carousel>
    </section>
</template>
<style scoped>
</style>
