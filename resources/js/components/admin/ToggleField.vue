<script setup lang="ts">
defineProps<{ label: string; hint?: string }>();
const model = defineModel<boolean>();
</script>

<template>
    <label class="flex cursor-pointer items-start gap-3 text-sm">
        <input
            v-model="model"
            type="checkbox"
            class="mt-0.5 size-4 shrink-0 accent-primary"
        />
        <span>
            <span class="font-medium">{{ label }}</span>
            <span v-if="hint" class="block text-xs text-muted-foreground">{{
                hint
            }}</span>
        </span>
    </label>
</template>
