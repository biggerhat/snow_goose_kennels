<script setup lang="ts">
import { CircleHelp, X } from '@lucide/vue';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    /** Unique per page; remembers whether she closed it. */
    id: string;
    title?: string;
    steps: string[];
}>();

const open = ref(false);
const key = `help-seen:${props.id}`;

onMounted(() => {
    try {
        // Show once by itself on a first visit, then only when asked for.
        if (!localStorage.getItem(key)) {
            open.value = true;
        }
    } catch {
        // storage unavailable: stay closed
    }
});

function toggle() {
    open.value = !open.value;

    try {
        localStorage.setItem(key, '1');
    } catch {
        // ignore
    }
}
</script>

<template>
    <div>
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            :aria-expanded="open"
            @click="toggle"
        >
            <CircleHelp class="size-4" />
            {{ open ? 'Hide help' : 'How does this page work?' }}
        </button>

        <aside
            v-if="open"
            class="relative mt-3 rounded-xl border border-primary/30 bg-primary/5 p-4 pr-10 text-sm"
        >
            <button
                type="button"
                class="absolute top-2 right-2 rounded-full p-1 text-muted-foreground hover:bg-muted"
                aria-label="Close help"
                @click="toggle"
            >
                <X class="size-4" />
            </button>
            <p class="mb-2 font-serif text-base font-semibold">
                {{ title ?? 'How this page works' }}
            </p>
            <ul class="list-disc space-y-1.5 pl-5">
                <li v-for="s in steps" :key="s">{{ s }}</li>
            </ul>
        </aside>
    </div>
</template>
