<script setup lang="ts">
import { useFieldId } from '@/composables/useFieldId';

const props = defineProps<{
    id?: string;
    rows?: number;
    placeholder?: string;
}>();
const fieldId = useFieldId();
const model = defineModel<string | null>();
</script>

<template>
    <textarea
        :id="props.id ?? fieldId ?? undefined"
        v-model="model"
        :rows="rows ?? 4"
        :placeholder="placeholder"
        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
    />
</template>
