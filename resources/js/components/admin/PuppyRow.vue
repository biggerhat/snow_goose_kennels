<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import DocumentManager from '@/components/admin/DocumentManager.vue';
import HealthRecords from '@/components/admin/HealthRecords.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useWeightUnit } from '@/composables/useWeightUnit';
import { destroy, update } from '@/routes/admin/puppies';
import type { Option, Options, Puppy } from '@/types';

const props = defineProps<{
    puppy: Puppy;
    sex: Option[];
    color: Option[];
    status: Option[];
    contacts: { id: number; name: string }[];
}>();

const { unit, toDisplay, toGrams } = useWeightUnit();
const options = computed(() => usePage().props.options as Options);

const form = useForm({
    name: props.puppy.name ?? '',
    collar: props.puppy.collar ?? '',
    sex: props.puppy.sex,
    color: props.puppy.color,
    birth_weight_g: props.puppy.birth_weight_g,
    status: props.puppy.status,
    contact_id: props.puppy.contact_id,
    sale_price: props.puppy.sale_price,
    notes: props.puppy.notes ?? '',
    microchip: props.puppy.microchip ?? '',
    registration_type: props.puppy.registration_type ?? null,
    akc_number: props.puppy.akc_number ?? '',
    papers_given_on: props.puppy.papers_given_on ?? '',
    went_home_on: props.puppy.went_home_on ?? '',
    vet_checked_on: props.puppy.vet_checked_on ?? '',
});

// The day a puppy is marked as gone home is the go-home date, unless she says otherwise.
watch(
    () => form.status,
    (status) => {
        if (status === 'home' && !form.went_home_on) {
            form.went_home_on = new Date().toLocaleDateString('en-CA');
        }
    },
);

// Weights are stored in grams but typed in whatever she weighs in.
const birthWeight = computed({
    get: () => toDisplay(form.birth_weight_g),
    set: (v: string) => (form.birth_weight_g = toGrams(v)),
});

const title = computed(() => {
    const who =
        form.name || (form.collar ? `${form.collar} collar` : 'New puppy');
    const sex = form.sex === 'male' ? 'boy' : 'girl';

    return `${who} (${sex})`;
});

const needsBuyer = computed(
    () => ['reserved', 'home'].includes(form.status) && !form.contact_id,
);

function save() {
    form.put(update.url(props.puppy.id), {
        preserveScroll: true,
        onSuccess: () => form.defaults().reset(),
    });
}
</script>

