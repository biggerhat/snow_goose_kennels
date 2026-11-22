<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Testimonial } from '@/types';

defineProps<{ testimonials: Testimonial[] }>();
</script>

<template>
    <Head title="Happy families">
        <meta
            name="description"
            content="Stories from families who brought home a Snow Goose Labrador puppy."
        />
    </Head>

    <div class="mx-auto max-w-4xl space-y-10 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Happy families
            </h1>
            <p class="text-lg text-muted-foreground">
                Notes from the people who took our puppies home.
            </p>
        </header>

        <p
            v-if="!testimonials.length"
            class="photo-frame p-8 text-center text-muted-foreground"
        >
            Stories are coming soon.
        </p>

        <ul class="grid gap-6 md:grid-cols-2">
            <li
                v-for="(t, i) in testimonials"
                :key="t.id"
                class="photo-frame space-y-3 p-6"
                :style="{ transform: `rotate(${i % 2 ? 0.6 : -0.6}deg)` }"
            >
                <p class="text-lg leading-relaxed whitespace-pre-line">
                    “{{ t.body }}”
                </p>
                <p class="text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{{ t.name }}</span
                    ><span v-if="t.location">, {{ t.location }}</span
                    ><span v-if="t.puppy_name"> · with {{ t.puppy_name }}</span>
                </p>
            </li>
        </ul>

        <p class="text-center">
            <Link href="/apply" class="font-medium underline underline-offset-4"
                >Join the waitlist →</Link
            >
        </p>
    </div>
</template>
