<script setup lang="ts">
import { useFieldId } from '@/composables/useFieldId';

defineProps<{ accept?: string; multiple?: boolean }>();
defineEmits<{ change: [files: FileList | null] }>();
const fieldId = useFieldId();
</script>

<template>
    <input
        :id="fieldId ?? undefined"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="block w-full text-sm file:mr-3 file:rounded-md file:border file:bg-secondary file:px-3 file:py-2 file:text-sm"
        @change="$emit('change', ($event.target as HTMLInputElement).files)"
    />
</template>
