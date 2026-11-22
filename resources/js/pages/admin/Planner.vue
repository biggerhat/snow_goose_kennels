<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Circle, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { AkcItem } from '@/components/admin/AkcChecklist.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/admin/litters';
import { planner } from '@/routes/admin';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Pairing planner', href: planner() }] },
});

const props = defineProps<{
    dogs: { id: number; name: string; sex: string; is_ours: boolean }[];
    selected: { sire: number | null; dam: number | null };
    result: {
        inbreeding: { percent: number; generations: number; common: string[] };
        parents: {
            id: number;
            slug: string;
            name: string;
            registered_name: string | null;
            titles: string | null;
            is_ours: boolean;
            age_label: string | null;
            registry_number: string | null;
            clearances: { label: string; done: boolean }[];
        }[];
        checks: AkcItem[];
    } | null;
}>();

const sire = ref<string | null>(
    props.selected.sire ? String(props.selected.sire) : null,
);
const dam = ref<string | null>(
    props.selected.dam ? String(props.selected.dam) : null,
);

const options = (sex: string) =>
    props.dogs
        .filter((d) => d.sex === sex)
        .map((d) => ({
            value: String(d.id),
            label: d.is_ours ? d.name : `${d.name} (another kennel)`,
        }));

function go() {
    router.get(
        planner.url(),
        { sire: sire.value || undefined, dam: dam.value || undefined },
        { preserveState: true, replace: true },
    );
}

const tone = computed(() => {
    const p = props.result?.inbreeding.percent ?? 0;

    return p >= 12.5
        ? 'text-red-600'
        : p >= 6.25
          ? 'text-amber-600'
          : 'text-emerald-600';
});
</script>

<template>
    <Head title="Pairing planner" />

    <div class="max-w-4xl space-y-6 p-4 md:p-6">
        <PageHeader
            title="Pairing planner"
            description="Think about a mating before you plan a litter."
            :help="[
                'Choose a sire (father) and a dam (mother). The planner shows how closely related they are, what health tests each has, and the AKC points to watch.',
                'The relatedness figure is worked out from the family trees you have entered. The more generations you have filled in, the more it can see. A short pedigree can look fine when the dogs really are related.',
                'It is a planning aid, not advice. Talk to your vet or an experienced breeder before you decide.',
                'When you like a pairing, tap Plan this litter to start a litter with both dogs filled in.',
            ]"
            help-title="Planning a pairing"
            help-topic="Plan a pairing before the litter"
        />

        <div class="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-2">
            <div class="space-y-1.5">
                <label for="planner-sire" class="text-sm font-medium"
                    >Sire (father)</label
                >
                <NativeSelect
                    id="planner-sire"
                    v-model="sire"
                    :options="options('male')"
                    placeholder="Choose…"
                    @update:model-value="go()"
                />
            </div>
            <div class="space-y-1.5">
                <label for="planner-dam" class="text-sm font-medium"
                    >Dam (mother)</label
                >
                <NativeSelect
                    id="planner-dam"
                    v-model="dam"
                    :options="options('female')"
                    placeholder="Choose…"
                    @update:model-value="go()"
                />
            </div>
        </div>

        <template v-if="result">
            <section class="space-y-3 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    How closely related are they?
                </h2>
                <p class="flex items-baseline gap-3">
                    <span :class="['font-serif text-5xl font-semibold', tone]"
                        >{{ result.inbreeding.percent }}%</span
                    >
                    <span class="text-sm text-muted-foreground">
                        expected inbreeding in the puppies, from
                        {{ result.inbreeding.generations }} generation{{
                            result.inbreeding.generations === 1 ? '' : 's'
                        }}
                        of known ancestors
                    </span>
                </p>
                <p v-if="result.inbreeding.common.length" class="text-sm">
                    <strong>Both families include:</strong>
                    {{ result.inbreeding.common.join(', ') }}.
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    No shared ancestors found in the trees on file.
                </p>
                <p class="text-xs text-muted-foreground">
                    For comparison: first cousins 6.25%, half siblings 12.5%,
                    full siblings 25%. This only counts ancestors you have
                    entered.
                </p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <section
                    v-for="p in result.parents"
                    :key="p.id"
                    class="space-y-2 rounded-xl border bg-card p-5"
                >
                    <h2 class="font-serif text-lg font-semibold">
                        <Link
                            :href="`/admin/dogs/${p.slug}/edit`"
                            class="hover:underline"
                            >{{ p.name }}</Link
                        >
                        <span
                            v-if="p.titles"
                            class="ml-2 text-sm font-normal text-muted-foreground"
                        >
                            {{ p.titles }}</span
                        >
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ p.registered_name ?? 'No registered name' }}
                        <template v-if="p.age_label">
                            · {{ p.age_label }}</template
                        >
                        <template v-if="!p.is_ours"> · another kennel</template>
                    </p>
                    <p class="text-sm">
                        {{ p.registry_number ?? 'No registration number' }}
                    </p>
                    <ul class="flex flex-wrap gap-1.5 text-xs">
                        <li
                            v-for="c in p.clearances"
                            :key="c.label"
                            :class="[
                                'rounded-full px-2.5 py-1',
                                c.done
                                    ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300'
                                    : 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
                            ]"
                        >
                            {{ c.done ? '✓' : '○' }} {{ c.label }}
                        </li>
                    </ul>
                </section>
            </div>

            <section class="space-y-3 rounded-xl border bg-card p-5">
                <h2 class="font-serif text-lg font-semibold">
                    AKC points for this pairing
                </h2>
                <ul class="divide-y rounded-lg border">
                    <li
                        v-for="c in result.checks"
                        :key="c.key"
                        class="flex gap-3 p-3 text-sm"
                    >
                        <Check
                            v-if="c.status === 'ok'"
                            class="mt-0.5 size-4 shrink-0 text-emerald-600"
                        />
                        <TriangleAlert
                            v-else-if="c.status === 'warn'"
                            class="mt-0.5 size-4 shrink-0 text-red-600"
                        />
                        <Circle
                            v-else
                            class="mt-0.5 size-4 shrink-0 text-amber-600"
                        />
                        <span>
                            <span class="block font-medium">{{ c.title }}</span>
                            <span
                                v-if="c.detail"
                                class="block text-muted-foreground"
                                >{{ c.detail }}</span
                            >
                        </span>
                    </li>
                </ul>
            </section>

            <Button as-child>
                <Link
                    :href="
                        create({
                            query: {
                                sire: result.parents[0].id,
                                dam: result.parents[1].id,
                            },
                        })
                    "
                    >Plan this litter</Link
                >
            </Button>
        </template>
        <section
            v-else
            class="space-y-2 rounded-xl border border-dashed bg-card p-5 text-sm"
        >
            <p class="font-medium">
                Choose both parents to see how they match up.
            </p>
            <p class="text-muted-foreground">
                You will see how closely related they are as a percentage, which
                health tests each dog has, and the AKC points to watch. For
                example, two dogs with no shared ancestors show 0%. A brother
                and sister would show 25%.
            </p>
        </section>
    </div>
</template>
