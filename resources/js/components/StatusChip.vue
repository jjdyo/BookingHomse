<script setup lang="ts">
interface Props {
  value: boolean;
  trueLabel?: string;
  falseLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
  trueLabel: 'Enabled',
  falseLabel: 'Disabled',
});
</script>

<template>
  <span
    class="rounded-full px-2 py-1 text-xs"
    :class="props.value
      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
      : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'"
  >
    {{ props.value ? props.trueLabel : props.falseLabel }}
  </span>
</template>
