<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StickySave from '@/components/admin/StickySave.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import { exportMethod } from '@/routes/admin';
import { edit, update } from '@/routes/admin/site';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Website text', href: edit() }] },
});

const props = defineProps<{
    contact: {
        phone: string | null;
        email: string | null;
        location: string | null;
        facebook: string | null;
    };
    sections: { key: string; title: string; hint: string; body: string }[];
    owner: Record<string, string>;
}>();

const state = reactive<Record<string, string>>({
    phone: props.contact.phone ?? '',
    email: props.contact.email ?? '',
    location: props.contact.location ?? '',
    facebook: props.contact.facebook ?? '',
    ...props.owner,
    ...Object.fromEntries(props.sections.map((s) => [s.key, s.body])),
});

const snapshot = ref(JSON.stringify(state));
const dirty = computed(() => JSON.stringify(state) !== snapshot.value);
useUnsavedWarning(() => dirty.value);
function discard() {
    Object.assign(state, JSON.parse(snapshot.value));
}

const get = (key: string): string => state[key] ?? '';
const set = (key: string, value: unknown): void => {
    state[key] = String(value ?? '');
};

const exportUrl = (type: string) => exportMethod.url(type);

const processing = ref(false);
const fieldErrors = computed(
    () => usePage().props.errors as Record<string, string | undefined>,
);

function save() {
    router.put(
        update.url(),
        { ...state },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => (snapshot.value = JSON.stringify(state)),
        },
    );
}
</script>

