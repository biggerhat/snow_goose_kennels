<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import PageTabs from '@/components/admin/PageTabs.vue';
import type { PageTab } from '@/components/admin/PageTabs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePageTabs } from '@/composables/usePageTabs';
import { formatDate } from '@/lib/format';
import { exportMethod, money as moneyRoute } from '@/routes/admin';
import { destroy, store, update } from '@/routes/admin/expenses';
import type { Option } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Money', href: moneyRoute() }] },
});

type Expense = {
    id: number;
    spent_on: string;
    category: string;
    amount: number;
    vendor: string | null;
    description: string | null;
    litter_id: number | null;
    dog_id: number | null;
    litter_title: string | null;
    dog_name: string | null;
};

const props = defineProps<{
    year: number;
    years: number[];
    summary: {
        income: number;
        costs: number;
        net: number;
        income_lines: { label: string; cents: number }[];
        categories: { label: string; cents: number }[];
        litters: {
            id: number;
            slug: string;
            title: string;
            income: number;
            costs: number;
            net: number;
            puppies_sold: number;
        }[];
    };
    expenses: Expense[];
    litters: { id: number; title: string }[];
    dogs: { id: number; name: string }[];
    categories: Option[];
}>();

const tabs = computed<PageTab[]>(() => [
    { key: 'summary', label: 'Summary' },
    { key: 'expenses', label: 'Expenses', count: props.expenses.length },
]);
const tab = usePageTabs(['summary', 'expenses'], 'summary');

