<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
}>();

const state = reactive<Record<string, string>>({
    phone: props.contact.phone ?? '',
    email: props.contact.email ?? '',
    location: props.contact.location ?? '',
    facebook: props.contact.facebook ?? '',
    ...Object.fromEntries(props.sections.map((s) => [s.key, s.body])),
});

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
        />

        <form class="space-y-8" @submit.prevent="save">
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
    </div>
</template>
