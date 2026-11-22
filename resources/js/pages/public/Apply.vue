<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Input } from '@/components/ui/input';
import type { Options } from '@/types';

const props = defineProps<{
    litters: { id: number; title: string }[];
    presetLitter: number | null;
    submitted: boolean;
}>();
const options = computed(() => usePage().props.options as Options);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    city: '',
    state: '',
    litter_id: props.presetLitter as number | null,
    preferred_sex: null as string | null,
    preferred_color: null as string | null,
    home: '',
    experience: '',
    purpose: '',
    heard: '',
    message: '',
    website: '', // honeypot, left empty by real visitors
});

function submit() {
    form.post('/apply', {
        preserveScroll: false,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Apply for a puppy">
        <meta
            name="description"
            content="Apply for a Labrador puppy from Snow Goose Kennels. Tell us about your home and what you are looking for."
        />
    </Head>

    <div class="mx-auto max-w-2xl space-y-8 px-5 pt-12">
        <header class="space-y-2">
            <h1 class="font-serif text-5xl font-semibold tracking-tight">
                Apply for a puppy
            </h1>
            <p class="text-lg text-muted-foreground">
                A few questions help us match puppies to the right homes. It
                takes about five minutes, and we will reply by phone or email.
            </p>
        </header>

        <div v-if="submitted" class="photo-frame space-y-3 p-8 text-center">
            <h2 class="font-serif text-2xl font-semibold">Thank you!</h2>
            <p class="text-muted-foreground">
                We have your application and will be in touch soon.
            </p>
            <Link
                href="/litters"
                class="font-medium underline underline-offset-4"
                >Back to the litters</Link
            >
        </div>

        <form
            v-else
            class="photo-frame space-y-6 p-6 sm:p-8"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Your name" :error="form.errors.name" for="name"
                    ><Input
                        id="name"
                        v-model="form.name"
                        required
                        autocomplete="name"
                /></Field>
                <Field label="Phone" :error="form.errors.phone" for="phone"
                    ><Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        autocomplete="tel"
                /></Field>
                <Field
                    label="Email"
                    :error="form.errors.email"
                    for="email"
                    hint="Phone or email, whichever you check more."
                    ><Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                /></Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field label="City" :error="form.errors.city" for="city"
                        ><Input
                            id="city"
                            v-model="form.city"
                            autocomplete="address-level2"
                    /></Field>
                    <Field label="State" :error="form.errors.state" for="state"
                        ><Input
                            id="state"
                            v-model="form.state"
                            autocomplete="address-level1"
                    /></Field>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Field label="Which litter?" :error="form.errors.litter_id">
                    <NativeSelect
                        v-model="form.litter_id"
                        :options="
                            litters.map((l) => ({
                                value: String(l.id),
                                label: l.title,
                            }))
                        "
                        placeholder="Any upcoming litter"
                    />
                </Field>
                <Field label="Prefer" :error="form.errors.preferred_sex"
                    ><NativeSelect
                        v-model="form.preferred_sex"
                        :options="options.sex"
                        placeholder="No preference"
                /></Field>
                <Field label="Colour" :error="form.errors.preferred_color"
                    ><NativeSelect
                        v-model="form.preferred_color"
                        :options="options.color"
                        placeholder="No preference"
                /></Field>
            </div>

            <Field
                label="Tell us about your home and family"
                :error="form.errors.home"
                for="home"
                hint="Fenced yard? Children? Other pets?"
            >
                <TextArea id="home" v-model="form.home" :rows="3" />
            </Field>
            <Field
                label="Experience with dogs"
                :error="form.errors.experience"
                for="experience"
            >
                <TextArea id="experience" v-model="form.experience" :rows="3" />
            </Field>
            <Field
                label="What are you hoping for in a Lab?"
                :error="form.errors.purpose"
                for="purpose"
                hint="Family companion, hunting partner, service or therapy work…"
            >
                <TextArea id="purpose" v-model="form.purpose" :rows="3" />
            </Field>
            <Field
                label="How did you hear about us?"
                :error="form.errors.heard"
                for="heard"
                ><Input id="heard" v-model="form.heard"
            /></Field>
            <Field
                label="Anything else?"
                :error="form.errors.message"
                for="message"
                ><TextArea id="message" v-model="form.message" :rows="3"
            /></Field>

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
                {{ form.processing ? 'Sending…' : 'Send my application' }}
            </button>
            <p class="text-center text-xs text-muted-foreground">
                Submitting an application does not reserve a puppy. We will
                contact you about next steps.
            </p>
        </form>
    </div>
</template>
