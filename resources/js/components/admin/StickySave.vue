<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    dirty: boolean;
    processing?: boolean;
    /** Id of the <form> this bar saves. */
    formId: string;
    label?: string;
}>();

defineEmits<{ discard: [] }>();
</script>

<template>
    <Transition
        enter-active-class="transition duration-150"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="dirty"
            role="region"
            aria-label="Unsaved changes"
            class="fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] backdrop-blur max-md:bottom-14 md:left-[var(--sidebar-width,16rem)]"
        >
            <div
                class="mx-auto flex max-w-4xl items-center justify-between gap-3"
            >
                <p class="text-sm font-medium">
                    You have changes that are not saved.
                </p>
                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="$emit('discard')"
                        >Discard</Button
                    >
                    <Button type="submit" :form="formId" :disabled="processing">
                        {{ processing ? 'Saving…' : (label ?? 'Save changes') }}
                    </Button>
                </div>
            </div>
        </div>
    </Transition>
</template>
