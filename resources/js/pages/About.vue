<script setup lang="ts">
import BasicLayout from '@/layouts/BasicLayout.vue';
import { Head } from '@inertiajs/vue3';
import LargeHeaderImage from '@/components/LargeHeaderImage.vue';
import TextImageSplit from '@/components/TextImageSplit.vue';
import VideoWithText from '@/components/VideoWithText.vue';

// Static assets and content for the About page.
// Swap these with real copy/media when available.
const headerImage = '/images/BarnHomse169.png';
const sectionImage = 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1600&auto=format&fit=crop';
const youtubeId = 'dQw4w9WgXcQ';
</script>

<template>
    <Head title="About" />
    <BasicLayout>
        <!-- Large header image with centered blurb -->
        <LargeHeaderImage
            :image="headerImage"
            title="About BookingHomse"
            subtitle="We help riders and hosts connect seamlessly with simple, reliable booking."
            height-class="h-[28vh] md:h-[38vh] lg:h-[50vh]"
        />

        <!-- Text left / image right section as a reusable component -->
        <TextImageSplit
            title="Our Story"
            :image-src="sectionImage"
            image-alt="About illustration"
            :image-right="true"
        >
            <p>
                Founded with a passion for making travel and riding logistics effortless,
                BookingHomse brings modern tools to a timeless experience. We focus on
                clarity, speed, and trust — so you can spend less time coordinating and
                more time doing what you love.
            </p>
            <p class="mt-4">
                From smart scheduling to helpful reminders, our platform grows alongside
                our community. We’re continuously improving based on real-world feedback
                from riders, hosts, and event organizers.
            </p>
        </TextImageSplit>

        <!-- Video block component: YouTube video with text to the side -->
        <VideoWithText
            title="See It In Action"
            :video-src="`https://www.youtube.com/embed/${youtubeId}?rel=0&modestbranding=1`"
            :text-first-desktop="true"
            :video-first-mobile="true"
        >
            <p>
                Take a quick tour of how BookingHomse streamlines the booking process,
                keeps everyone informed, and helps you stay organized on the go.
            </p>
            <p class="mt-4">
                This overview highlights the key features that make planning simpler for
                individuals and teams alike.
            </p>
        </VideoWithText>
    </BasicLayout>

</template>
