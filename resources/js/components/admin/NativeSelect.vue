<script setup lang="ts">
import type { Option } from '@/types';

defineProps<{
    options: Option[];
    placeholder?: string;
    id?: string;
}>();

const model = defineModel<string | number | null>();
</script>

<template>
    <select
        :id="id"
        v-model="model"
        class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30 [&>option]:bg-popover [&>option]:text-popover-foreground"
    >
        <option v-if="placeholder !== undefined" :value="null">
            {{ placeholder }}
        </option>
        <option v-for="o in options" :key="o.value" :value="o.value">
            {{ o.label }}
        </option>
    </select>
</template>
