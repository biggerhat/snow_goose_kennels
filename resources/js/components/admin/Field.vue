<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{ label: string; error?: string; hint?: string; for?: string }>();
</script>

<template>
    <div class="grid content-start gap-1.5">
        <Label :for="$props.for">{{ label }}</Label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <InputError :message="error" />
    </div>
</template>
