<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { home } from '@/routes';
import { send as verificationSend } from '@/routes/verification';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

defineProps<{
    status?: string;
}>();

const COOLDOWN = 60;
const secondsLeft = ref<number>(COOLDOWN);
let interval: number | undefined;
const canResend = computed(() => secondsLeft.value <= 0);

function startTimer() {
    stopTimer();
    secondsLeft.value = COOLDOWN;
    interval = window.setInterval(() => {
        secondsLeft.value -= 1;
        if (secondsLeft.value <= 0) stopTimer();
    }, 1000);
}

function stopTimer() {
    if (interval) {
        clearInterval(interval);
        interval = undefined;
    }
}

onMounted(() => startTimer());
onUnmounted(() => stopTimer());

const form = useForm({});
function resend() {
    if (!canResend.value || form.processing) return;
    form.post(verificationSend().url, {
        preserveScroll: true,
        onStart: () => startTimer(),
    });
}
</script>

<template>
    <AuthLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <div class="space-y-6 text-center">
            <Button :disabled="!canResend || form.processing" variant="secondary" @click="resend">
                <Spinner v-if="form.processing" />
                <span v-if="canResend">Resend verification email</span>
                <span v-else>Resend available in {{ secondsLeft }}s</span>
            </Button>

            <TextLink :href="home().url" class="mx-auto block text-sm">
                Back to home
            </TextLink>
        </div>
    </AuthLayout>
</template>
