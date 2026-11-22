<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AkcChecklist from '@/components/admin/AkcChecklist.vue';
import type { AkcItem } from '@/components/admin/AkcChecklist.vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import DocumentManager from '@/components/admin/DocumentManager.vue';
import Field from '@/components/admin/Field.vue';
import HealthRecords from '@/components/admin/HealthRecords.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PhotoManager from '@/components/admin/PhotoManager.vue';
import QuickDog from '@/components/admin/QuickDog.vue';
import TextArea from '@/components/admin/TextArea.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import PedigreeChart from '@/components/PedigreeChart.vue';
import type { PedigreeNode } from '@/components/PedigreeChart.vue';
import { formatDate, labelFor } from '@/lib/format';
import { edit as editLitter } from '@/routes/admin/litters';
import { destroy, index, store, update } from '@/routes/admin/dogs';
import {
    destroy as destroyHeat,
    store as storeHeat,
} from '@/routes/admin/heat-cycles';
import {
    destroy as destroyTest,
    store as storeTest,
} from '@/routes/admin/health-tests';
import type { Dog, Options } from '@/types';

const props = defineProps<{
    dog: Dog | null;
    /** Opened from "Add a dog from another kennel". */
    outside?: boolean;
    parents: {
        id: number;
        name: string;
        sex: string;
        is_ours: boolean;
        registered_name: string | null;
    }[];
    contacts: { id: number; name: string }[];
    pedigree?: PedigreeNode | null;
    akc?: { items: AkcItem[]; todo: number; warn: number };
    litters?: {
        id: number;
        slug: string;
        title: string;
        status: string;
        born_on: string | null;
        due_on: string | null;
        puppies_count: number;
    }[];
}>();
const options = computed(() => usePage().props.options as Options);

const fromElsewhere = computed({
    get: () => !form.is_ours,
    set: (v: boolean) => (form.is_ours = !v),
});
const parentOptions = (sex: string) =>
    props.parents
        .filter((d) => d.sex === sex)
        .map((d) => ({
            value: String(d.id),
            label: d.is_ours ? d.name : `${d.name} (another kennel)`,
        }));

// Select a dog the moment it has been added from the quick form.
const justAdded = ref<{ key: 'sire_id' | 'dam_id'; name: string } | null>(null);
watch(
    () => props.parents,
    (list) => {
        const pending = justAdded.value;
        const dog = pending && list.find((d) => d.name === pending.name);

        if (pending && dog) {
            form[pending.key] = dog.id;
            justAdded.value = null;
        }
    },
);

defineOptions({ layout: { breadcrumbs: [{ title: 'Dogs', href: index() }] } });

const form = useForm({
    name: props.dog?.name ?? '',
    registered_name: props.dog?.registered_name ?? '',
    sex: props.dog?.sex ?? 'female',
    role: props.dog?.role ?? (props.outside ? 'other' : 'dam'),
    is_ours: props.dog?.is_ours ?? !props.outside,
    owner_contact_id: props.dog?.owner_contact_id ?? null,
    sire_id: props.dog?.sire_id ?? null,
    dam_id: props.dog?.dam_id ?? null,
    titles: props.dog?.titles ?? '',
    dna_profile: props.dog?.dna_profile ?? '',
    acquired_on: props.dog?.acquired_on ?? '',
    left_on: props.dog?.left_on ?? '',
    left_reason: props.dog?.left_reason ?? null,
    color: props.dog?.color ?? null,
    birth_date: props.dog?.birth_date ?? '',
    bio: props.dog?.bio ?? '',
    is_public: props.dog?.is_public ?? true,
    registry_number: props.dog?.registry_number ?? '',
    microchip: props.dog?.microchip ?? '',
    private_notes: props.dog?.private_notes ?? '',
});

useUnsavedWarning(() => form.isDirty);

// Keep role and sex in step so the obvious mistake cannot happen.
watch(
    () => form.sex,
    (sex) => {
        if (sex === 'male' && form.role === 'dam') {
            form.role = 'stud';
        } else if (sex === 'female' && form.role === 'stud') {
            form.role = 'dam';
        }
    },
);