<template>
    <div class="space-y-3 rounded-lg border bg-background/50 p-3">
        <form class="space-y-3" @submit.prevent="save">
            <div class="flex items-center justify-between gap-2">
                <p class="font-medium">{{ title }}</p>
                <div class="flex items-center gap-1">
                    <Button
                        v-if="form.isDirty"
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        >Save changes</Button
                    >
                    <ConfirmDelete
                        icon
                        label="Remove this puppy"
                        :message="`Remove ${title}? Their weights are deleted too. Photos stay in your Photos library.`"
                        @confirm="
                            router.delete(destroy.url(puppy.id), {
                                preserveScroll: true,
                            })
                        "
                    />
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field
                    label="Collar colour"
                    :error="form.errors.collar"
                    help="A coloured ribbon or collar so you can tell the puppies apart before they have names."
                >
                    <Input v-model="form.collar" placeholder="Red" />
                </Field>
                <Field label="Name" :error="form.errors.name">
                    <Input v-model="form.name" placeholder="(optional)" />
                </Field>
                <Field label="Boy or girl" :error="form.errors.sex">
                    <NativeSelect v-model="form.sex" :options="sex" />
                </Field>
                <Field label="Coat colour" :error="form.errors.color">
                    <NativeSelect
                        v-model="form.color"
                        :options="color"
                        placeholder="Choose…"
                    />
                </Field>
                <Field
                    label="Status"
                    :error="form.errors.status"
                    help="Available: still looking for a home. Reserved: someone has chosen this puppy. Gone home: the puppy has left with its family. Kept: you are keeping it."
                >
                    <NativeSelect v-model="form.status" :options="status" />
                </Field>
                <Field
                    label="Buyer"
                    :error="form.errors.contact_id"
                    :hint="
                        needsBuyer
                            ? 'Choose who this puppy is going to.'
                            : undefined
                    "
                >
                    <NativeSelect
                        v-model="form.contact_id"
                        :options="
                            contacts.map((c) => ({
                                value: String(c.id),
                                label: c.name,
                            }))
                        "
                        placeholder="Nobody yet"
                    />
                </Field>
                <Field
                    label="Sold for ($)"
                    :error="form.errors.sale_price"
                    hint="Private. Whole dollars."
                >
                    <Input
                        v-model="form.sale_price"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        placeholder="1500"
                    />
                </Field>
                <Field
                    :label="`Birth weight (${unit === 'oz' ? 'ounces' : 'grams'})`"
                    :error="form.errors.birth_weight_g"
                    hint="Switch units in Weigh-ins below."
                >
                    <Input
                        v-model="birthWeight"
                        type="number"
                        step="0.1"
                        min="0"
                        inputmode="decimal"
                    />
                </Field>
                <Field
                    label="Notes"
                    :error="form.errors.notes"
                    class="sm:col-span-2 lg:col-span-4"
                    hint="Private. Markings, personality, anything to remember."
                >
                    <Input v-model="form.notes" />
                </Field>
            </div>

            <details class="rounded-lg border bg-card/50 p-3">
                <summary class="cursor-pointer text-sm font-medium select-none">
                    Go-home &amp; AKC details
                    <span class="font-normal text-muted-foreground"
                        >(microchip, registration papers, dates)</span
                    >
                </summary>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="Microchip number"
                        :error="form.errors.microchip"
                        hint="9 to 15 digits, from the vet."
                    >
                        <Input
                            v-model="form.microchip"
                            inputmode="numeric"
                            placeholder="985112345678901"
                        />
                    </Field>
                    <Field
                        label="Papers given to the buyer"
                        :error="form.errors.registration_type"
                        help="Full registration lets the new owner register the puppy and its own litters with AKC. Limited registration lets the puppy be registered but not its offspring. No papers means a bill of sale only."
                    >
                        <NativeSelect
                            v-model="form.registration_type"
                            :options="options.registrationType"
                            placeholder="Not decided"
                        />
                    </Field>
                    <Field
                        label="AKC number"
                        :error="form.errors.akc_number"
                        hint="From the puppy’s own registration, if you have it."
                    >
                        <Input v-model="form.akc_number" />
                    </Field>
                    <Field
                        label="Papers handed over on"
                        :error="form.errors.papers_given_on"
                    >
                        <Input v-model="form.papers_given_on" type="date" />
                    </Field>
                    <Field
                        label="Went home on"
                        :error="form.errors.went_home_on"
                        hint="Set for you when you mark the puppy Gone home."
                    >
                        <Input v-model="form.went_home_on" type="date" />
                    </Field>
                    <Field
                        label="Vet check on"
                        :error="form.errors.vet_checked_on"
                        hint="The check before the puppy goes home."
                    >
                        <Input v-model="form.vet_checked_on" type="date" />
                    </Field>
                </div>
            </details>
        </form>

        <details class="rounded-lg border bg-card/50 p-3">
            <summary class="cursor-pointer text-sm font-medium select-none">
                Health records
                <span class="font-normal text-muted-foreground"
                    >(deworming, vaccines, vet checks;
                    {{ puppy.health_records?.length ?? 0 }})</span
                >
            </summary>
            <div class="mt-3">
                <HealthRecords
                    target="puppy"
                    :id="puppy.id"
                    :records="puppy.health_records ?? []"
                />
            </div>
        </details>

        <details class="rounded-lg border bg-card/50 p-3">
            <summary class="cursor-pointer text-sm font-medium select-none">
                Paperwork
                <span class="font-normal text-muted-foreground"
                    >(bill of sale, registration papers, vet records;
                    {{ puppy.documents?.length ?? 0 }})</span
                >
            </summary>
            <div class="mt-3">
                <DocumentManager
                    type="puppy"
                    :id="puppy.id"
                    :documents="puppy.documents ?? []"
                    category="sale"
                    compact
                />
            </div>
        </details>
    </div>
</template>
