<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PhotoManager from '@/components/admin/PhotoManager.vue';
import TextArea from '@/components/admin/TextArea.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useUnsavedWarning } from '@/composables/useUnsavedWarning';
import { formatDate } from '@/lib/format';
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

const props = defineProps<{ dog: Dog | null }>();
const options = computed(() => usePage().props.options as Options);

defineOptions({ layout: { breadcrumbs: [{ title: 'Dogs', href: index() }] } });

const form = useForm({
    name: props.dog?.name ?? '',
    registered_name: props.dog?.registered_name ?? '',
    sex: props.dog?.sex ?? 'female',
    role: props.dog?.role ?? 'dam',
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
                'After you save, you can add health tests, heat cycles (for females) and photos further down this page.',
                'Untick Show on the website to keep a dog private. Registration, microchip and private notes are never shown to visitors.',
            ]"
            help-title="Adding a dog"
        >
            <ConfirmDelete
                v-if="dog"
                label="Delete dog"
                :message="`Delete ${dog.name}? Her health tests and heat cycles are deleted too. Litters she was in stay, but show no mother or father. Photos stay in your Photos library. This cannot be undone.`"
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