function submit() {
    if (props.dog) {
        form.put(update.url(props.dog.slug), { preserveScroll: true });
    } else {
        form.post(store.url());
    }
}

const testForm = useForm({
    name: '',
    result: '',
    tested_on: '',
    certificate_number: '',
    verification_url: '',
    is_public: true,
});

function addTest() {
    if (!props.dog) {
        return;
    }

    testForm.post(storeTest.url(props.dog.slug), {
        preserveScroll: true,
        onSuccess: () => testForm.reset(),
    });
}

const heatForm = useForm({
    started_on: '',
    ended_on: '',
    bred_on: '',
    notes: '',
});

function addHeat() {
    if (props.dog) {
        heatForm.post(storeHeat.url(props.dog.slug), {
            preserveScroll: true,
            onSuccess: () => heatForm.reset(),
        });
    }
}

const nextHeat = computed(() => {
    const last = props.dog?.heat_cycles?.[0]?.started_on;

    if (!last) {
        return null;
    }

    const [y, m, d] = last.split('-').map(Number);
    const from = new Date(y, m - 1 + 6, d);
    const to = new Date(y, m - 1 + 8, d);
    const fmt = (x: Date) =>
        x.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });

    return `${fmt(from)} – ${fmt(to)}`;
});

const chic = [
    { label: 'Hips (OFA)', match: /hip/i, required: true },
    { label: 'Elbows (OFA)', match: /elbow/i, required: true },
    { label: 'Eye exam', match: /eye|caer|acvo/i, required: true },
    { label: 'EIC', match: /eic/i, required: true },
    { label: 'D-locus (dilute)', match: /d-?locus|dilute/i, required: true },
    { label: 'prcd-PRA', match: /pra/i, required: false },
    { label: 'CNM', match: /cnm/i, required: false },
    { label: 'Heart', match: /heart|cardiac|echo/i, required: false },
].map((c) => ({
    ...c,
    done: (props.dog?.health_tests ?? []).some((t) => c.match.test(t.name)),
}));

const suggestions = [
    'OFA Hips',
    'OFA Elbows',
    'EIC',
    'CNM',
    'PRA',
    'Eye exam (CAER)',
    'Heart (echo)',
];
</script>

