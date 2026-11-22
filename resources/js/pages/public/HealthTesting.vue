<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';
import { formatDate } from '@/lib/format';

defineProps<{
    dogs: {
        slug: string;
        name: string;
        registered_name: string | null;
        titles: string | null;
        role: string;
        health_tests: {
            name: string;
            result: string;
            tested_on: string | null;
            certificate_number: string | null;
            verification_url: string | null;
        }[];
    }[];
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();
</script>

<template>
    <Head title="Health testing">
        <meta
            name="description"
            content="Health clearances for each of our breeding Labrador retrievers, with links to the public records."
        />
    </Head>

    <div class="mx-auto max-w-4xl space-y-10 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Health testing
            </h1>
            <p class="max-w-2xl text-lg text-muted-foreground">
                Every clearance we have on file for our breeding dogs. Where a
                public record exists, tap Verify to see it yourself.
            </p>
        </header>

        <section
            v-for="d in dogs"
            :key="d.slug"
            class="photo-frame space-y-3 p-6"
        >
            <h2 class="font-serif text-2xl font-semibold">
                <Link :href="`/our-dogs/${d.slug}`" class="hover:underline">{{
                    d.name
                }}</Link>
                <span
                    v-if="d.titles"
                    class="ml-2 text-base font-normal text-muted-foreground"
                    >{{ d.titles }}</span
                >
            </h2>
            <p v-if="d.registered_name" class="text-sm text-muted-foreground">
                {{ d.registered_name }}
            </p>
            <ul
                v-if="d.health_tests.length"
                class="divide-y rounded-lg border bg-card"
            >
                <li
                    v-for="t in d.health_tests"
                    :key="t.name"
                    class="flex flex-wrap justify-between gap-x-4 px-4 py-2 text-sm"
                >
                    <span class="font-medium">{{ t.name }}</span>
                    <span class="text-right">
                        {{ t.result }}
                        <span v-if="t.tested_on" class="text-muted-foreground">
                            · {{ formatDate(t.tested_on) }}</span
                        >
                        <span
                            v-if="t.certificate_number || t.verification_url"
                            class="block text-xs text-muted-foreground"
                        >
                            <template v-if="t.certificate_number"
                                >#{{ t.certificate_number }}</template
                            >
                            <a
                                v-if="t.verification_url"
                                :href="t.verification_url"
                                target="_blank"
                                rel="noopener"
                                class="ml-2 underline"
                                >Verify</a
                            >
                        </span>
                    </span>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">
                No clearances are shown for this dog yet.
            </p>
        </section>

        <section class="photo-frame space-y-3 p-8 text-center">
            <h2 class="font-serif text-2xl font-semibold">
                Questions about testing?
            </h2>
            <p class="text-muted-foreground">
                We are happy to talk through any result or send a copy.
            </p>
            <div class="flex justify-center">
                <ContactLines :contact="contact" />
            </div>
        </section>
    </div>
</template>
