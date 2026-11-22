<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import MoneyInput from '@/components/admin/MoneyInput.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StickySave from '@/components/admin/StickySave.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import { destroy, index, store, update } from '@/routes/admin/waitlist';
import type { Options, WaitlistEntry } from '@/types';

const props = defineProps<{
    entry: WaitlistEntry | null;
    contacts: { id: number; name: string }[];
    litters: { id: number; title: string; next_pick: number }[];
    puppies?: {
        id: number;
        name: string | null;
        collar: string | null;
        sex: string;
    }[];
    presetContact?: number | null;
    presetLitter?: number | null;
}>();
const options = computed(() => usePage().props.options as Options);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Waitlist', href: index() }] },
});

const newPerson = ref(
    !props.entry && !props.presetContact && !props.contacts.length,
);

const form = useForm({
    contact_id: (props.entry?.contact_id ?? props.presetContact ?? null) as
        | number
        | null,
    new_name: '',
    new_phone: '',
    new_email: '',
    litter_id: (props.entry?.litter_id ?? props.presetLitter ?? null) as
        | number
        | null,
    puppy_id: props.entry?.puppy_id ?? null,
    preferred_sex: props.entry?.preferred_sex ?? null,
    preferred_color: props.entry?.preferred_color ?? null,
    pick_order: props.entry?.pick_order ?? null,
    status: props.entry?.status ?? 'waiting',
    deposit_amount: props.entry?.deposit_amount ?? null,
    deposit_paid_on: props.entry?.deposit_paid_on ?? '',
    notes: props.entry?.notes ?? '',
});

useUnsavedWarning(() => form.isDirty);
const formDirty = computed(() => form.isDirty);
const formBusy = computed(() => form.processing);
const discardChanges = () => form.reset();

const nextPick = computed(
    () => props.litters.find((l) => l.id === form.litter_id)?.next_pick ?? null,
);

// Moving someone to "Deposit paid" is the moment she has the money in hand: save a step.
watch(
    () => form.status,
    (status) => {
        if (status === 'deposit_paid' && !form.deposit_paid_on) {
            form.deposit_paid_on = new Date().toLocaleDateString('en-CA');
        }
    },
);

function submit() {
    if (newPerson.value) {
        form.contact_id = null;
    }

    if (props.entry) {
        form.put(update.url(props.entry.id));
    } else {
        form.post(store.url());
    }
}
</script>

