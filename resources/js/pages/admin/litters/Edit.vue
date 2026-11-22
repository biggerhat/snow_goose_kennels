<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AkcChecklist from '@/components/admin/AkcChecklist.vue';
import ArchivedBanner from '@/components/admin/ArchivedBanner.vue';
import type { AkcItem } from '@/components/admin/AkcChecklist.vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import { stud as studAgreement } from '@/routes/admin/records';
import LitterSchedule from '@/components/admin/LitterSchedule.vue';
import PageTabs from '@/components/admin/PageTabs.vue';
import type { PageTab } from '@/components/admin/PageTabs.vue';
import TabPanel from '@/components/admin/TabPanel.vue';
import { usePageTabs } from '@/composables/usePageTabs';
import StickySave from '@/components/admin/StickySave.vue';
import DocumentManager from '@/components/admin/DocumentManager.vue';
import Field from '@/components/admin/Field.vue';
import HealthRecords from '@/components/admin/HealthRecords.vue';
import MoneyInput from '@/components/admin/MoneyInput.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PhotoManager from '@/components/admin/PhotoManager.vue';
import QuickDog from '@/components/admin/QuickDog.vue';
import { planner as plannerUrl } from '@/routes/admin';
import PuppyRow from '@/components/admin/PuppyRow.vue';
import WeighIns from '@/components/admin/WeighIns.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import { formatDate, labelFor, money } from '@/lib/format';
import {
    destroy as destroyDiary,
    store as storeDiary,
} from '@/routes/admin/diary';
import {
    akc as akcApplication,
    destroy,
    index,
    purge,
    restore,
    store,
    update,
} from '@/routes/admin/litters';
import { store as storePuppy } from '@/routes/admin/puppies';
import {
    create as newWaitlist,
    edit as editWaitlist,
} from '@/routes/admin/waitlist';
import type { Dog, Litter, Options } from '@/types';

const props = defineProps<{
    litter: Litter | null;
    dogs: Pick<Dog, 'id' | 'name' | 'sex' | 'is_ours'>[];
    contacts?: { id: number; name: string }[];
    akc?: { items: AkcItem[]; todo: number; warn: number };
    damHistory?: { text: string } | null;
    preset?: { sire: number | null; dam: number | null };
    inbreeding?: {
        percent: number;
        generations: number;
        common: string[];
    } | null;
}>();
const options = computed(() => usePage().props.options as Options);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Litters', href: index() }] },
});

const form = useForm({
    name: props.litter?.name ?? '',
    sire_id: props.litter?.sire_id ?? props.preset?.sire ?? null,
    dam_id: props.litter?.dam_id ?? props.preset?.dam ?? null,
    status: props.litter?.status ?? 'planned',
    bred_on: props.litter?.bred_on ?? '',
    due_on: props.litter?.due_on ?? '',
    born_on: props.litter?.born_on ?? '',
    go_home_on: props.litter?.go_home_on ?? '',
    price: props.litter?.price ?? null,
    deposit: props.litter?.deposit ?? null,
    expected_count: props.litter?.expected_count ?? null,
    description: props.litter?.description ?? '',
    is_public: props.litter?.is_public ?? true,
    private_notes: props.litter?.private_notes ?? '',
    breeding_method: props.litter?.breeding_method ?? null,
    bred_place: props.litter?.bred_place ?? '',
    bred_handler: props.litter?.bred_handler ?? '',
    stud_fee: props.litter?.stud_fee ?? null,
    stud_fee_paid_on: props.litter?.stud_fee_paid_on ?? '',
    stillborn_count: props.litter?.stillborn_count ?? null,
    akc_applied_on: props.litter?.akc_applied_on ?? '',
    akc_litter_number: props.litter?.akc_litter_number ?? '',
    sire_owner_signed_on: props.litter?.sire_owner_signed_on ?? '',
    dam_owner_signed_on: props.litter?.dam_owner_signed_on ?? '',
    multiple_sires: props.litter?.multiple_sires ?? false,
    akc_marketplace_listed_on: props.litter?.akc_marketplace_listed_on ?? '',
});

useUnsavedWarning(() => form.isDirty);

function addDays(iso: string, days: number): string {
    const [y, m, d] = iso.split('-').map(Number);

    return new Date(y, m - 1, d + days).toLocaleDateString('en-CA');
}

