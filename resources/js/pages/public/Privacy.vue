<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';

defineProps<{
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();
</script>

<template>
    <Head title="Privacy">
        <meta
            name="description"
            content="What information this kennel website collects, how it is used, and who sees it."
        />
    </Head>

    <div class="mx-auto max-w-3xl space-y-8 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Privacy
            </h1>
            <p class="text-lg text-muted-foreground">
                A short, plain explanation of what happens to your information
                on this site.
            </p>
        </header>

        <section class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">What we collect</h2>
            <p class="text-lg leading-relaxed">
                Only what you choose to send us. If you fill in the puppy
                application, that is your name, your email or phone number, your
                town and state, and your answers about your home, your
                experience and what you are looking for. Nothing else is
                collected about you when you browse.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">How we use it</h2>
            <p class="text-lg leading-relaxed">
                To reply to you, to keep our waiting list, and to match people
                with puppies. If you buy a puppy, we keep the sale record, as
                the American Kennel Club asks breeders to keep records for
                several years.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">Who sees it</h2>
            <p class="text-lg leading-relaxed">
                Only the family who run the kennel. We do not sell, rent or
                share your information with anyone else, and we do not show your
                name or details anywhere on this website. Applications are also
                sent by email to the people who run the kennel.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">
                Cookies and tracking
            </h2>
            <p class="text-lg leading-relaxed">
                We use one small cookie that keeps the application form safe
                from misuse. We do not use advertising or analytics trackers.
                The fonts on this site are loaded from Bunny Fonts, a service
                that does not track visitors.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="font-serif text-2xl font-semibold">
                Seeing or removing your information
            </h2>
            <p class="text-lg leading-relaxed">
                Ask us any time and we will tell you what we have, correct it,
                or remove it. We may need to keep sale records for AKC.
            </p>
            <div class="photo-frame mt-3 p-6">
                <ContactLines :contact="contact" />
            </div>
            <Link
                href="/contact"
                class="inline-block pt-1 font-medium underline underline-offset-4"
                >Contact us →</Link
            >
        </section>
    </div>
</template>
