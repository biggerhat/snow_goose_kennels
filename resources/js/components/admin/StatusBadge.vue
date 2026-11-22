<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ value: string; label: string }>();

const tone = computed(() => {
    if (
        ['new', 'expected', 'contacted', 'reserved', 'offered'].includes(
            props.value,
        )
    ) {
        return 'bg-amber-500/15 text-amber-800 dark:text-amber-300';
    }

    if (
        ['on_ground', 'available', 'deposit_paid', 'stud', 'dam'].includes(
            props.value,
        )
    ) {
        return 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300';
    }

    if (['all_home', 'home', 'placed', 'kept'].includes(props.value)) {
        return 'bg-sky-500/15 text-sky-800 dark:text-sky-300';
    }

    if (['passed'].includes(props.value)) {
        return 'bg-red-500/15 text-red-800 dark:text-red-300';
    }

    return 'bg-muted text-muted-foreground';
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
            tone,
        ]"
    >
        {{ label }}
    </span>
</template>
