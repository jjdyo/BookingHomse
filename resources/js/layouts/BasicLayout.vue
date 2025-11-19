<script setup lang="ts">
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
const message = computed(() => page.props.flash?.message);
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <SiteHeader />
        <main class="flex-1">
            <div v-if="success || error || message" class="mx-auto max-w-6xl p-4">
                <Alert :variant="error ? 'destructive' : 'default'">
                    <AlertTitle>{{ error ? 'Notice' : 'Success' }}</AlertTitle>
                    <AlertDescription>
                        {{ error || success || message }}
                    </AlertDescription>
                </Alert>
            </div>
            <slot />
        </main>
        <SiteFooter />
    </div>

</template>
