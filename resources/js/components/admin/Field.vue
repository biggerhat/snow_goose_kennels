<script setup lang="ts">
import { computed, provide, useId } from 'vue';
import HelpTip from '@/components/admin/HelpTip.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { fieldIdKey } from '@/composables/useFieldId';

const props = defineProps<{
    label: string;
    error?: string;
    /** Small grey line under the box. */
    hint?: string;
    /** Longer explanation, shown when the ? is tapped. */
    help?: string;
    required?: boolean;
    for?: string;
}>();

const autoId = useId();
const controlId = computed(() => props.for ?? autoId);
provide(fieldIdKey, controlId);
</script>

<template>
    <div :class="['grid content-start gap-1.5', error && 'field-error']">
        <Label :for="controlId" class="gap-1">
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
