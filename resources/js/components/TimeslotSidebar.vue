<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface TimeslotSummary {
    id: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string;
    url: string;
}

const nowHappening = ref<TimeslotSummary[]>([]);
const upcoming = ref<TimeslotSummary[]>([]);
const loading = ref(true);

const page = usePage();
const visible = computed(() => (page.props as any).site?.show_event_feed ?? true);

async function fetchSidebar() {
    if (!visible.value) return;
    loading.value = true;
    try {
        const response = await fetch('/timeslots/sidebar');
        const data = await response.json();
        nowHappening.value = data.now_happening;
        upcoming.value = data.upcoming;
    } catch (error) {
        console.error('Failed to fetch sidebar data', error);
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    fetchSidebar();
});

// Re-fetch if we turned it on or if settings changed (like lookahead)
watch(() => (page.props as any).site?.show_event_feed, (newVal) => {
    if (newVal) fetchSidebar();
});
watch(() => (page.props as any).site?.event_feed_lookahead_days, () => {
    fetchSidebar();
});

function formatTime(isoString: string) {
    return new Date(isoString).toLocaleString([], {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <aside v-if="visible" class="w-full h-full max-h-[800px] flex flex-col gap-8 overflow-hidden rounded-xl border bg-card p-4 shadow-sm">
        <!-- Now Happening -->
        <section class="flex flex-col min-h-0">
            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-4 sticky top-0 bg-card z-10 py-1">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                </span>
                Now Happening
            </h3>
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 2" :key="i" class="animate-pulse space-y-2">
                    <div class="h-4 w-3/4 rounded bg-muted"></div>
                    <div class="h-3 w-full rounded bg-muted"></div>
                </div>
            </div>
            <div v-else-if="nowHappening.length === 0" class="text-sm text-muted-foreground italic">
                Nothing happening right now.
            </div>
            <div v-else class="space-y-3 overflow-y-auto pr-1 custom-scrollbar">
                <a
                    v-for="event in nowHappening"
                    :key="event.id"
                    :href="event.url"
                    class="block rounded-lg border border-transparent bg-rose-50 p-3 transition-all hover:border-rose-200 hover:shadow-sm dark:bg-rose-950/20 dark:hover:border-rose-800"
                >
                    <h4 class="font-semibold text-foreground leading-tight">{{ event.title }}</h4>
                    <p v-if="event.description" class="mt-1 text-xs text-muted-foreground line-clamp-2">
                        {{ event.description }}
                    </p>
                    <div class="mt-2 text-[10px] font-medium text-rose-700 dark:text-rose-300">
                        {{ formatTime(event.start_at) }} - {{ formatTime(event.end_at) }}
                    </div>
                </a>
            </div>
        </section>

        <!-- Upcoming -->
        <section class="flex flex-col min-h-0 flex-1">
            <h3 class="text-sm font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-4 sticky top-0 bg-card z-10 py-1">
                Upcoming Events
            </h3>
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="animate-pulse space-y-2">
                    <div class="h-4 w-2/3 rounded bg-muted"></div>
                    <div class="h-3 w-full rounded bg-muted"></div>
                </div>
            </div>
            <div v-else-if="upcoming.length === 0" class="text-sm text-muted-foreground italic">
                No upcoming events scheduled.
            </div>
            <div v-else class="space-y-3 overflow-y-auto pr-1 custom-scrollbar">
                <a
                    v-for="event in upcoming"
                    :key="event.id"
                    :href="event.url"
                    class="block rounded-lg border border-transparent bg-muted/50 p-3 transition-all hover:border-border hover:bg-muted dark:bg-muted/20"
                >
                    <h4 class="font-semibold text-foreground leading-tight">{{ event.title }}</h4>
                    <p v-if="event.description" class="mt-1 text-xs text-muted-foreground line-clamp-2">
                        {{ event.description }}
                    </p>
                    <div class="mt-2 text-[10px] font-medium text-muted-foreground">
                        {{ formatTime(event.start_at) }}
                    </div>
                </a>
            </div>
        </section>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--color-border);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--color-muted-foreground);
}
</style>