<template>
    <Head :title="dog ? dog.name : 'Add a dog'" />

    <div class="max-w-4xl space-y-8 p-4 md:p-6">
        <PageHeader
            :title="dog ? dog.name : 'Add a dog'"
            :description="
                dog
                    ? (dog.registered_name ?? undefined)
                    : 'Add the basics now; photos and health tests come after saving.'
            "
            :help="[
                'Add each dog you keep or breed: the mothers (dams), the fathers (studs), and retired dogs.',
                'Only name, sex and role are needed. Add a photo and a short description to make the dog’s page on the website nicer.',
                'After you save, you can add health tests, vaccines, heat cycles (for females), papers and photos further down this page.',
                'Family tree: choose the father and mother. If they belong to another kennel, add them with the link under the box. The pedigree draws itself from everyone’s parents.',
                'Documents keeps a private copy of registration papers, pedigrees, OFA and DNA reports and vet records. The AKC record list shows what is still missing for this dog.',
                'Untick Show on the website to keep a dog private. Registration, microchip and private notes are never shown to visitors.',
            ]"
            help-title="Adding a dog"
        >
            <ConfirmDelete
                v-if="dog"
                label="Delete dog"
                :message="`Delete ${dog.name}? Her health tests, heat cycles and documents are deleted too. If this dog was sold or passed away, use “Left the kennel” in the private details instead, so your records stay complete. Litters she was in stay, but show no mother or father. Photos stay in your Photos library. This cannot be undone.`"
                @confirm="router.delete(destroy.url(dog.slug))"
            />
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    The basics
                    <span class="text-sm font-normal text-muted-foreground"
                        >(shown on the website)</span
                    >
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Name"
                        required
                        :error="form.errors.name"
                        for="name"
                        hint="The everyday name you call the dog."
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Bella"
                        />
                    </Field>
                    <Field
                        label="Registered name"
                        :error="form.errors.registered_name"
                        for="registered_name"
                        help="The long official name on the dog’s registration papers, such as the AKC certificate. Leave empty if there is none."
                    >
                        <Input
                            id="registered_name"
                            v-model="form.registered_name"
                            placeholder="Snow Goose's Bella of the Marsh"
                        />
                    </Field>
                    <Field
                        label="Titles"
                        :error="form.errors.titles"
                        for="titles"
                        help="Letters earned in dog sports or shows, like MH (Master Hunter), SH (Senior Hunter) or CH (Champion). They are shown on the website."
                        hint="Optional. For example MH, SH."
                    >
                        <Input
                            id="titles"
                            v-model="form.titles"
                            placeholder="MH, SH"
                        />
                    </Field>
                    <Field label="Sex" required :error="form.errors.sex">
                        <NativeSelect
                            v-model="form.sex"
                            :options="options.sex"
                        />
                    </Field>
                    <Field
                        label="Role"
                        required
                        :error="form.errors.role"
                        help="Dam: a mother you breed. Stud: a father you breed. Retired: no longer bred. Other: anything else, like a family pet."
                    >
                        <NativeSelect
                            v-model="form.role"
                            :options="options.role"
                        />
                    </Field>
                    <Field
                        label="Coat colour"
                        :error="form.errors.color"
                        help="Black, chocolate, yellow, or fox red (a deep red-gold yellow)."
                    >
                        <NativeSelect
                            v-model="form.color"
                            :options="options.color"
                            placeholder="Choose…"
                        />
                    </Field>
                    <Field
                        label="Birth date"
                        :error="form.errors.birth_date"
                        for="birth_date"
                    >
                        <Input
                            id="birth_date"
                            v-model="form.birth_date"
                            type="date"
                        />
                    </Field>
                </div>
                <Field
                    label="About this dog"
                    :error="form.errors.bio"
                    for="bio"
                >
                    <TextArea
                        id="bio"
                        v-model="form.bio"
                        :rows="5"
                        placeholder="Personality, what she is great at, what her pups are like…"
                    />
                </Field>
                <ToggleField
                    v-model="form.is_public"
                    label="Show on the website"
                    hint="Tick to show this dog on the Our Dogs page. Untick to keep the dog private."
                />
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Family tree
                        <span class="text-sm font-normal text-muted-foreground"
                            >(parents)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Choose this dog’s father and mother. The tree below
                        builds itself from every dog’s parents, back several
                        generations.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Field
                            label="Sire (father)"
                            :error="form.errors.sire_id"
                        >
                            <NativeSelect
                                v-model="form.sire_id"
                                :options="parentOptions('male')"
                                placeholder="Not known"
                            />
                        </Field>
                        <QuickDog
                            sex="male"
                            label="The father is"
                            :contacts="contacts"
                            @added="
                                (n) => (justAdded = { key: 'sire_id', name: n })
                            "
                        />
                    </div>
                    <div class="space-y-2">
                        <Field label="Dam (mother)" :error="form.errors.dam_id">
                            <NativeSelect
                                v-model="form.dam_id"
                                :options="parentOptions('female')"
                                placeholder="Not known"
                            />
                        </Field>
                        <QuickDog
                            sex="female"
                            label="The mother is"
                            :contacts="contacts"
                            @added="
                                (n) => (justAdded = { key: 'dam_id', name: n })
                            "
                        />
                    </div>
                </div>
                <ToggleField
                    v-model="fromElsewhere"
                    label="This dog lives at another kennel"
                    hint="Use this for a stud or a mother that belongs to someone else. These dogs are never shown on your website’s Our Dogs page."
                />
                <Field
                    v-if="fromElsewhere"
                    label="Owner"
                    :error="form.errors.owner_contact_id"
                    hint="Pick the owner from your Contacts. Add them there first if they are new."
                    class="sm:max-w-sm"
                >
                    <NativeSelect
                        v-model="form.owner_contact_id"
                        :options="
                            contacts.map((c) => ({
                                value: String(c.id),
                                label: c.name,
                            }))
                        "
                        placeholder="Not chosen"
                    />
                </Field>
            </section>

            <section
                class="space-y-4 rounded-xl border border-dashed bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">
                    Private details
                    <span class="text-sm font-normal text-muted-foreground"
                        >(only you can see this)</span
                    >
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="Registration number"
                        :error="form.errors.registry_number"
                        for="registry_number"
                        hint="From the registry papers, such as AKC."
                    >
                        <Input
                            id="registry_number"
                            v-model="form.registry_number"
                        />
                    </Field>
                    <Field
                        label="Microchip number"
                        :error="form.errors.microchip"
                        for="microchip"
                        hint="9 to 15 digits, from the vet paperwork. Spaces are fine."
                    >
                        <Input
                            id="microchip"
                            v-model="form.microchip"
                            inputmode="numeric"
                            placeholder="985112345678901"
                        />
                    </Field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field
                        label="AKC DNA profile number"
                        :error="form.errors.dna_profile"
                        for="dna_profile"
                        help="If AKC has this dog’s DNA on file it has a profile number. AKC requires a DNA profile for a sire used often, for imports, for dogs with conditional (Q) registration, and for artificial insemination."
                        hint="Leave empty if there is none."
                    >
                        <Input id="dna_profile" v-model="form.dna_profile" />
                    </Field>
                    <span />
                    <Field
                        label="Came to the kennel on"
                        :error="form.errors.acquired_on"
                        for="acquired_on"
                        hint="Leave empty if the dog was born here: the birth date is used."
                    >
                        <Input
                            id="acquired_on"
                            v-model="form.acquired_on"
                            type="date"
                        />
                    </Field>
                    <div class="grid grid-cols-2 gap-4">
                        <Field
                            label="Left the kennel on"
                            :error="form.errors.left_on"
                            for="left_on"
                            help="Fill this in if the dog was sold, given away or died. Keep the dog here rather than deleting it: AKC wants breeding and ownership records kept for 5 years after that."
                        >
                            <Input
                                id="left_on"
                                v-model="form.left_on"
                                type="date"
                            />
                        </Field>
                        <Field label="Why" :error="form.errors.left_reason">
                            <NativeSelect
                                v-model="form.left_reason"
                                :options="options.leftReason"
                                placeholder="Still here"
                            />
                        </Field>
                    </div>
                </div>
                <Field
                    label="Private notes"
                    :error="form.errors.private_notes"
                    for="private_notes"
                >
                    <TextArea
                        id="private_notes"
                        v-model="form.private_notes"
                        :rows="4"
                        placeholder="Vet info, heat cycles, anything you want to remember…"
                    />
                </Field>
            </section>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{
                    dog ? 'Save changes' : 'Add dog'
                }}</Button>
            </div>
        </form>

        <template v-if="dog">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    Health tests &amp; clearances
                </h2>
                <div class="space-y-1.5">
                    <p class="text-xs text-muted-foreground">
                        Health tests show buyers you care about the puppies’
                        health. Green means done. The Labrador Retriever Club
                        suggests at least hips, elbows, eyes, EIC and D-locus;
                        the rest are extra.
                    </p>
                    <ul class="flex flex-wrap gap-1.5 text-xs">
                        <li
                            v-for="c in chic"
                            :key="c.label"
                            :class="[
                                'rounded-full px-2.5 py-1',
                                c.done
                                    ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300'
                                    : c.required
                                      ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300'
                                      : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            {{ c.done ? '✓' : '○' }} {{ c.label
                            }}<span v-if="!c.required" class="opacity-70">
                                (recommended)</span
                            >
                        </li>
                    </ul>
                </div>
                <ul v-if="dog.health_tests?.length" class="divide-y">
                    <li
                        v-for="t in dog.health_tests"
                        :key="t.id"
                        class="flex items-center justify-between gap-3 py-2 text-sm"
                    >
                        <div>
                            <span class="font-medium">{{ t.name }}:</span>
                            {{ t.result }}
                            <span
                                v-if="t.tested_on"
                                class="text-muted-foreground"
                                >· {{ formatDate(t.tested_on) }}</span
                            >
                            <span
                                v-if="t.certificate_number"
                                class="text-muted-foreground"
                                >· #{{ t.certificate_number }}</span
                            >
                            <a
                                v-if="t.verification_url"
                                :href="t.verification_url"
                                target="_blank"
                                rel="noopener"
                                class="underline"
                                >· verify</a
                            >
                            <span
                                v-if="!t.is_public"
                                class="text-muted-foreground"
                                >· private</span
                            >
                        </div>
                        <ConfirmDelete
                            icon
                            message="Remove this health test? You can add it again later."
                            @confirm="
                                router.delete(destroyTest.url(t.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No tests recorded yet.
                </p>

                <form
                    class="grid items-end gap-3 sm:grid-cols-[1fr_1fr_9rem_auto]"
                    @submit.prevent="addTest"
                >
                    <Field
                        label="Test"
                        required
                        :error="testForm.errors.name"
                        help="Pick one from the list or type your own. Hips and elbows are graded by OFA; the DNA tests (EIC, CNM, PRA) say Clear, Carrier or Affected."
                    >
                        <Input
                            v-model="testForm.name"
                            list="test-names"
                            placeholder="OFA Hips"
                            required
                        />
                        <datalist id="test-names">
                            <option
                                v-for="s in suggestions"
                                :key="s"
                                :value="s"
                            />
                        </datalist>
                    </Field>
                    <Field
                        label="Result"
                        required
                        :error="testForm.errors.result"
                        hint="For example Good, Excellent, Normal or Clear."
                    >
                        <Input
                            v-model="testForm.result"
                            placeholder="Good"
                            required
                        />
                    </Field>
                    <Field label="Date" :error="testForm.errors.tested_on">
                        <Input v-model="testForm.tested_on" type="date" />
                    </Field>
                    <Field
                        label="OFA / certificate number"
                        :error="testForm.errors.certificate_number"
                    >
                        <Input
                            v-model="testForm.certificate_number"
                            placeholder="LR-12345G24M-PI"
                        />
                    </Field>
                    <Field
                        label="Link to the public record"
                        :error="testForm.errors.verification_url"
                        hint="Paste the dog's page from ofa.org"
                    >
                        <Input
                            v-model="testForm.verification_url"
                            type="url"
                            placeholder="https://ofa.org/…"
                        />
                    </Field>
                    <ToggleField
                        v-model="testForm.is_public"
                        label="Show on the website"
                        class="sm:col-span-2"
                    />
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="testForm.processing"
                        >Add test</Button
                    >
                </form>
            </section>

            <section v-if="akc" class="space-y-3 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    AKC record for this dog
                </h2>
                <AkcChecklist :akc="akc" />
            </section>

            <section
                v-if="dog?.is_ours !== false"
                class="space-y-3 rounded-xl border bg-card p-5"
            >
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Vaccines, treatments &amp; vet visits
                        <span class="text-sm font-normal text-muted-foreground"
                            >(private)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Rabies and other shots, worming, flea and heartworm
                        medicine, checkups. Add a “next due” date and you will
                        be reminded.
                    </p>
                </div>
                <HealthRecords
                    target="dog"
                    :id="dog?.slug ?? ''"
                    :records="dog?.health_records ?? []"
                />
            </section>

            <section class="space-y-3 rounded-xl border bg-card p-5">
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Papers &amp; documents
                        <span class="text-sm font-normal text-muted-foreground"
                            >(private)</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Keep a copy of this dog’s registration certificate,
                        pedigree, OFA and DNA reports and vet records. Only you
                        can open them.
                    </p>
                </div>
                <DocumentManager
                    type="dog"
                    :id="dog?.id ?? 0"
                    :documents="dog?.documents ?? []"
                    category="registration"
                />
            </section>

            <section class="space-y-3 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">Pedigree</h2>
                <PedigreeChart
                    v-if="dog?.sire_id || dog?.dam_id"
                    :tree="pedigree ?? null"
                    admin
                />
                <p v-else class="text-sm text-muted-foreground">
                    Choose this dog’s father and mother under Family tree above,
                    then save. The tree is drawn here, and it grows as you add
                    each parent’s own parents. If the dog has papers, you can
                    also keep a copy under Documents.
                </p>
            </section>

            <section
                v-if="litters?.length"
                class="space-y-3 rounded-xl border bg-card p-5"
            >
                <h2 class="font-serif text-lg font-semibold">Litters</h2>
                <ul class="divide-y text-sm">
                    <li v-for="l in litters" :key="l.id">
                        <Link
                            :href="editLitter(l.slug)"
                            class="flex flex-wrap items-center justify-between gap-2 py-2 hover:underline"
                        >
                            <span class="font-medium">{{ l.title }}</span>
                            <span class="text-muted-foreground">
                                {{ labelFor(options.litterStatus, l.status) }}
                                ·
                                {{ formatDate(l.born_on ?? l.due_on) }}
                                · {{ l.puppies_count }} puppies
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                v-if="dog && dog.sex === 'female'"
                class="space-y-4 rounded-xl border bg-card p-5"
            >
                <div>
                    <h2 class="font-serif text-lg font-semibold">
                        Heat cycles
                    </h2>
                    <p v-if="nextHeat" class="text-sm text-muted-foreground">
                        Many Labs cycle roughly every 6–8 months, so the next
                        one might be around {{ nextHeat }}. Every dog is
                        different; this is only a rough guide.
                    </p>
                </div>
                <ul v-if="dog.heat_cycles?.length" class="divide-y text-sm">
                    <li
                        v-for="h in dog.heat_cycles"
                        :key="h.id"
                        class="flex items-center justify-between gap-3 py-2"
                    >
                        <span>
                            Started
                            <strong>{{ formatDate(h.started_on) }}</strong>
                            <span v-if="h.ended_on">
                                · ended {{ formatDate(h.ended_on) }}</span
                            >
                            <span v-if="h.bred_on">
                                · bred {{ formatDate(h.bred_on) }}</span
                            >
                            <span v-if="h.notes" class="text-muted-foreground">
                                · {{ h.notes }}</span
                            >
                        </span>
                        <ConfirmDelete
                            icon
                            message="Remove this heat cycle entry?"
                            @confirm="
                                router.delete(destroyHeat.url(h.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </li>
                </ul>
                <form
                    class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    @submit.prevent="addHeat"
                >
                    <Field
                        label="First day of heat"
                        required
                        :error="heatForm.errors.started_on"
                        help="The first day you saw signs of heat, such as spotting. Labs usually cycle about every 6 to 8 months, and it lasts about 3 weeks."
                        ><Input
                            v-model="heatForm.started_on"
                            type="date"
                            required
                    /></Field>
                    <Field
                        label="Last day of heat"
                        :error="heatForm.errors.ended_on"
                        ><Input v-model="heatForm.ended_on" type="date"
                    /></Field>
                    <Field label="Bred on" :error="heatForm.errors.bred_on"
                        ><Input v-model="heatForm.bred_on" type="date"
                    /></Field>
                    <Field label="Notes" :error="heatForm.errors.notes"
                        ><Input v-model="heatForm.notes"
                    /></Field>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="heatForm.processing"
                        >Add heat cycle</Button
                    >
                </form>
            </section>

            <section v-if="dog" class="space-y-4 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">Photos</h2>
                <PhotoManager
                    :photos="dog.photos ?? []"
                    type="dog"
                    :id="dog.id"
                />
            </section>
        </template>
    </div>
</template>
