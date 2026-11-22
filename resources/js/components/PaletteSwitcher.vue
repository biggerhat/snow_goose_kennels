<script setup lang="ts">
import { Check } from '@lucide/vue';
import { usePalette } from '@/composables/usePalette';

const { palette, palettes, updatePalette } = usePalette();
</script>

<template>
    <div
        class="flex flex-wrap gap-3"
        role="radiogroup"
        aria-label="Colour theme"
    >
        <button
            v-for="p in palettes"
            :key="p.value"
            type="button"
            role="radio"
            :aria-checked="palette === p.value"
            @click="updatePalette(p.value)"
            :class="[
                'flex items-center gap-2.5 rounded-lg border px-3.5 py-2 text-sm transition-colors',
                palette === p.value
                    ? 'border-foreground bg-card shadow-xs'
                    : 'border-border hover:bg-muted',
            ]"
        >
            <span
                class="flex size-5 items-center justify-center rounded-full ring-1 ring-black/10"
                :style="{ backgroundColor: p.swatch }"
            >
                <Check
                    v-if="palette === p.value"
                    class="size-3 text-white mix-blend-difference"
                />
            </span>
            {{ p.label }}
        </button>
    </div>
</template>