const usd = (cents: number) =>
    `${cents < 0 ? '−' : ''}$${(Math.abs(cents) / 100).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;

const year = ref(String(props.year));
const yearOptions = computed(() =>
    props.years.map((y) => ({ value: String(y), label: String(y) })),
);
const changeYear = () =>
    router.get(
        moneyRoute.url(),
        { year: year.value },
        { preserveState: true, preserveScroll: true },
    );

const editing = ref<number | null>(null);
const today = () => new Date().toLocaleDateString('en-CA');
const form = useForm({
    spent_on: today(),
    category: '',
    amount: '',
    vendor: '',
    description: '',
    litter_id: null as string | null,
    dog_id: null as string | null,
});

function startEdit(e: Expense) {
    editing.value = e.id;
    form.defaults({
        spent_on: e.spent_on,
        category: e.category,
        amount: String(e.amount),
        vendor: e.vendor ?? '',
        description: e.description ?? '',
        litter_id: e.litter_id ? String(e.litter_id) : null,
        dog_id: e.dog_id ? String(e.dog_id) : null,
    }).reset();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancel() {
    editing.value = null;
    form.defaults({
        spent_on: today(),
        category: '',
        amount: '',
        vendor: '',
        description: '',
        litter_id: null,
        dog_id: null,
    }).reset();
}

function save() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => cancel(),
    };

    if (editing.value) {
        form.put(update.url(editing.value), opts);
    } else {
        form.post(store.url(), opts);
    }
}

const litterOptions = computed(() =>
    props.litters.map((l) => ({ value: String(l.id), label: l.title })),
);
const dogOptions = computed(() =>
    props.dogs.map((d) => ({ value: String(d.id), label: d.name })),
);
const catLabel = (v: string) =>
    props.categories.find((c) => c.value === v)?.label ?? v;
</script>

<template>
    <Head title="Money" />

    <div class="max-w-4xl space-y-6 p-4 md:p-6">
        <PageHeader
            title="Money"
            description="What came in and what you spent, for taxes and for knowing how each litter did."
            :help="[
                'Puppy sales and stud fees show up here by themselves. They come from the price on each puppy and the stud fee on each litter, so you never type them twice.',
                'The Expenses tab is for what you spend: vet bills, food, health tests, AKC fees. Add each one with the date and the amount. Tie it to a litter and it counts toward that litter.',
                'The Summary tab adds it up for the year you choose, by kind and by litter. The spreadsheet button saves every expense for your tax person.',
                'This is a simple record, not tax advice. Keep your receipts and ask your tax preparer how to report a kennel.',
            ]"
            help-title="Keeping track of money"
            help-topic="Keep track of money"
        >
            <Button as-child variant="outline" size="sm">
                <a :href="exportMethod.url('expenses')" download
                    ><Download class="size-4" /> Expenses spreadsheet</a
                >
            </Button>
        </PageHeader>

        <div class="max-w-[10rem]">
            <NativeSelect
                v-model="year"
                :options="yearOptions"
                aria-label="Year"
                @update:model-value="changeYear()"
            />
        </div>

        <PageTabs v-model="tab" :tabs="tabs" />

        <div v-show="tab === 'summary'" class="space-y-6">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Came in</p>
                    <p class="font-serif text-3xl font-semibold">
                        {{ usd(summary.income) }}
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Spent</p>
                    <p class="font-serif text-3xl font-semibold">
                        {{ usd(summary.costs) }}
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Left over</p>
                    <p
                        :class="[
                            'font-serif text-3xl font-semibold',
                            summary.net < 0
                                ? 'text-red-700 dark:text-red-400'
                                : 'text-emerald-700 dark:text-emerald-400',
                        ]"
                    >
                        {{ usd(summary.net) }}
                    </p>
                </div>
            </div>

            <EmptyState
                v-if="
                    !summary.income && !summary.costs && !summary.litters.length
                "
                :title="`Nothing recorded for ${year}`"
                description="Puppy sales appear here once a puppy is marked Gone home with a price. Add what you spend on the Expenses tab."
            />

            <section v-if="summary.income_lines.length" class="space-y-2">
                <h2 class="font-serif text-xl font-semibold">
                    Where it came from
                </h2>
                <ul class="divide-y rounded-xl border bg-card text-sm">
                    <li
                        v-for="l in summary.income_lines"
                        :key="l.label"
                        class="flex justify-between gap-3 p-3"
                    >
                        <span>{{ l.label }}</span>
                        <span class="font-medium">{{ usd(l.cents) }}</span>
                    </li>
                </ul>
            </section>

            <section v-if="summary.categories.length" class="space-y-2">
                <h2 class="font-serif text-xl font-semibold">Where it went</h2>
                <ul class="divide-y rounded-xl border bg-card text-sm">
                    <li
                        v-for="c in summary.categories"
                        :key="c.label"
                        class="flex justify-between gap-3 p-3"
                    >
                        <span>{{ c.label }}</span>
                        <span class="font-medium">{{ usd(c.cents) }}</span>
                    </li>
                </ul>
            </section>

            <section v-if="summary.litters.length" class="space-y-2">
                <h2 class="font-serif text-xl font-semibold">By litter</h2>
                <ul class="divide-y rounded-xl border bg-card text-sm">
                    <li
                        v-for="l in summary.litters"
                        :key="l.id"
                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 p-3"
                    >
                        <span>
                            <a
                                :href="`/admin/litters/${l.slug}/edit`"
                                class="font-medium hover:underline"
                                >{{ l.title }}</a
                            >
                            <span
                                v-if="l.puppies_sold"
                                class="block text-muted-foreground"
                                >{{ l.puppies_sold }} puppies sold</span
                            >
                        </span>
                        <span class="text-right">
                            <span class="block"
                                >In {{ usd(l.income) }} · Spent
                                {{ usd(l.costs) }}</span
                            >
                            <span
                                :class="[
                                    'block font-medium',
                                    l.net < 0
                                        ? 'text-red-700 dark:text-red-400'
                                        : '',
                                ]"
                                >{{ usd(l.net) }} left over</span
                            >
                        </span>
                    </li>
                </ul>
            </section>
        </div>

        <div v-show="tab === 'expenses'" class="space-y-6">
            <form
                class="space-y-4 rounded-xl border bg-card p-5"
                @submit.prevent="save"
            >
                <h2 class="font-serif text-lg font-semibold">
                    {{ editing ? 'Change this expense' : 'Add an expense' }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Field
                        label="Date"
                        required
                        for="exp-date"
                        :error="form.errors.spent_on"
                    >
                        <Input
                            id="exp-date"
                            v-model="form.spent_on"
                            type="date"
                        />
                    </Field>
                    <Field
                        label="Kind"
                        required
                        for="exp-cat"
                        :error="form.errors.category"
                    >
                        <NativeSelect
                            id="exp-cat"
                            v-model="form.category"
                            :options="categories"
                            placeholder="Choose…"
                        />
                    </Field>
                    <Field
                        label="Amount"
                        required
                        for="exp-amount"
                        :error="form.errors.amount"
                        hint="In dollars, like 45 or 45.50."
                    >
                        <Input
                            id="exp-amount"
                            v-model="form.amount"
                            inputmode="decimal"
                            placeholder="45.50"
                        />
                    </Field>
                    <Field
                        label="Paid to"
                        for="exp-vendor"
                        :error="form.errors.vendor"
                    >
                        <Input
                            id="exp-vendor"
                            v-model="form.vendor"
                            placeholder="Dr. Smith, Tractor Supply…"
                        />
                    </Field>
                    <Field
                        label="For which litter?"
                        for="exp-litter"
                        :error="form.errors.litter_id"
                        hint="Optional. It then counts toward that litter."
                    >
                        <NativeSelect
                            id="exp-litter"
                            v-model="form.litter_id"
                            :options="litterOptions"
                            placeholder="Not for a litter"
                        />
                    </Field>
                    <Field
                        label="For which dog?"
                        for="exp-dog"
                        :error="form.errors.dog_id"
                        hint="Optional."
                    >
                        <NativeSelect
                            id="exp-dog"
                            v-model="form.dog_id"
                            :options="dogOptions"
                            placeholder="Not for one dog"
                        />
                    </Field>
                </div>
                <Field
                    label="What it was for"
                    for="exp-desc"
                    :error="form.errors.description"
                >
                    <Input
                        id="exp-desc"
                        v-model="form.description"
                        placeholder="Puppy vaccines, hip x-rays…"
                    />
                </Field>
                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">{{
                        editing ? 'Save changes' : 'Add expense'
                    }}</Button>
                    <Button
                        v-if="editing"
                        type="button"
                        variant="ghost"
                        @click="cancel"
                        >Cancel</Button
                    >
                </div>
            </form>

            <EmptyState
                v-if="!expenses.length"
                :title="`No expenses for ${year}`"
                description="Add each bill or receipt above. It only takes a moment."
            />

            <ul v-else class="divide-y rounded-xl border bg-card">
                <li
                    v-for="e in expenses"
                    :key="e.id"
                    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 p-3 text-sm"
                >
                    <span class="min-w-0">
                        <span class="block font-medium">{{
                            e.description || catLabel(e.category)
                        }}</span>
                        <span class="block text-muted-foreground">
                            {{ formatDate(e.spent_on, false) }} ·
                            {{ catLabel(e.category)
                            }}<template v-if="e.vendor">
                                · {{ e.vendor }}</template
                            ><template v-if="e.litter_title">
                                · {{ e.litter_title }}</template
                            ><template v-if="e.dog_name">
                                · {{ e.dog_name }}</template
                            >
                        </span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="font-medium">{{
                            usd(Math.round(e.amount * 100))
                        }}</span>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="startEdit(e)"
                            >Edit</Button
                        >
                        <ConfirmDelete
                            icon
                            label="Remove this expense"
                            :message="`Remove this ${usd(Math.round(e.amount * 100))} expense?`"
                            @confirm="
                                router.delete(destroy.url(e.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
