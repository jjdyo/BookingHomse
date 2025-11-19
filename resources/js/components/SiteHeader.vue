<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

// Minimal theme toggle: cycles system -> light -> dark
const { appearance, updateAppearance } = useAppearance();

const nextAppearance = () => {
    const order = ['system', 'light', 'dark'] as const;
    const current = appearance.value ?? 'system';
    const idx = order.indexOf(current as (typeof order)[number]);
    const next = order[(idx + 1) % order.length];
    updateAppearance(next);
};

const appearanceLabel = computed(() => {
    switch (appearance.value) {
        case 'light':
            return 'Light';
        case 'dark':
            return 'Dark';
        default:
            return 'System';
    }
});

// Auth-aware Login/Dashboard button
type PageProps = {
    auth?: {
        user?: unknown;
    };
};
const page = usePage<PageProps>();
const isLoggedIn = computed(() => Boolean(page.props.auth && page.props.auth.user));
const authButtonLabel = computed(() => (isLoggedIn.value ? 'Dashboard' : 'Login'));
const authButtonHref = computed(() => (isLoggedIn.value ? '/dashboard' : '/login'));
</script>

<template>
    <header class="w-full border-b border-sidebar-border/70 bg-white dark:bg-black">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 py-4 px-4 lg:px-8 xl:px-12">
            <div class="flex items-center">
                <Link href="/" class="flex items-center gap-2">
                    <AppLogo />
                </Link>
            </div>
            <!-- Desktop nav -->
            <nav class="hidden items-center gap-4 text-sm lg:flex">
                <Button asChild>
                    <Link href="/">Home</Link>
                </Button>
                <Button asChild>
                    <Link href="/about">About</Link>
                </Button>
                <Button asChild>
                    <Link href="/request-booking">Request Booking</Link>
                </Button>
                <!-- Login/Dashboard button depending on auth state -->
                <Button asChild>
                    <Link :href="authButtonHref">{{ authButtonLabel }}</Link>
                </Button>
                <!-- Appearance toggle: light/dark/system -->
                <Button
                    type="button"
                    variant="secondary"
                    class="ml-2"
                    title="Toggle appearance (System → Light → Dark)"
                    @click="nextAppearance"
                >
                    {{ appearanceLabel }}
                </Button>
            </nav>

            <!-- Mobile menu (<= ~lg) -->
            <div class="lg:hidden">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            class="h-10 w-10 p-0 border-1 border-black text-black dark:border-white dark:text-white"
                            aria-label="Open menu"
                            title="Menu"
                            type="button"
                        >
                            <!-- Simple hamburger icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                <path d="M4 6h16a1 1 0 0 0 0-2H4a1 1 0 1 0 0 2zm16 5H4a1 1 0 0 0 0 2h16a1 1 0 0 0 0-2zm0 7H4a1 1 0 0 0 0 2h16a1 1 0 0 0 0-2z" />
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="min-w-56">
                        <DropdownMenuItem asChild>
                            <Link href="/">Home</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href="/about">About</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href="/request-booking">Request Booking</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link :href="authButtonHref">{{ authButtonLabel }}</Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <button
                                type="button"
                                class="w-full text-left"
                                title="Toggle appearance (System → Light → Dark)"
                                @click="nextAppearance"
                            >
                                Appearance: {{ appearanceLabel }}
                            </button>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>
</template>
