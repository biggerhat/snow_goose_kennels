<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { labelFor } from '@/lib/format';
import type { Options } from '@/types';

const props = defineProps<{
    dogs: {
        slug: string;
        name: string;
        role: string;
        sex: string;
        color: string | null;
        age_label: string | null;
        photos: { thumb_url: string }[];
    }[];
}>();
const options = computed(() => usePage().props.options as Options);

const groups = computed(() =>
    [
        { title: 'Studs', items: props.dogs.filter((d) => d.role === 'stud') },
        { title: 'Dams', items: props.dogs.filter((d) => d.role === 'dam') },
        {
            title: 'Family & retired',
            items: props.dogs.filter((d) => !['stud', 'dam'].includes(d.role)),
        },
    ].filter((g) => g.items.length),
);
</script>

<template>
    <Head title="Our Dogs">
        <meta
            name="description"
            content="Meet the Labrador retriever studs and dams behind every Snow Goose litter."
        />
    </Head>

    <div class="mx-auto max-w-6xl space-y-12 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Our dogs
            </h1>
            <p class="max-w-xl text-lg text-muted-foreground">
                The Labs behind every litter.
            </p>
        </header>

        <p
            v-if="!dogs.length"
            class="photo-frame p-8 text-center text-muted-foreground"
        >
            Our dogs are coming soon.
        </p>

        <section v-for="g in groups" :key="g.title" class="space-y-5">
            <h2 class="font-serif text-2xl font-semibold">{{ g.title }}</h2>
            <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                <li v-for="(d, i) in g.items" :key="d.slug">
                    <Link
                        :href="`/our-dogs/${d.slug}`"
                        class="photo-frame block transition-transform hover:-translate-y-1"
                        :style="{
                            transform: `rotate(${i % 2 ? 0.8 : -0.8}deg)`,
                        }"
                    >
                        <div class="aspect-[4/5] overflow-hidden bg-muted">
                            <img
                                v-if="d.photos[0]"
                                :src="d.photos[0].thumb_url"
                                :alt="d.name"
                                class="size-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <p
                            class="mt-2 text-center font-serif text-xl font-semibold"
                        >
                            {{ d.name }}
                        </p>
                        <p class="text-center text-xs text-muted-foreground">
                            {{
                                [labelFor(options.color, d.color), d.age_label]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
