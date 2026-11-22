<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';
import LitterCard from '@/components/site/LitterCard.vue';
import Reeds from '@/components/site/Reeds.vue';

defineProps<{
    upcoming: any[];
    onGround: any[];
    past: any[];
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();
</script>

<template>
    <Head title="Litters">
        <meta
            name="description"
            content="Upcoming and current Labrador puppy litters with due dates, prices, deposits and availability."
        />
    </Head>

    <div class="mx-auto max-w-4xl space-y-12 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Litters
            </h1>
            <p class="max-w-xl text-lg text-muted-foreground">
                Upcoming pairings with due dates, prices and deposits, plus the
                pups already on the ground.
            </p>
        </header>

        <section class="space-y-6">
            <h2 class="font-serif text-2xl font-semibold">Upcoming</h2>
            <LitterCard
                v-for="l in upcoming"
                :key="l.id"
                :litter="l"
                contact-href="/contact"
            />
            <p
                v-if="!upcoming.length"
                class="photo-frame p-8 text-center text-muted-foreground"
            >
                Nothing is announced right now. Reach out and we will let you
                know when the next litter is planned.
            </p>
        </section>

        <section v-if="onGround.length" class="space-y-6">
            <Reeds />
            <h2 class="font-serif text-2xl font-semibold">On the ground</h2>
            <LitterCard
                v-for="l in onGround"
                :key="l.id"
                :litter="l"
                contact-href="/contact"
            />
        </section>

        <section v-if="past.length" class="space-y-6">
            <Reeds />
            <h2 class="font-serif text-2xl font-semibold">Past litters</h2>
            <LitterCard v-for="l in past" :key="l.id" :litter="l" />
        </section>

        <section class="photo-frame space-y-3 p-8 text-center">
            <h2 class="font-serif text-2xl font-semibold">
                Want to be on the list?
            </h2>
            <p class="text-muted-foreground">
                Call, message or send an application and we will tell you where
                things stand.
            </p>
            <div class="flex justify-center">
                <ContactLines :contact="contact" />
            </div>
            <Link
                href="/contact"
                class="font-medium underline underline-offset-4"
                >Contact us →</Link
            >
        </section>
    </div>
</template>
