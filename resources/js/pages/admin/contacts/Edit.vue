<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import DocumentManager from '@/components/admin/DocumentManager.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import { formatDateTime, labelFor, money } from '@/lib/format';
import {
    destroy as destroyNote,
    store as storeNote,
} from '@/routes/admin/contact-notes';
import { destroy, index, store, update } from '@/routes/admin/contacts';
import { edit as editLitter } from '@/routes/admin/litters';
import {
    create as newWaitlist,
    edit as editWaitlist,
} from '@/routes/admin/waitlist';
import type { Contact, Options } from '@/types';

const props = defineProps<{ contact: Contact | null }>();
const options = computed(() => usePage().props.options as Options);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Contacts', href: index() }] },
});

const form = useForm({
    name: props.contact?.name ?? '',
    type: props.contact?.type ?? 'buyer',
    email: props.contact?.email ?? '',
    phone: props.contact?.phone ?? '',
    address: props.contact?.address ?? '',
    city: props.contact?.city ?? '',
    state: props.contact?.state ?? '',
    postal_code: props.contact?.postal_code ?? '',
    notes: props.contact?.notes ?? '',
});

useUnsavedWarning(() => form.isDirty);

function submit() {
    if (props.contact) {
        form.put(update.url(props.contact.id), { preserveScroll: true });
    } else {
        form.post(store.url());
    }
}

const noteForm = useForm({ body: '' });

function addNote() {
    if (props.contact) {
        noteForm.post(storeNote.url(props.contact.id), {
            preserveScroll: true,
            onSuccess: () => noteForm.reset(),
        });
    }
}
</script>

