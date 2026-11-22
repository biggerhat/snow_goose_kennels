<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';
import Reeds from '@/components/site/Reeds.vue';

defineProps<{
    sections: { key: string; title: string; body: string }[];
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();
</script>

<template>
    <Head title="How it works">
        <meta
            name="description"
            content="How to reserve a Labrador puppy: our process, deposits, go-home age and policies."
        />
    </Head>

    <div class="mx-auto max-w-3xl space-y-10 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                How it works
            </h1>
            <p class="text-lg text-muted-foreground">
                What to expect from first call to go-home day.
            </p>
        </header>

        <p
            v-if="!sections.length"
            class="photo-frame p-8 text-center text-muted-foreground"
        >
            Details are coming soon. In the meantime
            <Link href="/contact" class="font-medium text-foreground underline"
                >get in touch</Link
            >
            and we will walk you through it.
        </p>

        <section v-for="(s, i) in sections" :key="s.key" class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">{{ s.title }}</h2>
            <p class="text-lg leading-relaxed whitespace-pre-line">
                {{ s.body }}
            </p>
            <Reeds v-if="i < sections.length - 1" class="pt-6" />
        </section>

        <section class="photo-frame space-y-4 p-8 text-center">
            <h2 class="font-serif text-2xl font-semibold">Ready to start?</h2>
            <p class="text-muted-foreground">
                Send a short application and we will get back to you.
            </p>
            <Link
                href="/apply"
                class="inline-block rounded-full bg-primary px-6 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90"
                >Apply for a puppy</Link
            >
            <div class="flex justify-center">
                <ContactLines :contact="contact" />
            </div>
        </section>
    </div>
</template>
