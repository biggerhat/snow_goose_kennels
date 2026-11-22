<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Syringe } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { daysUntil, formatDate, labelFor } from '@/lib/format';
import {
    destroy,
    dog as storeDog,
    litter as storeLitter,
    puppy as storePuppy,
    update,
} from '@/routes/admin/health-records';
import type { HealthRecord, Options } from '@/types';

const props = defineProps<{
    /** What the entry is for: one dog, one puppy, or every puppy in a litter. */
    target: 'dog' | 'puppy' | 'litter';
    /** The dog, puppy or litter (its slug for a dog or litter, its id for a puppy). */
    id: string | number;
    records?: HealthRecord[];
    heading?: string;
}>();

const options = computed(() => usePage().props.options as Options);
const today = () => new Date().toLocaleDateString('en-CA');
const adding = ref(false);
const listId = `health-names-${Math.random().toString(36).slice(2)}`;

const form = useForm({
    type: 'vaccine',
    name: '',
    given_on: today(),
    next_due_on: '',
    given_by: '',
    lot_number: '',
    notes: '',
});

const suggestions: Record<string, string[]> = {
    vaccine: ['Rabies', 'DHPP', 'Bordetella', 'Leptospirosis', 'Lyme'],
    deworming: ['Pyrantel', 'Fenbendazole (Panacur)'],
    parasite: ['Heartworm preventive', 'Flea and tick preventive'],
    vet_visit: ['Annual exam', 'Puppy checkup', 'Health certificate'],
    medication: [],
    surgery: [],
    other: [],
};

const url = computed(() =>
    props.target === 'dog'
        ? storeDog.url(String(props.id))
        : props.target === 'litter'
          ? storeLitter.url(String(props.id))
          : storePuppy.url(Number(props.id)),
);

function add() {
    form.post(url.value, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset(
                'name',
                'next_due_on',
                'given_by',
                'lot_number',
                'notes',
            );
            form.given_on = today();
            adding.value = false;
        },
    });
}

/** Is a later entry with the same name already on file? Then the old "due" date is done. */
function dueTone(r: HealthRecord): { text: string; tone: string } | null {
    if (!r.next_due_on) {
        return null;
    }

    const done = (props.records ?? []).some(
        (o) =>
            o.id !== r.id &&
            o.name.trim().toLowerCase() === r.name.trim().toLowerCase() &&
            o.given_on > r.given_on,
    );

    if (done) {
        return null;
    }

    const days = daysUntil(r.next_due_on) ?? 0;

    if (days < 0) {
        return {
            text: `Overdue since ${formatDate(r.next_due_on)}`,
            tone: 'bg-red-500/15 text-red-700 dark:text-red-300',
        };
    }

    return {
        text: `Next due ${formatDate(r.next_due_on)}`,
        tone:
            days <= 14
                ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300'
                : 'bg-muted text-muted-foreground',
    };
}

const editing = ref<number | null>(null);
const edit = useForm({
    type: 'vaccine',
    name: '',
    given_on: '',
    next_due_on: '',
    given_by: '',
    lot_number: '',
    notes: '',
});

function startEdit(r: HealthRecord) {
    editing.value = r.id;
    edit.type = r.type;
    edit.name = r.name;
    edit.given_on = r.given_on;
    edit.next_due_on = r.next_due_on ?? '';
    edit.given_by = r.given_by ?? '';
    edit.lot_number = r.lot_number ?? '';
    edit.notes = r.notes ?? '';
    edit.clearErrors();
}

