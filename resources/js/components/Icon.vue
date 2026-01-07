<script setup lang="ts">
import { cn } from '@/lib/utils';
import {
    Search,
    Filter,
    Calendar,
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    Plus,
    Clock,
    User,
    MapPin,
    DollarSign,
    Info,
    Edit,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';

const icons: Record<string, any> = {
    Search,
    Filter,
    Calendar,
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    Plus,
    Clock,
    User,
    MapPin,
    DollarSign,
    Info,
    Edit,
    Trash2,
};

interface Props {
    name: string;
    class?: string;
    size?: number | string;
    color?: string;
    strokeWidth?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    size: 16,
    strokeWidth: 2,
});

const className = computed(() => cn('h-4 w-4', props.class));

const icon = computed(() => {
    // Convert kebab-case to PascalCase (e.g. "chevron-right" to "ChevronRight")
    const iconName = props.name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
    return icons[iconName];
});
</script>

<template>
    <component
        :is="icon"
        :class="className"
        :size="size"
        :stroke-width="strokeWidth"
        :color="color"
    />
</template>
