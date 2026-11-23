<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/components/admin/Field.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Input } from '@/components/ui/input';

defineProps<{ submitted: boolean }>();

const form = useForm({
    name: '',
    location: '',
    puppy_name: '',
    email: '',
    body: '',
    permission: false,
    website: '', // honeypot, left empty by real visitors
});

function submit() {
    form.post('/share-your-story', { onSuccess: () => form.reset() });
}
</script>

<template>
    <Head title="Share your story">
        <meta
            name="description"
            content="Tell us how your Snow Goose Kennels puppy is doing."
        />
    </Head>

    <div class="mx-auto max-w-2xl space-y-8 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Share your story
            </h1>
            <p class="text-lg text-muted-foreground">
                Did one of our puppies join your family? We would love to hear
                how things are going. A few sentences is plenty.
            </p>
        </header>

        <div v-if="submitted" class="photo-frame space-y-3 p-8 text-center">
            <h2 class="font-serif text-2xl font-semibold">Thank you!</h2>
            <p class="text-muted-foreground">
                We read every story. Yours will appear on our home page once we
                have had a look at it.
            </p>
            <Link href="/" class="font-medium underline underline-offset-4"
                >Back to the home page</Link
            >
        </div>

        <form
            v-else
            class="photo-frame space-y-5 p-6 sm:p-8"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <Field
                    label="Your name"
                    required
                    for="story-name"
                    :error="form.errors.name"
                    hint="A first name or family name is fine."
                    ><Input
                        id="story-name"
                        v-model="form.name"
                        required
                        autocomplete="name"
                        placeholder="The Millers"
                /></Field>
                <Field
                    label="Your puppy’s name"
                    for="story-puppy"
                    :error="form.errors.puppy_name"
                    ><Input
                        id="story-puppy"
                        v-model="form.puppy_name"
                        placeholder="Maggie"
                /></Field>
                <Field
                    label="Where you live"
                    for="story-where"
                    :error="form.errors.location"
                    hint="Town and state, if you like."
                    ><Input
                        id="story-where"
                        v-model="form.location"
                        placeholder="Boise, ID"
                /></Field>
                <Field
                    label="Your email"
                    for="story-email"
                    :error="form.errors.email"
                    hint="Optional. Only so we can say thank you. It is never shown."
                    ><Input
                        id="story-email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                /></Field>
            </div>
            <Field
                label="Your story"
                required
                for="story-body"
                :error="form.errors.body"
                ><TextArea
                    id="story-body"
                    v-model="form.body"
                    :rows="6"
                    required
            /></Field>

            <div class="space-y-1">
                <label class="flex items-start gap-3 text-sm">
                    <input
                        v-model="form.permission"
                        type="checkbox"
                        class="mt-1 size-5 shrink-0"
                    />
                    <span
                        >It is OK to show my story, with the name and town I
                        typed above, on the Snow Goose Kennels website.</span
                    >
                </label>
                <p
                    v-if="form.errors.permission"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.permission }}
                </p>
            </div>

            <!-- Honeypot: hidden from people, tempting to bots. -->
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label
                    >Leave this empty
                    <input
                        v-model="form.website"
                        type="text"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                /></label>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-full bg-primary px-6 py-3 font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60"
            >
                {{ form.processing ? 'Sending…' : 'Send our story' }}
            </button>
            <p class="text-center text-xs text-muted-foreground">
                We look at every story before it goes on the website.
            </p>
        </form>
    </div>
</template>
