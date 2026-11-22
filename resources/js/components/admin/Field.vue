<script setup lang="ts">
import HelpTip from '@/components/admin/HelpTip.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    error?: string;
    /** Small grey line under the box. */
    hint?: string;
    /** Longer explanation, shown when the ? is tapped. */
    help?: string;
    required?: boolean;
    for?: string;
}>();
</script>

<template>
    <div :class="['grid content-start gap-1.5', error && 'field-error']">
        <Label :for="$props.for" class="gap-1">
            {{ label }}
            <span
                v-if="required"
                class="text-red-600"
                title="Required"
                aria-hidden="true"
                >*</span
            >
            <span v-if="required" class="sr-only">(required)</span>
            <HelpTip v-if="help" :text="help" :label="`About: ${label}`" />
        </Label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <InputError :message="error" />
    </div>
</template>
