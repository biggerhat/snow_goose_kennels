<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { search } from '@/routes/admin';

type Group = {
    title: string;
    items: { label: string; sub: string; href: string }[];
};

const open = ref(false);
const q = ref('');
const groups = ref<Group[]>([]);
const loading = ref(false);
const searched = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
let seq = 0;

watch(q, (value) => {
    clearTimeout(timer);

    if (value.trim().length < 2) {
        groups.value = [];
        searched.value = false;

        return;
    }

    timer = setTimeout(async () => {
        const mine = ++seq;
        loading.value = true;

        try {
            const r = await fetch(search.url({ query: { q: value.trim() } }), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await r.json();

            if (mine === seq) {
                groups.value = data.groups ?? [];
                searched.value = true;
            }
        } finally {
            loading.value = false;
        }
    }, 200);
});

function go(href: string) {
    open.value = false;
    q.value = '';
    router.visit(href);
}

function onKey(e: KeyboardEvent) {
    const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(
        (e.target as HTMLElement)?.tagName,
    );

    if (
        (e.key === '/' && !typing) ||
        ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')
    ) {
        e.preventDefault();
        open.value = true;
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <button
        type="button"
        class="ml-auto inline-flex min-h-9 items-center gap-2 rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        aria-label="Find a dog, litter, puppy or contact"
        @click="open = true"
    >
        <Search class="size-4" />
        <span class="hidden sm:inline">Find…</span>
        <kbd class="hidden rounded border px-1.5 text-[10px] lg:inline">/</kbd>
    </button>

    <Dialog v-model:open="open">
        <DialogContent
            class="top-[15%] max-w-lg translate-y-0 gap-3 p-4 sm:top-[20%]"
        >
            <DialogHeader>
                <DialogTitle>Find anything</DialogTitle>
                <DialogDescription>
                    Search dogs, litters, puppies and contacts by name, number,
                    phone or email.
                </DialogDescription>
            </DialogHeader>
            <input
                v-model="q"
                type="search"
                autofocus
                placeholder="Start typing…"
                aria-label="Search"
                class="h-11 w-full rounded-md border border-input bg-transparent px-3 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                @keydown.enter="
                    groups[0]?.items[0] && go(groups[0].items[0].href)
                "
            />
            <div
                class="max-h-[50vh] space-y-3 overflow-y-auto"
                aria-live="polite"
            >
                <p v-if="loading" class="text-sm text-muted-foreground">
                    Searching…
                </p>
                <p
                    v-else-if="searched && !groups.length"
                    class="text-sm text-muted-foreground"
                >
                    Nothing found for “{{ q }}”. Try a shorter word.
                </p>
                <section v-for="g in groups" :key="g.title">
                    <h3
                        class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ g.title }}
                    </h3>
                    <ul>
                        <li v-for="i in g.items" :key="i.href + i.label">
                            <button
                                type="button"
                                class="flex w-full flex-col rounded-md px-2 py-2 text-left hover:bg-muted"
                                @click="go(i.href)"
                            >
                                <span class="text-sm font-medium">{{
                                    i.label
                                }}</span>
                                <span
                                    v-if="i.sub"
                                    class="text-xs text-muted-foreground"
                                    >{{ i.sub }}</span
                                >
                            </button>
                        </li>
                    </ul>
                </section>
            </div>
        </DialogContent>
    </Dialog>
</template>
