<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Check, Circle, Info } from '@lucide/vue';
import Field from '@/components/admin/Field.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { update } from '@/routes/admin/akc-programs';

type Item = {
    key: string;
    title: string;
    status: 'ok' | 'todo' | 'info';
    detail: string | null;
    href: string | null;
};
type Program = { title: string; url: string; items: Item[] };

const props = defineProps<{
    programs: {
        heart: Program;
        merit: Program;
        answers: {
            years: number | null;
            club: boolean;
            standing: boolean;
            education: string | null;
        };
    };
}>();

const form = useForm({
    years: props.programs.answers.years as number | null,
    club: props.programs.answers.club,
    standing: props.programs.answers.standing,
    education: props.programs.answers.education ?? '',
});

const save = () =>
    form
        .transform((d) => ({ ...d, education: d.education || null }))
        .put(update.url(), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <p class="text-sm text-muted-foreground">
            AKC runs two programs for breeders. This checks the parts it can see
            in your records, and lets you confirm the rest. AKC decides who
            qualifies, and its rules change, so read each program’s page too.
        </p>

        <section
            v-for="p in [programs.heart, programs.merit]"
            :key="p.title"
            class="space-y-3 rounded-xl border bg-card p-5"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-serif text-lg font-semibold">{{ p.title }}</h2>
                <a
                    :href="p.url"
                    target="_blank"
                    rel="noopener"
                    class="text-sm underline underline-offset-2"
                    >AKC’s requirements</a
                >
            </div>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="i in p.items"
                    :key="i.key"
                    class="flex gap-3 p-3 text-sm"
                >
                    <Check
                        v-if="i.status === 'ok'"
                        class="mt-0.5 size-4 shrink-0 text-emerald-600"
                    />
                    <Info
                        v-else-if="i.status === 'info'"
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                    />
                    <Circle
                        v-else
                        class="mt-0.5 size-4 shrink-0 text-amber-600"
                    />
                    <div class="min-w-0">
                        <p class="font-medium">{{ i.title }}</p>
                        <p v-if="i.detail" class="text-muted-foreground">
                            {{ i.detail }}
                            <Link
                                v-if="i.href && i.status === 'todo'"
                                :href="i.href"
                                class="underline underline-offset-2"
                                >Go there</Link
                            >
                        </p>
                    </div>
                </li>
            </ul>
        </section>

        <form
            class="space-y-4 rounded-xl border border-dashed bg-card p-5"
            @submit.prevent="save"
        >
            <div>
                <h2 class="font-serif text-lg font-semibold">
                    What only you know
                </h2>
                <p class="text-sm text-muted-foreground">
                    The app cannot see these, so tell it. They are private.
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field
                    label="Years you have taken part in AKC events"
                    for="akc-years"
                    :error="form.errors.years"
                    hint="Shows, hunt tests, trials, training classes. A whole number."
                >
                    <Input
                        id="akc-years"
                        v-model="form.years"
                        type="number"
                        min="0"
                        max="80"
                        inputmode="numeric"
                    />
                </Field>
                <Field
                    label="Last AKC breeder education course finished"
                    for="akc-edu"
                    :error="form.errors.education"
                >
                    <Input id="akc-edu" v-model="form.education" type="date" />
                </Field>
            </div>
            <ToggleField
                v-model="form.club"
                label="I belong to an AKC breed club"
            />
            <ToggleField
                v-model="form.standing"
                label="I am in good standing with AKC"
            />
            <Button type="submit" :disabled="form.processing"
                >Save changes</Button
            >
        </form>
    </div>
</template>
