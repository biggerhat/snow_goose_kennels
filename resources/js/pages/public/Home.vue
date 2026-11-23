<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ContactLines from '@/components/site/ContactLines.vue';
import LitterCard from '@/components/site/LitterCard.vue';
import Reeds from '@/components/site/Reeds.vue';
import { labelFor } from '@/lib/format';
import type { Options } from '@/types';

const props = defineProps<{
    next: any | null;
    onGround: any[];
    dogs: {
        slug: string;
        name: string;
        role: string;
        color: string | null;
        photos: { thumb_url: string }[];
    }[];
    gallery: { id: number; thumb_url: string; caption: string | null }[];
    quotes: {
        id: number;
        name: string;
        location: string | null;
        puppy_name: string | null;
        body: string;
    }[];
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();

const allQuotes = ref(false);
const shownQuotes = computed(() =>
    allQuotes.value ? props.quotes : props.quotes.slice(0, 3),
);
const name = computed(() => usePage().props.name as string);
const options = computed(() => usePage().props.options as Options);
</script>

<template>
    <Head title="Labrador Retrievers">
        <meta
            name="description"
            content="Family-raised black, chocolate and yellow Labrador retriever puppies. See upcoming litters, dates, prices and health testing."
        />
    </Head>

    <section
        class="mx-auto grid max-w-6xl items-center gap-8 px-5 pt-10 pb-6 md:grid-cols-[1.2fr_1fr] md:pt-16"
    >
        <div class="space-y-6">
            <p
                class="text-xs font-semibold tracking-[0.25em] text-muted-foreground uppercase"
            >
                Labrador retrievers · family raised
            </p>
            <h1
                class="font-serif text-5xl leading-[1.05] font-semibold tracking-tight sm:text-6xl lg:text-7xl"
            >
                {{ name }}
            </h1>
            <p class="max-w-md text-lg text-muted-foreground">
                Black, chocolate and yellow Labs, raised underfoot and ready for
                the marsh, the field or the couch.
            </p>
            <div class="flex flex-wrap gap-3">
                <Link
                    href="/litters"
                    class="rounded-full bg-primary px-6 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                    >See upcoming litters</Link
                >
                <Link
                    href="/apply"
                    class="rounded-full border border-foreground/30 px-6 py-3 font-medium transition-colors hover:bg-foreground/5"
                    >Apply for a puppy</Link
                >
                <Link
                    href="/our-dogs"
                    class="rounded-full border border-foreground/30 px-6 py-3 font-medium transition-colors hover:bg-foreground/5"
                    >Meet our dogs</Link
                >
            </div>
        </div>
        <div class="relative mx-auto w-full max-w-sm">
            <AppLogoIcon
                class-name="relative z-10 w-full text-foreground drop-shadow-[0_18px_18px_oklch(0.2_0.02_60/0.18)]"
            />
        </div>
    </section>

    <div class="my-10"><Reeds /></div>

    <section class="mx-auto max-w-6xl space-y-6 px-5">
        <header class="space-y-1">
            <h2 class="font-serif text-3xl font-semibold">
                {{ next ? 'The next litter' : 'Litters' }}
            </h2>
            <p class="text-muted-foreground">
                Dates, prices and deposits, kept up to date by us.
            </p>
        </header>
        <LitterCard v-if="next" :litter="next" contact-href="/contact" />
        <div v-else class="photo-frame p-8 text-center text-muted-foreground">
            No litters are announced right now.
            <Link href="/contact" class="font-medium text-foreground underline"
                >Get in touch</Link
            >
            to ask about the next one.
        </div>
        <LitterCard
            v-for="l in onGround"
            :key="l.id"
            :litter="l"
            contact-href="/contact"
        />
        <p>
            <Link
                href="/litters"
                class="font-medium underline underline-offset-4"
                >All litters, past and upcoming →</Link
            >
        </p>
    </section>

    <section v-if="dogs.length" class="mx-auto mt-16 max-w-6xl space-y-6 px-5">
        <h2 class="font-serif text-3xl font-semibold">The family</h2>
        <ul class="grid grid-cols-2 gap-5 md:grid-cols-4">
            <li v-for="(d, i) in dogs" :key="d.slug">
                <Link
                    :href="`/our-dogs/${d.slug}`"
                    class="photo-frame block transition-transform hover:-translate-y-1"
                    :style="{ transform: `rotate(${i % 2 ? 1 : -1}deg)` }"
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
                        class="mt-2 text-center font-serif text-lg font-semibold"
                    >
                        {{ d.name }}
                    </p>
                    <p class="text-center text-xs text-muted-foreground">
                        {{
                            [
                                labelFor(options.color, d.color),
                                labelFor(options.role, d.role),
                            ]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                </Link>
            </li>
        </ul>
    </section>

    <section
        v-if="gallery.length"
        class="mx-auto mt-16 max-w-6xl space-y-6 px-5"
    >
        <div class="flex items-end justify-between">
            <h2 class="font-serif text-3xl font-semibold">From the kennel</h2>
            <Link
                href="/gallery"
                class="font-medium underline underline-offset-4"
                >Full gallery →</Link
            >
        </div>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <li
                v-for="p in gallery"
                :key="p.id"
                class="aspect-square overflow-hidden rounded bg-muted"
            >
                <img
                    :src="p.thumb_url"
                    :alt="p.caption ?? ''"
                    class="size-full object-cover"
                    loading="lazy"
                />
            </li>
        </ul>
    </section>

    <section
        v-if="quotes.length"
        id="happy-families"
        class="mx-auto mt-16 max-w-6xl scroll-mt-6 space-y-6 px-5"
    >
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h2 class="font-serif text-3xl font-semibold">Happy families</h2>
            <Link
                href="/share-your-story"
                class="font-medium underline underline-offset-4"
                >Share your story →</Link
            >
        </div>
        <ul class="grid gap-5 md:grid-cols-3">
            <li
                v-for="q in shownQuotes"
                :key="q.id"
                class="photo-frame space-y-3 p-5"
            >
                <p class="leading-relaxed whitespace-pre-line">
                    “{{ q.body }}”
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ q.name }}<span v-if="q.location">, {{ q.location }}</span
                    ><span v-if="q.puppy_name"> · with {{ q.puppy_name }}</span>
                </p>
            </li>
        </ul>
        <p v-if="quotes.length > 3" class="text-center">
            <button
                type="button"
                class="font-medium underline underline-offset-4"
                :aria-expanded="allQuotes"
                @click="allQuotes = !allQuotes"
            >
                {{
                    allQuotes
                        ? 'Show fewer stories'
                        : `Read all ${quotes.length} stories`
                }}
            </button>
        </p>
    </section>

    <section
        v-if="contact.phone || contact.email"
        class="mx-auto mt-16 max-w-6xl px-5"
    >
        <div class="photo-frame space-y-4 p-8 text-center sm:p-12">
            <h2 class="font-serif text-3xl font-semibold">
                Interested in a puppy?
            </h2>
            <p class="mx-auto max-w-md text-muted-foreground">
                We keep a waitlist for each litter. Call or write and we will
                tell you where things stand.
            </p>
            <div class="flex justify-center">
                <ContactLines :contact="contact" />
            </div>
        </div>
    </section>
</template>
