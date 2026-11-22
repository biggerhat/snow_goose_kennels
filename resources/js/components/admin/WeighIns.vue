<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useWeightUnit } from '@/composables/useWeightUnit';
import { formatDate } from '@/lib/format';
import { destroy, store } from '@/routes/admin/weigh-ins';
import type { Puppy } from '@/types';

const props = defineProps<{ slug: string; puppies: Puppy[] }>();

const { unit, setUnit, toDisplay, toGrams } = useWeightUnit();

const show = (g: number | null | undefined) => toDisplay(g) || '—';

const today = () => {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const date = ref(today());
const entries = reactive<Record<number, string>>({});
const saving = ref(false);
const errors = ref<Record<string, string>>({});
const firstWeightError = computed(
    () =>
        Object.entries(errors.value).find(([k]) =>
            k.startsWith('weights'),
        )?.[1],
);

const dates = computed(() =>
    [
        ...new Set(
            props.puppies.flatMap((p) =>
                (p.weights ?? []).map((w) => w.weighed_on),
            ),
        ),
    ]
        .sort()
        .slice(-8),
);

const weightOn = (p: Puppy, d: string) =>
    p.weights?.find((w) => w.weighed_on === d)?.weight_g ?? null;
const label = (p: Puppy) => p.name || p.collar || `Puppy ${p.id}`;

function save() {
    const weights: Record<number, number> = {};

    for (const [id, raw] of Object.entries(entries)) {
        const grams = toGrams(raw);

        if (grams !== null) {
            weights[Number(id)] = grams;
        }
    }

    router.post(
        store.url(props.slug),
        { weighed_on: date.value, weights },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
            onError: (e) => (errors.value = e),
            onSuccess: () => {
                errors.value = {};
                Object.keys(entries).forEach((k) => delete entries[Number(k)]);
            },
        },
    );
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                Weigh the puppies, type each weight in, then tap Save weights.
                Leave a box empty to skip a puppy. Weighing every week or so
                shows who is growing well; a puppy that stops gaining is worth a
                call to the vet.
            </p>
            <div class="inline-flex rounded-lg bg-muted p-1 text-sm">
                <button
                    v-for="u in ['oz', 'g'] as const"
                    :key="u"
                    type="button"
                    :class="[
                        'rounded-md px-3 py-1',
                        unit === u
                            ? 'bg-card shadow-xs'
                            : 'text-muted-foreground',
                    ]"
                    @click="setUnit(u)"
                >
                    {{ u === 'oz' ? 'Ounces' : 'Grams' }}
                </button>
            </div>
        </div>

        <form
            class="space-y-3 rounded-lg border bg-background/50 p-3"
            @submit.prevent="save"
        >
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium" for="weigh-date"
                    >Weigh-in date</label
                >
                <Input
                    id="weigh-date"
                    v-model="date"
                    type="date"
                    class="w-44"
                />
            </div>
            <InputError :message="errors.weighed_on" />
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <label
                    v-for="p in puppies"
                    :key="p.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <span class="w-24 shrink-0 truncate font-medium">{{
                        label(p)
                    }}</span>
                    <Input
                        v-model="entries[p.id]"
                        type="number"
                        step="0.1"
                        min="0"
                        inputmode="decimal"
                        :placeholder="unit"
                    />
                </label>
            </div>
            <InputError :message="firstWeightError" />
            <Button type="submit" size="sm" :disabled="saving"
                >Save weights</Button
            >
        </form>

        <div v-if="dates.length" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="py-2 pr-3 font-medium">
                            Puppy ({{ unit }})
                        </th>
                        <th class="px-3 py-2 font-medium">Birth</th>
                        <th
                            v-for="d in dates"
                            :key="d"
                            class="px-3 py-2 font-medium"
                        >
                            <span class="inline-flex items-center gap-1"
                                >{{ formatDate(d, false) }}
                                <ConfirmDelete
                                    icon
                                    message="Delete every weight recorded on this date?"
                                    @confirm="
                                        router.delete(
                                            destroy.url({
                                                litter: slug,
                                                date: d,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    "
                                />
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="p in puppies"
                        :key="p.id"
                        class="border-b last:border-0"
                    >
                        <td class="py-2 pr-3 font-medium">{{ label(p) }}</td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ show(p.birth_weight_g) }}
                        </td>
                        <td v-for="d in dates" :key="d" class="px-3 py-2">
                            {{ show(weightOn(p, d)) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