// Labs carry puppies about 63 days; puppies are usually ready at 8 weeks.
const suggestedDue = computed(() =>
    form.bred_on && !form.due_on && !form.born_on
        ? addDays(form.bred_on, 63)
        : null,
);
const suggestedHome = computed(() => {
    const base = form.born_on || form.due_on;

    return base && !form.go_home_on ? addDays(base, 56) : null;
});

const litterSummary = computed(() => {
    const l = props.litter;

    if (!l) {
        return '';
    }

    const status = labelFor(options.value.litterStatus, l.status);
    const bornStatus = l.status === 'on_ground' || l.status === 'all_home';
    const date = bornStatus ? l.born_on : l.due_on;

    return date
        ? `${status} · ${bornStatus ? 'born' : 'due'} ${formatDate(date)}`
        : status;
});

const dogOptions = (sex: string) =>
    props.dogs
        .filter((d) => d.sex === sex)
        .map((d) => ({
            value: String(d.id),
            label: d.is_ours === false ? `${d.name} (another kennel)` : d.name,
        }));
const males = computed(() => dogOptions('male'));
const females = computed(() => dogOptions('female'));

// Pick a dog the moment it has been added from the quick form.
const justAdded = ref<{ key: 'sire_id' | 'dam_id'; name: string } | null>(null);
watch(
    () => props.dogs,
    (list) => {
        const pending = justAdded.value;
        const dog = pending && list.find((d) => d.name === pending.name);

        if (pending && dog) {
            form[pending.key] = dog.id;
            justAdded.value = null;
        }
    },
);

const tabs = computed<PageTab[]>(() => [
    { key: 'details', label: 'Details' },
    { key: 'puppies', label: 'Puppies', count: props.litter?.puppies?.length },
    {
        key: 'waitlist',
        label: 'Waitlist',
        count: props.litter?.waitlist_entries?.length,
    },
    { key: 'health', label: 'Health & diary' },
    { key: 'akc', label: 'Breeding & AKC' },
    { key: 'papers', label: 'Papers & photos' },
]);
const tab = usePageTabs(
    ['details', 'puppies', 'waitlist', 'health', 'akc', 'papers'],
    'details',
);

function submit() {
    if (props.litter) {
        form.put(update.url(props.litter.slug), {
            preserveScroll: true,
            // A mistake in the form shows on the tab that holds it; Details is the likeliest.
            onError: (e) => {
                if (
                    tab.value !== 'akc' ||
                    !Object.keys(e).some((k) =>
                        /akc|sire_owner|dam_owner|breed|stud|stillborn|multiple/.test(
                            k,
                        ),
                    )
                ) {
                    tab.value = 'details';
                }
            },
        });
    } else {
        form.post(store.url());
    }
}

const diaryForm = useForm({
    posted_on: new Date().toLocaleDateString('en-CA'),
    title: '',
    body: '',
    is_public: true,
});

function postDiary() {
    if (props.litter) {
        diaryForm.post(storeDiary.url(props.litter.slug), {
            preserveScroll: true,
            onSuccess: () => diaryForm.reset('title', 'body'),
        });
    }
}

function addPuppy(sex: 'male' | 'female') {
    if (props.litter) {
        router.post(
            storePuppy.url(props.litter.slug),
            { sex, status: 'available' },
            { preserveScroll: true },
        );
    }
}

const summary = computed(() => {
    const p = props.litter?.puppies ?? [];

    return {
        total: p.length,
        available: p.filter((x) => x.status === 'available').length,
        reserved: p.filter((x) => x.status === 'reserved').length,
        home: p.filter((x) => x.status === 'home').length,
    };
});
</script>