<template>
    <Head :title="contact ? contact.name : 'Add contact'" />

    <div class="max-w-4xl space-y-8 p-4 md:p-6">
        <PageHeader
            :title="contact ? contact.name : 'Add contact'"
            :description="
                contact
                    ? labelFor(options.contactType, contact.type)
                    : 'Anyone you talk to: buyers, your vet, friends. Only the name is needed.'
            "
            :help="[
                'Contacts is your address book. Everyone who calls, texts or applies can live here, with a running log of what you talked about.',
                'Add a person here, or just add them from the Waitlist page and they appear here automatically.',
                'After you save, use Conversation notes at the bottom to jot down each call or text, with the date.',
                'Nothing here is ever shown on the website.',
            ]"
            help-title="About this page"
        >
            <template v-if="contact">
                <Button v-if="contact.phone" as-child variant="outline"
                    ><a :href="`tel:${contact.phone}`">Call</a></Button
                >
                <Button v-if="contact.email" as-child variant="outline"
                    ><a :href="`mailto:${contact.email}`">Email</a></Button
                >
                <ConfirmDelete
                    label="Delete"
                    :message="`Delete ${contact.name}? Their conversation notes and waitlist entries are deleted too, and any puppy they bought will show no buyer. This cannot be undone.`"
                    @confirm="router.delete(destroy.url(contact.id))"
                />
            </template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Name"
                        required
                        :error="form.errors.name"
                        for="name"
                        ><Input id="name" v-model="form.name" required
                    /></Field>
                    <Field
                        label="Type"
                        :error="form.errors.type"
                        help="Why this person is in your book, such as a puppy buyer, your vet, or the owner of a stud dog. It only helps you search."
                        ><NativeSelect
                            v-model="form.type"
                            :options="options.contactType"
                    /></Field>
                    <Field
                        label="Phone"
                        :error="form.errors.phone"
                        hint="With area code, like (208) 555-0123."
                        for="phone"
                        ><Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="off"
                    /></Field>
                    <Field label="Email" :error="form.errors.email" for="email"
                        ><Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                    /></Field>
                    <Field
                        label="Street address"
                        :error="form.errors.address"
                        for="address"
                        class="sm:col-span-2"
                        ><Input id="address" v-model="form.address"
                    /></Field>
                    <Field label="City" :error="form.errors.city" for="city"
                        ><Input id="city" v-model="form.city"
                    /></Field>
                    <div class="grid grid-cols-2 gap-4">
                        <Field
                            label="State"
                            :error="form.errors.state"
                            for="state"
                            ><Input id="state" v-model="form.state"
                        /></Field>
                        <Field
                            label="ZIP"
                            :error="form.errors.postal_code"
                            for="postal_code"
                            ><Input id="postal_code" v-model="form.postal_code"
                        /></Field>
                    </div>
                </div>
                <Field label="Notes" :error="form.errors.notes" for="notes">
                    <TextArea
                        id="notes"
                        v-model="form.notes"
                        :rows="3"
                        placeholder="What they are looking for, where they heard about you…"
                    />
                </Field>
                <Button type="submit" :disabled="form.processing">{{
                    contact ? 'Save changes' : 'Add contact'
                }}</Button>
            </section>
        </form>

        <template v-if="contact">
            <section class="space-y-3 rounded-xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-serif text-lg font-semibold">Waitlist</h2>
                    <Button as-child size="sm" variant="secondary"
                        ><Link
                            :href="
                                newWaitlist({ query: { contact: contact.id } })
                            "
                            >Add to a litter</Link
                        ></Button
                    >
                </div>
                <p
                    v-if="!contact.waitlist_entries?.length"
                    class="text-sm text-muted-foreground"
                >
                    Not on any waitlist.
                </p>
                <ul class="divide-y">
                    <li v-for="e in contact.waitlist_entries" :key="e.id">
                        <Link
                            :href="editWaitlist(e.id)"
                            class="flex items-center justify-between gap-3 py-2 text-sm hover:underline"
                        >
                            <span
                                >{{ e.litter?.title ?? 'Any upcoming litter' }}
                                <span
                                    v-if="e.deposit_amount"
                                    class="text-muted-foreground"
                                    >· deposit
                                    {{ money(e.deposit_amount) }}</span
                                ></span
                            >
                            <StatusBadge
                                :value="e.status"
                                :label="
                                    labelFor(options.waitlistStatus, e.status)
                                "
                            />
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                v-if="contact.puppies?.length"
                class="space-y-3 rounded-xl border bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">Puppies</h2>
                <ul class="divide-y text-sm">
                    <li v-for="p in contact.puppies" :key="p.id" class="py-2">
                        {{ p.name || p.collar || 'Puppy' }} ·
                        <Link
                            v-if="p.litter"
                            :href="editLitter(p.litter.slug)"
                            class="underline"
                            >{{ p.litter.title }}</Link
                        >
                        <span v-if="p.sale_price" class="text-muted-foreground">
                            · {{ money(p.sale_price) }}</span
                        >
                    </li>
                </ul>
            </section>

            <section
                v-if="contact"
                class="space-y-3 rounded-xl border bg-card p-5"
            >
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Agreements &amp; papers
                        <span class="text-sm font-normal text-muted-foreground"
                            >(private)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        A signed stud agreement, a bill of sale or anything else
                        to do with this person.
                    </p>
                </div>
                <DocumentManager
                    type="contact"
                    :id="contact.id"
                    :documents="contact.documents ?? []"
                    category="sale"
                />
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    Conversation log
                </h2>
                <form class="space-y-2" @submit.prevent="addNote">
                    <TextArea
                        v-model="noteForm.body"
                        :rows="2"
                        placeholder="Called Tuesday, wants a female, will send deposit Friday…"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        variant="secondary"
                        :disabled="noteForm.processing || !noteForm.body"
                        >Add note</Button
                    >
                </form>
                <ul class="divide-y">
                    <li
                        v-for="n in contact.contact_notes"
                        :key="n.id"
                        class="flex items-start justify-between gap-3 py-3"
                    >
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDateTime(n.noted_at) }}
                            </p>
                            <p class="text-sm whitespace-pre-line">
                                {{ n.body }}
                            </p>
                        </div>
                        <ConfirmDelete
                            icon
                            message="Delete this note?"
                            @confirm="
                                router.delete(destroyNote.url(n.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
