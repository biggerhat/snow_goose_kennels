<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ContactLines from '@/components/site/ContactLines.vue';

const props = defineProps<{
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
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

    <div class="mx-auto max-w-2xl space-y-8 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Contact
            </h1>
            <p class="text-lg text-muted-foreground">
                Call or message us about a litter, a spot on the waitlist, or
                just to talk Labs.
            </p>
        </header>
        <div class="photo-frame p-8">
            <ContactLines v-if="hasAny" :contact="contact" />
            <p v-else class="text-muted-foreground">
                Contact details are coming soon.
            </p>
        </div>
    </div>
</template>
