<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    photos: {
        id: number;
        url: string;
        thumb_url: string;
        caption: string | null;
        credit: string | null;
    }[];
}>();
const open = ref<number | null>(null);
</script>

<template>
    <Head title="Gallery">
        <meta
            name="description"
            content="Photos of our Labrador puppies and dogs."
        />
    </Head>

    <div class="mx-auto max-w-6xl space-y-8 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Gallery
            </h1>
            <p class="max-w-xl text-lg text-muted-foreground">
                Pups, parents and a lot of mud.
            </p>
        </header>

        <p
            v-if="!photos.length"
            class="photo-frame p-8 text-center text-muted-foreground"
        >
            Photos are coming soon.
        </p>

        <ul class="columns-2 gap-4 md:columns-3 lg:columns-4">
            <li
                v-for="(p, i) in photos"
                :key="p.id"
                class="mb-4 break-inside-avoid"
            >
                <button
                    type="button"
                    class="photo-frame block w-full text-left transition-transform hover:-translate-y-0.5"
                    @click="open = i"
                >
                    <img
                        :src="p.thumb_url"
                        :alt="p.caption ?? ''"
                        class="w-full"
                        loading="lazy"
                    />
                    <span
                        v-if="p.caption"
                        class="mt-1.5 block px-1 text-xs text-muted-foreground"
                        >{{ p.caption }}</span
                    >
                </button>
            </li>
        </ul>
    </div>

    <div
        v-if="open !== null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4"
        role="dialog"
        aria-modal="true"
        @click.self="open = null"
        @keydown.esc="open = null"
    >
        <button
            type="button"
            class="absolute top-4 right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
            aria-label="Close"
            @click="open = null"
        >
            <X class="size-6" />
        </button>
        <figure class="max-h-full max-w-4xl">
            <img
                :src="photos[open].url"
                :alt="photos[open].caption ?? ''"
                class="max-h-[80vh] w-auto rounded"
            />
            <figcaption
                v-if="photos[open].caption || photos[open].credit"
                class="mt-3 text-center text-sm text-white/80"
            >
                {{ photos[open].caption
                }}<span v-if="photos[open].credit">
                    · {{ photos[open].credit }}</span
                >
            </figcaption>
        </figure>
        <button
            v-if="open > 0"
            type="button"
            class="absolute top-1/2 left-3 -translate-y-1/2 rounded-full bg-white/10 px-4 py-3 text-2xl text-white hover:bg-white/20"
            aria-label="Previous"
            @click="open--"
        >
            ‹
        </button>
        <button
            v-if="open < photos.length - 1"
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 rounded-full bg-white/10 px-4 py-3 text-2xl text-white hover:bg-white/20"
            aria-label="Next"
            @click="open++"
        >
            ›
        </button>
    </div>
</template>
