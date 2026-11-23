<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';
import Reeds from '@/components/site/Reeds.vue';

const props = defineProps<{
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
    sections: { key: string; title: string; body: string }[];
}>();
const hasAny = Object.values(props.contact).some(Boolean);
</script>

<template>
    <Head title="Contact">
        <meta
            name="description"
            content="Contact Snow Goose Kennels about Labrador puppies and the waitlist."
        />
    </Head>

    <div class="mx-auto max-w-3xl space-y-12 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Contact
            </h1>
            <p class="text-lg text-muted-foreground">
                Call or message us about a litter, a spot on the waitlist, or
                just to talk Labs.
            </p>
        </header>

        <div class="photo-frame space-y-5 p-8">
            <ContactLines v-if="hasAny" :contact="contact" />
            <p v-else class="text-muted-foreground">
                Contact details are coming soon.
            </p>
            <Link
                href="/apply"
                class="inline-block rounded-full bg-primary px-6 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                >Apply for a puppy</Link
            >
        </div>

        <section
            v-if="sections.length"
            id="how-it-works"
            class="scroll-mt-6 space-y-8"
        >
            <header class="space-y-1">
                <h2 class="font-serif text-4xl font-semibold tracking-tight">
                    How it works
                </h2>
                <p class="text-lg text-muted-foreground">
                    What to expect from first call to go-home day.
                </p>
            </header>
            <div v-for="(s, i) in sections" :key="s.key" class="space-y-2">
                <h3
                    v-if="s.key !== 'how_it_works'"
                    class="font-serif text-2xl font-semibold"
                >
                    {{ s.title }}
                </h3>
                <p class="text-lg leading-relaxed whitespace-pre-line">
                    {{ s.body }}
                </p>
                <Reeds v-if="i < sections.length - 1" class="pt-6" />
            </div>
        </section>
    </div>
</template>
