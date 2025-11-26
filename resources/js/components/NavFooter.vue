<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                    >
                        <!-- Open external links in a new tab, internal routes via Inertia -->
                        <template v-if="/^https?:\/\//.test(toUrl(item.href))">
                            <a
                                :href="toUrl(item.href)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </a>
                        </template>
                        <template v-else>
                            <Link :href="toUrl(item.href)">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </template>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