<template>
    <Head :title="entry ? 'Edit waitlist entry' : 'Add to waitlist'" />

    <div class="max-w-3xl space-y-6 p-4 md:p-6">
        <PageHeader
            :title="
                entry
                    ? (entry.contact?.name ?? 'Waitlist entry')
                    : 'Add to waitlist'
            "
            :help="[
                'The waitlist is the list of people who want a puppy. Add someone when they call, text or apply.',
                'Choose the person (or type a new one), the litter they want, and whether they prefer a boy or a girl or a colour.',
                'Change the status as things move along. When they pay a deposit, set Deposit paid and fill in the amount.',
                'The pick order says who chooses first when the puppies are old enough to pick. Pick 1 chooses first.',
            ]"
            help-title="About this page"
        >
            <Button v-if="entry?.contact?.phone" as-child variant="outline"
                ><a :href="`tel:${entry.contact.phone}`">Call</a></Button
            >
            <Button v-if="entry?.contact?.email" as-child variant="outline"
                ><a :href="`mailto:${entry.contact.email}`">Email</a></Button
            >
            <ConfirmDelete
                v-if="entry"
                label="Remove"
                :message="`Remove ${entry.contact?.name ?? 'this person'} from the waitlist? They stay in your Contacts. The deposit amount and notes on this entry are deleted.`"
                @confirm="router.delete(destroy.url(entry.id))"
            />
        </PageHeader>

        <section
            v-if="entry?.application && Object.keys(entry.application).length"
            class="space-y-3 rounded-xl border border-dashed bg-card p-5"
        >
            <h2 class="font-serif text-lg font-semibold">
                Their application
                <span class="text-sm font-normal text-muted-foreground"
                    >(from the website)</span
                >
            </h2>
            <dl class="space-y-3 text-sm">
                <div
                    v-for="(answer, question) in entry.application"
                    :key="question"
                >
                    <dt class="text-muted-foreground">{{ question }}</dt>
                    <dd class="whitespace-pre-line">{{ answer }}</dd>
                </div>
            </dl>
        </section>

        <form id="main-form" class="space-y-6" @submit.prevent="submit">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">Who</h2>

                <template v-if="!entry">
                    <div class="flex gap-2 text-sm">
                        <Button
                            type="button"
                            size="sm"
                            :variant="newPerson ? 'outline' : 'default'"
                            @click="newPerson = false"
                            >Existing contact</Button
                        >
                        <Button
                            type="button"
                            size="sm"
                            :variant="newPerson ? 'default' : 'outline'"
                            @click="newPerson = true"
                            >New person</Button
                        >
                    </div>
                </template>

                <Field
                    v-if="!newPerson"
                    label="Person"
                    required
                    :error="form.errors.contact_id"
                    hint="Not in the list yet? Tap “New person” above."
                >
                    <NativeSelect
                        v-model="form.contact_id"
                        :options="
                            contacts.map((c) => ({
                                value: String(c.id),
                                label: c.name,
                            }))
                        "
                        placeholder="Choose…"
                    />
                </Field>
                <div v-else class="grid gap-4 sm:grid-cols-3">
                    <Field
                        label="Name"
                        required
                        :error="form.errors.new_name"
                        for="new_name"
                        ><Input id="new_name" v-model="form.new_name"
                    /></Field>
                    <Field
                        label="Phone"
                        :error="form.errors.new_phone"
                        for="new_phone"
                        ><Input
                            id="new_phone"
                            v-model="form.new_phone"
                            type="tel"
                    /></Field>
                    <Field
                        label="Email"
                        :error="form.errors.new_email"
                        for="new_email"
                        ><Input
                            id="new_email"
                            v-model="form.new_email"
                            type="email"
                    /></Field>
                </div>
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">What they want</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Litter"
                        :error="form.errors.litter_id"
                        help="Which litter they are waiting for. Leave it on “Any upcoming litter” if they have not chosen one."
                    >
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
                    <Field
                        label="Pick order"
                        :error="form.errors.pick_order"
                        help="The order people choose their puppy in. 1 chooses first, 2 second, and so on. Each number can only be used once per litter."
                        for="pick_order"
                    >
                        <Input
                            id="pick_order"
                            v-model="form.pick_order"
                            type="number"
                            inputmode="numeric"
                            min="1"
                        />
                        <button
                            v-if="nextPick && form.pick_order !== nextPick"
                            type="button"
                            class="text-left text-xs text-primary underline underline-offset-2"
                            @click="form.pick_order = nextPick"
                        >
                            Use the next free number ({{ nextPick }})
                        </button>
                    </Field>
                    <Field label="Prefers" :error="form.errors.preferred_sex"
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
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    Status &amp; deposit
                </h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Field
                        label="Status"
                        required
                        :error="form.errors.status"
                        help="New application: came in from the website and not answered yet. Waiting: on the list. Contacted: you have talked. Deposit paid: they have paid to hold a place. Placed: they have their puppy. Passed: they decided not to get one."
                        ><NativeSelect
                            v-model="form.status"
                            :options="options.waitlistStatus"
                    /></Field>
                    <Field
                        label="Deposit ($)"
                        :error="form.errors.deposit_amount"
                        hint="Whole dollars, like 300."
                        for="deposit_amount"
                        ><MoneyInput
                            id="deposit_amount"
                            v-model="form.deposit_amount"
                            placeholder="300"
                    /></Field>
                    <Field
                        label="Deposit received"
                        :error="form.errors.deposit_paid_on"
                        for="deposit_paid_on"
                        ><Input
                            id="deposit_paid_on"
                            v-model="form.deposit_paid_on"
                            type="date"
                    /></Field>
                </div>
                <Field
                    v-if="entry && puppies?.length"
                    label="Matched puppy"
                    :error="form.errors.puppy_id"
                    hint="Choose the puppy once they have picked one."
                >
                    <NativeSelect
                        v-model="form.puppy_id"
                        :options="
                            puppies.map((p) => ({
                                value: String(p.id),
                                label: p.name || p.collar || `Puppy ${p.id}`,
                            }))
                        "
                        placeholder="Not matched yet"
                    />
                </Field>
                <Field label="Notes" :error="form.errors.notes" for="notes"
                    ><TextArea id="notes" v-model="form.notes" :rows="3"
                /></Field>
            </section>

            <Button type="submit" :disabled="form.processing">{{
                entry ? 'Save changes' : 'Add to waitlist'
            }}</Button>
        </form>

        <StickySave
            :dirty="formDirty"
            :processing="formBusy"
            form-id="main-form"
            @discard="discardChanges"
        />
    </div>
</template>