<template>
    <Head :title="litter ? litter.title : 'New litter'" />

    <div class="max-w-5xl space-y-8 p-4 md:p-6">
        <PageHeader
            :title="litter ? litter.title : 'New litter'"
            :description="
                litter
                    ? `${litterSummary}`
                    : 'Pick the pairing and the dates. You can fill in the rest later.'
            "
            :help="[
                'Start with the mother (dam), the father (sire) and the status. Everything else can be added later.',
                'Change the status as things happen: Planned, then Expected, then On the ground when puppies are born.',
                'Once the litter exists, scroll down to add each puppy, record weights, post updates for families, and see who is waiting.',
                'Breeding & AKC keeps the paperwork side: how and where they were bred, any stud fee, and when the AKC application went in. The AKC checklist on that tab tells you what is still missing.',
                'The “Print a stud agreement” button makes a paper for the owners of the sire and dam to sign. The “Fill in the AKC litter application” button in Breeding & AKC lines up everything AKC asks for and fills in AKC’s own PDF form for you.',
                'If the father belongs to another kennel, pick “not in the list?” under the Sire box to add him. Keep the signed stud agreement on the Papers & photos tab.',
                'Untick Show on the website to hide a litter while you decide. Nothing is lost.',
            ]"
            help-title="About this page"
        >
        </PageHeader>

        <PageTabs v-if="litter" v-model="tab" :tabs="tabs" />

        <aside
            v-if="
                litter &&
                !litter.deleted_at &&
                litter.status === 'on_ground' &&
                !litter.puppies?.length
            "
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm"
        >
            <p>
                <strong>Next:</strong> the puppies have arrived, so add each
                one. Tap “+ Add girl puppy” or “+ Add boy puppy” on the Puppies
                tab.
            </p>
            <Button type="button" size="sm" @click="tab = 'puppies'"
                >Go to Puppies</Button
            >
        </aside>

        <ArchivedBanner
            v-if="litter?.deleted_at"
            what="This litter"
            :blocked-until="litter.purge_blocked_until"
            @restore="router.post(restore.url(litter.slug))"
            @purge="router.delete(purge.url(litter.slug))"
        />

        <form
            v-show="!litter || tab === 'details' || tab === 'akc'"
            id="main-form"
            class="space-y-6"
            @submit.prevent="submit"
        >
            <section
                v-show="!litter || tab === 'details'"
                class="space-y-4 rounded-xl border bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">
                    Pairing &amp; dates
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Dam (mother)"
                        :error="form.errors.dam_id"
                        help="The mother of the puppies. Only female dogs are listed, including mothers from other kennels. If she is missing, use the link under the box to add her."
                    >
                        <NativeSelect
                            v-model="form.dam_id"
                            :options="females"
                            placeholder="Choose…"
                        />
                        <QuickDog
                            sex="female"
                            label="The mother is"
                            reload="dogs"
                            :contacts="contacts ?? []"
                            @added="
                                (n) => (justAdded = { key: 'dam_id', name: n })
                            "
                        />
                    </Field>
                    <Field
                        label="Sire (father)"
                        :error="form.errors.sire_id"
                        help="The father of the puppies. Only male dogs are listed, including studs from other kennels. If he belongs to someone else, use the link under the box to add him."
                    >
                        <NativeSelect
                            v-model="form.sire_id"
                            :options="males"
                            placeholder="Choose…"
                        />
                        <QuickDog
                            sex="male"
                            label="The father is"
                            reload="dogs"
                            :contacts="contacts ?? []"
                            @added="
                                (n) => (justAdded = { key: 'sire_id', name: n })
                            "
                        />
                    </Field>
                    <Field
                        label="Status"
                        required
                        :error="form.errors.status"
                        help="Planned: thinking about it. Expected: the mother is bred and you are waiting. On the ground: puppies are born. All gone home: every puppy has left. It switches to All gone home by itself when you mark the last puppy as gone home."
                    >
                        <NativeSelect
                            v-model="form.status"
                            :options="options.litterStatus"
                        />
                    </Field>
                    <Field
                        label="Litter name (optional)"
                        :error="form.errors.name"
                        hint="Leave blank to show “Dam × Sire”."
                        for="name"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="The Spring Marsh Litter"
                        />
                    </Field>
                </div>
                <p
                    v-if="damHistory"
                    class="rounded-lg border bg-background/50 p-3 text-sm"
                >
                    <strong>The mother’s litters:</strong>
                    {{ damHistory.text }}
                </p>
                <p
                    v-if="inbreeding"
                    class="rounded-lg border bg-background/50 p-3 text-sm"
                >
                    <strong>How closely related the parents are:</strong>
                    {{ inbreeding.percent }}% expected inbreeding, from
                    {{ inbreeding.generations }} generation{{
                        inbreeding.generations === 1 ? '' : 's'
                    }}
                    of known ancestors.
                    <span v-if="inbreeding.common.length">
                        Both families include
                        {{ inbreeding.common.join(', ') }}.
                    </span>
                    <Link
                        :href="
                            plannerUrl.url({
                                query: {
                                    sire: form.sire_id ?? undefined,
                                    dam: form.dam_id ?? undefined,
                                },
                            })
                        "
                        class="underline underline-offset-2"
                        >Open the pairing planner</Link
                    >
                </p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field
                        label="Bred on"
                        :error="form.errors.bred_on"
                        hint="The day of the mating."
                        for="bred_on"
                        ><Input id="bred_on" v-model="form.bred_on" type="date"
                    /></Field>
                    <Field
                        label="Due date"
                        :error="form.errors.due_on"
                        for="due_on"
                        ><Input id="due_on" v-model="form.due_on" type="date" />
                        <button
                            v-if="suggestedDue"
                            type="button"
                            class="text-left text-xs text-primary underline underline-offset-2"
                            @click="form.due_on = suggestedDue"
                        >
                            Use {{ formatDate(suggestedDue) }} (63 days after
                            breeding)
                        </button></Field
                    >
                    <Field
                        label="Born on"
                        :error="form.errors.born_on"
                        hint="Fill in once the puppies arrive."
                        for="born_on"
                        ><Input id="born_on" v-model="form.born_on" type="date"
                    /></Field>
                    <Field
                        label="Go-home date"
                        :error="form.errors.go_home_on"
                        for="go_home_on"
                        help="The day puppies leave for their new homes. Most breeders wait until they are 8 weeks old."
                        ><Input
                            id="go_home_on"
                            v-model="form.go_home_on"
                            type="date"
                        />
                        <button
                            v-if="suggestedHome"
                            type="button"
                            class="text-left text-xs text-primary underline underline-offset-2"
                            @click="form.go_home_on = suggestedHome"
                        >
                            Use {{ formatDate(suggestedHome) }} (8 weeks old)
                        </button></Field
                    >
                </div>
            </section>

            <section
                v-show="!litter || tab === 'akc'"
                class="space-y-4 rounded-xl border bg-card p-5"
            >
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Breeding &amp; AKC
                        <span class="text-sm font-normal text-muted-foreground"
                            >(private)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        AKC asks you to keep a record of every mating and every
                        litter. Fill in what you know; the checklist below shows
                        what is still missing.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="How they were bred"
                        :error="form.errors.breeding_method"
                        help="Natural mating is the usual way. Artificial insemination needs extra AKC paperwork and a DNA profile for the sire."
                    >
                        <NativeSelect
                            v-model="form.breeding_method"
                            :options="options.breedingMethod"
                            placeholder="Not recorded"
                        />
                    </Field>
                    <Field
                        label="Where"
                        :error="form.errors.bred_place"
                        for="bred_place"
                        hint="Town or kennel where the mating took place."
                    >
                        <Input id="bred_place" v-model="form.bred_place" />
                    </Field>
                    <Field
                        label="Who handled the mating"
                        :error="form.errors.bred_handler"
                        for="bred_handler"
                    >
                        <Input id="bred_handler" v-model="form.bred_handler" />
                    </Field>
                    <Field
                        label="Stud fee ($)"
                        :error="form.errors.stud_fee"
                        for="stud_fee"
                        help="Money paid for the stud’s services, either to the other kennel for their stud or to you for yours. Leave empty if there was none. Keep the signed stud agreement under Documents."
                        hint="Whole dollars."
                    >
                        <MoneyInput id="stud_fee" v-model="form.stud_fee" />
                    </Field>
                    <Field
                        label="Stud fee paid on"
                        :error="form.errors.stud_fee_paid_on"
                        for="stud_fee_paid_on"
                    >
                        <Input
                            id="stud_fee_paid_on"
                            v-model="form.stud_fee_paid_on"
                            type="date"
                        />
                    </Field>
                    <Field
                        label="Puppies born not alive"
                        :error="form.errors.stillborn_count"
                        for="stillborn_count"
                        hint="Only if any were stillborn or died soon after birth."
                    >
                        <Input
                            id="stillborn_count"
                            v-model="form.stillborn_count"
                            type="number"
                            inputmode="numeric"
                            min="0"
                        />
                    </Field>
                    <Field
                        label="AKC application sent on"
                        :error="form.errors.akc_applied_on"
                        for="akc_applied_on"
                        help="The date you sent in the Application for Litter Registration. AKC wants it within 6 months of the birth date."
                    >
                        <Input
                            id="akc_applied_on"
                            v-model="form.akc_applied_on"
                            type="date"
                        />
                    </Field>
                    <Field
                        label="AKC litter number"
                        :error="form.errors.akc_litter_number"
                        for="akc_litter_number"
                        hint="Printed on the papers AKC sends back."
                    >
                        <Input
                            id="akc_litter_number"
                            v-model="form.akc_litter_number"
                        />
                    </Field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="Sire’s owner signed on"
                        :error="form.errors.sire_owner_signed_on"
                        for="sire_owner_signed_on"
                        help="The litter application has to be signed by the sire’s owner, certifying the mating. If the sire belongs to someone else, you need their signature. Write the date they signed."
                    >
                        <Input
                            id="sire_owner_signed_on"
                            v-model="form.sire_owner_signed_on"
                            type="date"
                        />
                    </Field>
                    <Field
                        label="Dam’s owner(s) signed on"
                        :error="form.errors.dam_owner_signed_on"
                        for="dam_owner_signed_on"
                        help="Every owner or lessee of the dam signs the application. Write the date the last one signed."
                    >
                        <Input
                            id="dam_owner_signed_on"
                            v-model="form.dam_owner_signed_on"
                            type="date"
                        />
                    </Field>
                    <Field
                        label="Listed on AKC Marketplace on"
                        :error="form.errors.akc_marketplace_listed_on"
                        for="akc_marketplace_listed_on"
                        hint="Only if you listed the litter there."
                    >
                        <Input
                            id="akc_marketplace_listed_on"
                            v-model="form.akc_marketplace_listed_on"
                            type="date"
                        />
                    </Field>
                </div>
                <ToggleField
                    v-model="form.multiple_sires"
                    label="More than one male could be the father"
                    hint="For example, the mother was bred to two dogs. AKC then needs DNA profiles for every possible sire, the dam and all the puppies."
                />
                <div v-if="litter" class="flex flex-wrap gap-2">
                    <Button as-child variant="secondary" size="sm">
                        <Link :href="akcApplication.url(litter.slug)"
                            >Fill in the AKC litter application</Link
                        >
                    </Button>
                    <Button as-child variant="secondary" size="sm">
                        <a
                            :href="studAgreement.url(litter.slug)"
                            target="_blank"
                            rel="noopener"
                            >Print a stud agreement</a
                        >
                    </Button>
                </div>
                <div v-if="litter && akc" class="space-y-2">
                    <p class="font-medium">AKC checklist</p>
                    <AkcChecklist :akc="akc" />
                </div>
            </section>

            <section
                v-show="!litter || tab === 'details'"
                class="space-y-4 rounded-xl border bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">
                    Price &amp; website
                    <span class="text-sm font-normal text-muted-foreground"
                        >(shown to the public)</span
                    >
                </h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Field
                        label="Price per puppy ($)"
                        :error="form.errors.price"
                        hint="Whole dollars, like 1500."
                        for="price"
                        ><MoneyInput
                            id="price"
                            v-model="form.price"
                            placeholder="1500"
                    /></Field>
                    <Field
                        label="Deposit ($)"
                        :error="form.errors.deposit"
                        help="The amount a buyer pays up front to hold a place on the list. It is usually taken off the price."
                        hint="Whole dollars, like 300."
                        for="deposit"
                        ><MoneyInput
                            id="deposit"
                            v-model="form.deposit"
                            placeholder="300"
                    /></Field>
                    <Field
                        label="Expected pups"
                        :error="form.errors.expected_count"
                        hint="A rough number is fine."
                        for="expected_count"
                        ><Input
                            id="expected_count"
                            v-model="form.expected_count"
                            type="number"
                            inputmode="numeric"
                            min="0"
                    /></Field>
                </div>
                <Field
                    label="About this litter"
                    :error="form.errors.description"
                    for="description"
                >
                    <TextArea
                        id="description"
                        v-model="form.description"
                        :rows="4"
                        placeholder="What to expect from this pairing, colours, temperament…"
                    />
                </Field>
                <ToggleField
                    v-model="form.is_public"
                    label="Show on the website"
                    hint="Tick to show this litter on the Litters page. Untick to keep it private while you decide."
                />
            </section>

            <section
                v-show="!litter || tab === 'details'"
                class="space-y-3 rounded-xl border border-dashed bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">
                    Private notes
                    <span class="text-sm font-normal text-muted-foreground"
                        >(only you)</span
                    >
                </h2>
                <TextArea
                    v-model="form.private_notes"
                    :rows="3"
                    placeholder="Whelping notes, vet visits, anything to remember…"
                />
            </section>

            <Button type="submit" :disabled="form.processing">{{
                litter ? 'Save changes' : 'Create litter'
            }}</Button>
        </form>

        <template v-if="litter">
            <TabPanel name="puppies" :active="tab === 'puppies'">
                <section class="space-y-4 rounded-xl border bg-card p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="font-serif text-lg font-semibold">
                                Puppies
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ summary.total }} total ·
                                {{ summary.available }} available ·
                                {{ summary.reserved }} reserved ·
                                {{ summary.home }} gone home
                                <span v-if="litter.price">
                                    · {{ money(litter.price) }} each</span
                                >
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="addPuppy('female')"
                                >+ Add girl puppy</Button
                            >
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="addPuppy('male')"
                                >+ Add boy puppy</Button
                            >
                        </div>
                    </div>
                    <p
                        v-if="!litter.puppies?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No puppies yet. Tap “Add girl puppy” or “Add boy puppy”
                        once for each pup as they are born, then give each a
                        collar colour so you can tell them apart.
                    </p>
                    <div class="space-y-2">
                        <PuppyRow
                            v-for="p in litter.puppies"
                            :key="p.id"
                            :puppy="p"
                            :sex="options.sex"
                            :color="options.color"
                            :status="options.puppyStatus"
                            :contacts="contacts ?? []"
                        />
                    </div>
                </section>
            </TabPanel>

            <TabPanel name="waitlist" :active="tab === 'waitlist'">
                <section class="space-y-3 rounded-xl border bg-card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-serif text-lg font-semibold">
                            Waitlist for this litter
                        </h2>
                        <Button as-child size="sm" variant="secondary"
                            ><Link
                                :href="
                                    newWaitlist({
                                        query: { litter: litter.id },
                                    })
                                "
                                >Add person</Link
                            ></Button
                        >
                    </div>
                    <p
                        v-if="!litter.waitlist_entries?.length"
                        class="text-sm text-muted-foreground"
                    >
                        Nobody is on the list for this litter yet.
                    </p>
                    <ul class="divide-y">
                        <li v-for="e in litter.waitlist_entries" :key="e.id">
                            <Link
                                :href="editWaitlist(e.id)"
                                class="flex items-center justify-between gap-3 py-2 text-sm hover:underline"
                            >
                                <span>
                                    <span
                                        v-if="e.pick_order"
                                        class="mr-1 font-semibold"
                                        >#{{ e.pick_order }}</span
                                    >{{ e.contact?.name }}
                                    <span class="text-muted-foreground">{{
                                        e.contact?.phone
                                    }}</span>
                                </span>
                                <StatusBadge
                                    :value="e.status"
                                    :label="
                                        labelFor(
                                            options.waitlistStatus,
                                            e.status,
                                        )
                                    "
                                />
                            </Link>
                        </li>
                    </ul>
                </section>
            </TabPanel>

            <TabPanel name="health" :active="tab === 'health'">
                <p class="text-sm text-muted-foreground">
                    Each box here saves by itself when you tap its own Add or
                    Save button.
                </p>
                <section class="space-y-3 rounded-xl border bg-card p-5">
                    <h2 class="font-serif text-lg font-semibold">
                        Puppy schedule
                    </h2>
                    <LitterSchedule
                        :slug="litter.slug"
                        :tasks="litter.tasks ?? []"
                        :has-birth-date="!!litter.born_on"
                    />
                </section>

                <section
                    v-if="litter.puppies?.length"
                    class="space-y-3 rounded-xl border bg-card p-5"
                >
                    <div>
                        <h2 class="font-serif text-lg font-semibold">
                            Litter health: deworming, vaccines &amp; vet checks
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Did the whole litter get dewormed or vaccinated
                            today? Record it once here and it is added to every
                            puppy. Each puppy’s own records are in its card
                            above.
                        </p>
                    </div>
                    <HealthRecords target="litter" :id="litter.slug" />
                </section>

                <section
                    v-if="litter.puppies?.length"
                    class="space-y-3 rounded-xl border bg-card p-5"
                >
                    <h2 class="font-serif text-lg font-semibold">Weigh-ins</h2>
                    <WeighIns :slug="litter.slug" :puppies="litter.puppies" />
                </section>

                <section class="space-y-4 rounded-xl border bg-card p-5">
                    <div>
                        <h2 class="font-serif text-lg font-semibold">
                            Puppy diary
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            A short note each week (eyes open, first steps,
                            first bath) shows on the litter's page. Families
                            love these.
                        </p>
                    </div>
                    <form
                        class="space-y-3 rounded-lg border bg-background/50 p-3"
                        @submit.prevent="postDiary"
                    >
                        <div class="grid gap-3 sm:grid-cols-[11rem_1fr]">
                            <Field
                                label="Date"
                                :error="diaryForm.errors.posted_on"
                                ><Input
                                    v-model="diaryForm.posted_on"
                                    type="date"
                            /></Field>
                            <Field
                                label="Title (optional)"
                                :error="diaryForm.errors.title"
                                ><Input
                                    v-model="diaryForm.title"
                                    placeholder="Week 3: eyes are open!"
                            /></Field>
                        </div>
                        <Field
                            label="What happened?"
                            :error="diaryForm.errors.body"
                            ><TextArea v-model="diaryForm.body" :rows="3"
                        /></Field>
                        <ToggleField
                            v-model="diaryForm.is_public"
                            label="Show on the website"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="diaryForm.processing || !diaryForm.body"
                            >Post update</Button
                        >
                    </form>
                    <ul v-if="litter.diary?.length" class="divide-y">
                        <li
                            v-for="d in litter.diary"
                            :key="d.id"
                            class="flex items-start justify-between gap-3 py-3"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{ formatDate(d.posted_on)
                                    }}<span v-if="d.title">
                                        · {{ d.title }}</span
                                    ><span
                                        v-if="!d.is_public"
                                        class="font-normal text-muted-foreground"
                                    >
                                        · hidden</span
                                    >
                                </p>
                                <p
                                    class="text-sm whitespace-pre-line text-muted-foreground"
                                >
                                    {{ d.body }}
                                </p>
                            </div>
                            <ConfirmDelete
                                icon
                                message="Delete this update?"
                                @confirm="
                                    router.delete(destroyDiary.url(d.id), {
                                        preserveScroll: true,
                                    })
                                "
                            />
                        </li>
                    </ul>
                </section>
            </TabPanel>

            <TabPanel name="papers" :active="tab === 'papers'">
                <p class="text-sm text-muted-foreground">
                    Papers and photos are added as soon as you pick them.
                </p>
                <section class="space-y-3 rounded-xl border bg-card p-5">
                    <div>
                        <h2 class="font-serif text-lg font-semibold">
                            Litter documents
                            <span
                                class="text-sm font-normal text-muted-foreground"
                                >(private)</span
                            >
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            The stud agreement, a copy of the AKC litter
                            application, vet records for the whole litter. For
                            one puppy’s bill of sale, use that puppy’s details
                            above.
                        </p>
                    </div>
                    <DocumentManager
                        type="litter"
                        :id="litter.id"
                        :documents="litter.documents ?? []"
                        category="stud_agreement"
                    />
                </section>

                <section class="space-y-4 rounded-xl border bg-card p-5">
                    <h2 class="font-serif text-lg font-semibold">
                        Litter photos
                    </h2>
                    <PhotoManager
                        :photos="litter.photos ?? []"
                        type="litter"
                        :id="litter.id"
                    />
                </section>

                <section
                    v-if="litter && !litter.deleted_at"
                    class="space-y-3 rounded-xl border border-dashed bg-card p-5"
                >
                    <h2 class="font-serif text-lg font-semibold">
                        Archive this litter
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Hides the litter from your lists and the website. The
                        puppies, weights, buyers, documents and diary are all
                        kept, as AKC asks, and you can bring the litter back any
                        time.
                    </p>
                    <ConfirmDelete
                        label="Archive litter"
                        :message="`Archive this litter? It is hidden from your lists and the website, but the ${litter.puppies?.length ?? 0} puppies, weights, buyers, documents and the puppy diary are all kept. People on the waitlist stay. You can bring the litter back any time.`"
                        @confirm="router.delete(destroy.url(litter.slug))"
                    />
                </section>
            </TabPanel>
        </template>

        <StickySave
            :dirty="form.isDirty"
            :processing="form.processing"
            form-id="main-form"
            @discard="form.reset()"
        />
    </div>
</template>
