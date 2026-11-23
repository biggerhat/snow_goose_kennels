<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleHelp, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { topicId } from '@/lib/helpTopic';
import { help } from '@/routes/admin';

const props = defineProps<{
    title?: string;
    steps: string[];
    /** The Help page how-to that goes with this page, by its question. */
    topic?: string;
}>();

// Always starts closed: the owner opens it when she wants it. The first thing a new
// owner sees is a single welcome note on the dashboard, not a panel on every page.
const open = ref(false);
const all = ref(false);
const shown = computed(() =>
    all.value ? props.steps : props.steps.slice(0, 3),
);
</script>

<template>
    <div>
        <button
            type="button"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground md:min-h-9"
            :aria-expanded="open"
            @click="open = !open"
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
                class="absolute top-2 right-2 rounded-full p-2 text-muted-foreground hover:bg-muted"
                aria-label="Close help"
                @click="open = false"
            >
                <X class="size-4" />
            </button>
            <p class="mb-2 font-serif text-base font-semibold">
                {{ title ?? 'About this page' }}
            </p>
            <ul class="list-disc space-y-1.5 pl-5">
                <li v-for="s in shown" :key="s">{{ s }}</li>
            </ul>
            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
                <button
                    v-if="steps.length > 3"
                    type="button"
                    class="underline underline-offset-2"
                    @click="all = !all"
                >
                    {{ all ? 'Show less' : `Show ${steps.length - 3} more` }}
                </button>
                <Link
                    :href="topic ? `${help().url}#${topicId(topic)}` : help()"
                    class="underline underline-offset-2"
                    >{{
                        topic
                            ? 'See the step-by-step help'
                            : 'Open the full Help page'
                    }}</Link
                >
            </p>
        </aside>
    </div>
</template>
