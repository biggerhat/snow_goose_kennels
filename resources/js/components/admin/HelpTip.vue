<script setup lang="ts">
import { CircleHelp } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{ text: string; label?: string }>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

function onDocClick(e: MouseEvent) {
    if (open.value && root.value && !root.value.contains(e.target as Node)) {
        open.value = false;
    }
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <span ref="root" class="relative inline-flex align-middle">
        <button
            type="button"
            class="relative -my-[5px] inline-flex size-6 items-center justify-center rounded-full text-muted-foreground after:absolute after:-inset-2 after:content-[''] hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :aria-label="label ?? 'What is this?'"
            :aria-expanded="open"
            @click.prevent="open = !open"
        >
            <CircleHelp class="size-4" />
        </button>
        <span
            v-if="open"
            role="note"
            class="absolute top-full left-0 z-30 mt-1 w-64 max-w-[80vw] rounded-lg border bg-popover p-3 text-sm leading-snug font-normal text-popover-foreground shadow-lg"
        >
            {{ text }}
        </span>
    </span>
</template>
