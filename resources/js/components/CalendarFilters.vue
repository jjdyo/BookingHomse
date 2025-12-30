<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Icon from '@/components/Icon.vue';

export interface CalendarFilterState {
    search: string;
    title: string;
    address: string;
    horses: string;
    trainers: string;
}

const props = defineProps<{
    modelValue: CalendarFilterState;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: CalendarFilterState): void;
    (e: 'filter'): void;
}>();

const localFilters = ref<CalendarFilterState>({ ...props.modelValue });

watch(() => props.modelValue, (newVal) => {
    localFilters.value = { ...newVal };
}, { deep: true });

function onSearch() {
    emit('update:modelValue', { ...localFilters.value });
    emit('filter');
}

function clearFilters() {
    localFilters.value = {
        search: '',
        title: '',
        address: '',
        horses: '',
        trainers: '',
    };
    onSearch();
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-lg border bg-card p-4 shadow-sm">
        <!-- Main Search Bar -->
        <div class="flex-1 min-w-[200px]">
            <Label for="calendar-search" class="mb-2">Search Calendar</Label>
            <div class="relative">
                <Icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <Input
                    id="calendar-search"
                    v-model="localFilters.search"
                    placeholder="Search by title, horse, trainer..."
                    class="pl-9"
                    @keyup.enter="onSearch"
                />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Advanced Filter Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon" title="Advanced Filters">
                        <Icon name="filter" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80 p-4">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="filter-title">Title</Label>
                            <Input id="filter-title" v-model="localFilters.title" placeholder="Filter by title" />
                        </div>
                        <div class="space-y-2">
                            <Label for="filter-address">Address / Location</Label>
                            <Input id="filter-address" v-model="localFilters.address" placeholder="Filter by address" />
                        </div>
                        <div class="space-y-2">
                            <Label for="filter-horses">Horse(s)</Label>
                            <Input id="filter-horses" v-model="localFilters.horses" placeholder="Horse names (comma separated)" />
                        </div>
                        <div class="space-y-2">
                            <Label for="filter-trainers">Trainer(s)</Label>
                            <Input id="filter-trainers" v-model="localFilters.trainers" placeholder="Trainer names (comma separated)" />
                        </div>
                        <div class="flex justify-between pt-2">
                            <Button variant="ghost" size="sm" @click="clearFilters">Clear All</Button>
                            <Button size="sm" @click="onSearch">Apply Filters</Button>
                        </div>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <Button @click="onSearch">
                Search
            </Button>
        </div>
    </div>
</template>