function saveEdit(r: HealthRecord) {
    edit.put(update.url(r.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}
</script>

<template>
    <div class="space-y-3">
        <template v-if="target !== 'litter'">
            <ul v-if="records?.length" class="divide-y rounded-lg border">
                <li v-for="r in records" :key="r.id" class="p-3 text-sm">
                    <div
                        v-if="editing !== r.id"
                        class="flex flex-wrap items-start justify-between gap-2"
                    >
                        <div class="flex min-w-0 gap-3">
                            <Syringe
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0">
                                <p class="font-medium">
                                    {{ r.name }}
                                    <span
                                        class="font-normal text-muted-foreground"
                                    >
                                        ·
                                        {{
                                            labelFor(
                                                options.healthRecordType,
                                                r.type,
                                            )
                                        }}
                                    </span>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Given {{ formatDate(r.given_on) }}
                                    <template v-if="r.given_by">
                                        by {{ r.given_by }}</template
                                    >
                                    <template v-if="r.lot_number">
                                        · lot {{ r.lot_number }}</template
                                    >
                                </p>
                                <p v-if="r.notes" class="mt-0.5 text-xs">
                                    {{ r.notes }}
                                </p>
                                <span
                                    v-if="dueTone(r)"
                                    :class="[
                                        'mt-1 inline-block rounded-full px-2 py-0.5 text-xs',
                                        dueTone(r)?.tone,
                                    ]"
                                    >{{ dueTone(r)?.text }}</span
                                >
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="startEdit(r)"
                                >Edit</Button
                            >
                            <ConfirmDelete
                                icon
                                message="Remove this health record?"
                                @confirm="
                                    router.delete(destroy.url(r.id), {
                                        preserveScroll: true,
                                    })
                                "
                            />
                        </div>
                    </div>
                    <form
                        v-else
                        class="grid gap-3 sm:grid-cols-2"
                        @submit.prevent="saveEdit(r)"
                    >
                        <Field label="Kind" :error="edit.errors.type">
                            <NativeSelect
                                v-model="edit.type"
                                :options="options.healthRecordType"
                            />
                        </Field>
                        <Field
                            label="What was it"
                            required
                            :error="edit.errors.name"
                        >
                            <Input v-model="edit.name" />
                        </Field>
                        <Field
                            label="Given on"
                            required
                            :error="edit.errors.given_on"
                        >
                            <Input v-model="edit.given_on" type="date" />
                        </Field>
                        <Field
                            label="Next due"
                            :error="edit.errors.next_due_on"
                        >
                            <Input v-model="edit.next_due_on" type="date" />
                        </Field>
                        <Field label="Given by" :error="edit.errors.given_by">
                            <Input v-model="edit.given_by" />
                        </Field>
                        <Field
                            label="Lot number"
                            :error="edit.errors.lot_number"
                        >
                            <Input v-model="edit.lot_number" />
                        </Field>
                        <Field
                            label="Notes"
                            :error="edit.errors.notes"
                            class="sm:col-span-2"
                        >
                            <Input v-model="edit.notes" />
                        </Field>
                        <div class="flex gap-2 sm:col-span-2">
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="edit.processing"
                                >Save</Button
                            >
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="editing = null"
                                >Cancel</Button
                            >
                        </div>
                    </form>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">
                Nothing recorded yet.
            </p>
        </template>

        <Button
            v-if="!adding"
            type="button"
            size="sm"
            variant="secondary"
            @click="adding = true"
        >
            <Syringe class="size-4" />
            {{
                heading ??
                (target === 'litter'
                    ? 'Record for every puppy'
                    : 'Add a vaccine, treatment or vet visit')
            }}
        </Button>

        <form
            v-else
            class="space-y-3 rounded-lg border bg-background/50 p-3"
            @submit.prevent="add"
        >
            <p v-if="target === 'litter'" class="text-sm text-muted-foreground">
                This adds the same entry to every puppy in the litter, such as a
                deworming day or a vet check. Edit a single puppy afterwards if
                it differs.
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
                <Field label="Kind" required :error="form.errors.type">
                    <NativeSelect
                        v-model="form.type"
                        :options="options.healthRecordType"
                    />
                </Field>
                <Field
                    label="What was it"
                    required
                    :error="form.errors.name"
                    hint="For example Rabies, or the name of the dewormer."
                >
                    <Input v-model="form.name" :list="listId" />
                    <datalist :id="listId">
                        <option
                            v-for="s in suggestions[form.type] ?? []"
                            :key="s"
                            :value="s"
                        />
                    </datalist>
                </Field>
                <Field label="Given on" required :error="form.errors.given_on">
                    <Input v-model="form.given_on" type="date" />
                </Field>
                <Field
                    label="Next due"
                    :error="form.errors.next_due_on"
                    help="When it needs doing again. You will see it on the dashboard and in your morning email as the date gets close. Leave empty for one-time things."
                >
                    <Input v-model="form.next_due_on" type="date" />
                </Field>
                <Field
                    label="Given by"
                    :error="form.errors.given_by"
                    hint="Your vet, or you."
                >
                    <Input v-model="form.given_by" />
                </Field>
                <Field
                    label="Lot number"
                    :error="form.errors.lot_number"
                    hint="From the vial, if you want to keep it."
                >
                    <Input v-model="form.lot_number" />
                </Field>
            </div>
            <Field label="Notes" :error="form.errors.notes">
                <Input v-model="form.notes" />
            </Field>
            <div class="flex gap-2">
                <Button type="submit" size="sm" :disabled="form.processing"
                    >Save</Button
                >
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    @click="adding = false"
                    >Cancel</Button
                >
            </div>
        </form>
    </div>
</template>
