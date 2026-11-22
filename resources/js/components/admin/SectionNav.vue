<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { jumpTo, pageSections } from '@/directives/fold';

const active = ref<string | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort(
                    (a, b) =>
                        a.boundingClientRect.top - b.boundingClientRect.top,
                )[0];

            if (visible) {
                active.value = visible.target.id;
            }
        },
        { rootMargin: '-20% 0px -65% 0px' },
    );

    // Sections register themselves as they mount, a moment after this bar.
    setTimeout(
        () =>
            pageSections.forEach((s) => {
                const el = document.getElementById(s.id);

                if (el) {
                    observer?.observe(el);
                }
            }),
        300,
    );
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <nav
        v-if="pageSections.length > 3"
        aria-label="On this page"
        class="sticky top-0 z-20 -mx-4 border-b bg-background/95 px-4 py-2 backdrop-blur md:-mx-6 md:px-6"
    >
        <ul
            class="flex [scrollbar-width:none] gap-1.5 overflow-x-auto pb-0.5 text-sm"
        >
            <li v-for="s in pageSections" :key="s.id" class="shrink-0">
                <button
                    type="button"
                    :class="[
                        'min-h-9 rounded-full border px-3 py-1 transition-colors',
                        active === s.id
                            ? 'border-foreground bg-card font-medium'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                    :aria-current="active === s.id ? 'true' : undefined"
                    @click="jumpTo(s.id)"
                >
                    {{ s.label }}
                </button>
            </li>
        </ul>
    </nav>
</template>