<template>
    <Head title="Website text" />

    <div class="max-w-3xl space-y-8 p-4 md:p-6">
        <PageHeader
            title="Website text"
            description="What visitors read on the Contact page and the How it works page. Leave a box empty and it will not appear."
            :help="[
                'Fill in your phone, email and town first. They show on the Contact page and the bottom of the home page.',
                'The boxes under How it works and policies are optional. Write them the way you would explain it on the phone. Only boxes with text appear on the website, so it is fine to leave some empty.',
                'Tap Save at the bottom when you finish. Changes show on the website straight away.',
                'Kennel owner is private. It is only used to fill in AKC forms for you and is never shown on the website.',
                'The site never makes up rules for you. Only what you write is shown.',
            ]"
            help-title="Website text"
        >
            <Button as-child variant="outline" size="sm"
                ><a href="/contact" target="_blank" rel="noopener"
                    >See the Contact page</a
                ></Button
            >
            <Button as-child variant="outline" size="sm"
                ><a href="/how-it-works" target="_blank" rel="noopener"
                    >See How it works</a
                ></Button
            >
        </PageHeader>

        <form id="main-form" class="space-y-8" @submit.prevent="save">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    Contact details
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Phone" :error="fieldErrors.phone" for="phone"
                        ><Input
                            id="phone"
                            :model-value="get('phone')"
                            @update:model-value="set('phone', $event)"
                            type="tel"
                    /></Field>
                    <Field
                        label="Email"
                        :error="fieldErrors.email"
                        for="email"
                        hint="New applications are also emailed to the logins on this site."
                        ><Input
                            id="email"
                            :model-value="get('email')"
                            @update:model-value="set('email', $event)"
                            type="email"
                    /></Field>
                    <Field
                        label="Town / state"
                        :error="fieldErrors.location"
                        for="location"
                        ><Input
                            id="location"
                            :model-value="get('location')"
                            @update:model-value="set('location', $event)"
                            placeholder="Boise, Idaho"
                    /></Field>
                    <Field
                        label="Facebook page"
                        :error="fieldErrors.facebook"
                        for="facebook"
                        ><Input
                            id="facebook"
                            :model-value="get('facebook')"
                            @update:model-value="set('facebook', $event)"
                            type="url"
                            placeholder="https://facebook.com/…"
                    /></Field>
                </div>
            </section>

            <section
                class="space-y-4 rounded-xl border border-dashed bg-card p-5"
            >
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Kennel owner
                        <span class="text-sm font-normal text-muted-foreground"
                            >(private)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Used to fill in AKC forms for you, like the litter
                        application. It is never shown on the website.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="First name"
                        :error="fieldErrors.owner_first_name"
                        for="owner_first_name"
                        ><Input
                            id="owner_first_name"
                            :model-value="get('owner_first_name')"
                            @update:model-value="
                                set('owner_first_name', $event)
                            "
                    /></Field>
                    <Field
                        label="Last name"
                        :error="fieldErrors.owner_last_name"
                        for="owner_last_name"
                        ><Input
                            id="owner_last_name"
                            :model-value="get('owner_last_name')"
                            @update:model-value="
                                set('owner_last_name', $event)
                            "
                    /></Field>
                    <Field
                        label="Street address"
                        :error="fieldErrors.owner_address"
                        for="owner_address"
                        class="sm:col-span-2"
                        ><Input
                            id="owner_address"
                            :model-value="get('owner_address')"
                            @update:model-value="set('owner_address', $event)"
                    /></Field>
                    <Field
                        label="City"
                        :error="fieldErrors.owner_city"
                        for="owner_city"
                        ><Input
                            id="owner_city"
                            :model-value="get('owner_city')"
                            @update:model-value="set('owner_city', $event)"
                    /></Field>
                    <div class="grid grid-cols-2 gap-4">
                        <Field
                            label="State"
                            :error="fieldErrors.owner_state"
                            for="owner_state"
                            hint="Two letters, like ID."
                            ><Input
                                id="owner_state"
                                maxlength="2"
                                :model-value="get('owner_state')"
                                @update:model-value="
                                    set(
                                        'owner_state',
                                        String($event).toUpperCase(),
                                    )
                                "
                        /></Field>
                        <Field
                            label="ZIP code"
                            :error="fieldErrors.owner_zip"
                            for="owner_zip"
                            ><Input
                                id="owner_zip"
                                inputmode="numeric"
                                :model-value="get('owner_zip')"
                                @update:model-value="set('owner_zip', $event)"
                        /></Field>
                    </div>
                    <Field
                        label="Phone"
                        :error="fieldErrors.owner_phone"
                        for="owner_phone"
                        hint="Leave empty to use the phone above."
                        ><Input
                            id="owner_phone"
                            type="tel"
                            :model-value="get('owner_phone')"
                            @update:model-value="set('owner_phone', $event)"
                    /></Field>
                    <Field
                        label="Email"
                        :error="fieldErrors.owner_email"
                        for="owner_email"
                        hint="Leave empty to use the email above."
                        ><Input
                            id="owner_email"
                            type="email"
                            :model-value="get('owner_email')"
                            @update:model-value="set('owner_email', $event)"
                    /></Field>
                </div>
            </section>

            <section class="space-y-5 rounded-xl border bg-card p-5">
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        How it works &amp; policies
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Buyers look for these answers before they call. Say it
                        the way you would on the phone. Only the boxes you fill
                        in are shown.
                    </p>
                </div>
                <Field
                    v-for="s in sections"
                    :key="s.key"
                    :label="s.title"
                    :error="fieldErrors[s.key]"
                    :hint="s.hint"
                    :for="s.key"
                >
                    <TextArea
                        :id="s.key"
                        :model-value="get(s.key)"
                        @update:model-value="set(s.key, $event)"
                        :rows="4"
                    />
                </Field>
            </section>

            <Button type="submit" :disabled="processing">Save</Button>

            <section
                class="space-y-3 rounded-xl border border-dashed bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">
                    Download your records
                </h2>
                <p class="text-sm text-muted-foreground">
                    Spreadsheet files (open in Excel or Google Sheets). A handy
                    backup of everything you have entered.
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="t in ['dogs', 'puppies', 'contacts', 'waitlist']"
                        :key="t"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <a :href="exportUrl(t)" download>{{
                            t[0].toUpperCase() + t.slice(1)
                        }}</a>
                    </Button>
                </div>
            </section>
        </form>

        <StickySave
            :dirty="dirty"
            :processing="processing"
            form-id="main-form"
            @discard="discard"
        />
    </div>
</template>
