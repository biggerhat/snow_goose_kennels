<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useWeightUnit } from '@/composables/useWeightUnit';
import { destroy, update } from '@/routes/admin/puppies';
import type { Option, Puppy } from '@/types';

const props = defineProps<{
    puppy: Puppy;
    sex: Option[];
    color: Option[];
    status: Option[];
    contacts: { id: number; name: string }[];
}>();

const { unit, toDisplay, toGrams } = useWeightUnit();

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
});

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
    <form
        class="space-y-3 rounded-lg border bg-background/50 p-3"
        @submit.prevent="save"
    >
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
    </form>
</template>
