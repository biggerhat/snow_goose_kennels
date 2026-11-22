<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import { destroy, generate, store, update } from '@/routes/admin/tasks';
import type { LitterTask } from '@/types';

const props = defineProps<{
    slug: string;
    tasks: LitterTask[];
    hasBirthDate: boolean;
}>();

const todayStr = () => new Date().toLocaleDateString('en-CA');
const late = (t: LitterTask) => !t.done_on && t.due_on < todayStr();
const todo = computed(() => props.tasks.filter((t) => !t.done_on));
const done = computed(() => props.tasks.filter((t) => t.done_on));

const form = useForm({ title: '', detail: '', due_on: '' });

function add() {
    form.post(store.url(props.slug), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const tick = (t: LitterTask, value: boolean) =>
    router.put(update.url(t.id), { done: value }, { preserveScroll: true });
const move = (t: LitterTask, due_on: string) =>
    due_on &&
    router.put(update.url(t.id), { due_on }, { preserveScroll: true });
</script>

<template>
    <div class="space-y-4">
        <div v-if="!tasks.length" class="space-y-3 text-sm">
            <p class="text-muted-foreground">
                A list of the usual steps for raising a litter: weigh-ins,
                deworming, first vaccines, the vet check and go-home day. They
                are counted from the birth date, and each one shows on your
                dashboard when it is due.
            </p>
            <Button
                type="button"
                :disabled="!hasBirthDate"
                @click="
                    router.post(
                        generate.url(slug),
                        {},
                        { preserveScroll: true },
                    )
                "
                >Make the puppy schedule</Button
            >
            <p v-if="!hasBirthDate" class="text-muted-foreground">
                Add the birth date on the Details tab first.
            </p>
        </div>

        <template v-else>
            <p class="text-sm text-muted-foreground">
                Tick a step when it is done. This is a typical schedule. Your
                vet has the final say, so change a date or remove any step.
            </p>
            <ul class="divide-y rounded-lg border bg-card">
                <li
                    v-for="t in todo"
                    :key="t.id"
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 p-3"
                >
                    <label class="flex min-w-0 flex-1 items-start gap-3">
                        <input
                            type="checkbox"
                            class="mt-1 size-5 shrink-0 accent-primary"
                            :aria-label="`Done: ${t.title}`"
                            @change="
                                tick(
                                    t,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <span class="min-w-0 text-sm">
                            <span class="block font-medium">{{ t.title }}</span>
                            <span
                                v-if="t.detail"
                                class="block text-muted-foreground"
                                >{{ t.detail }}</span
                            >
                        </span>
                    </label>
                    <span class="flex items-center gap-2">
                        <span
                            :class="[
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                late(t)
                                    ? 'bg-red-500/15 text-red-800 dark:text-red-300'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                            >{{ late(t) ? 'Late · ' : ''
                            }}{{ formatDate(t.due_on, false) }}</span
                        >
                        <input
                            type="date"
                            :value="t.due_on"
                            class="w-9 cursor-pointer rounded-md border bg-transparent p-1 text-transparent"
                            :aria-label="`Change the date for ${t.title}`"
                            title="Change the date"
                            @change="
                                move(
                                    t,
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <ConfirmDelete
                            icon
                            label="Remove this step"
                            :message="`Remove “${t.title}” from the schedule?`"
                            @confirm="
                                router.delete(destroy.url(t.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </span>
                </li>
                <li
                    v-if="!todo.length"
                    class="p-3 text-sm text-muted-foreground"
                >
                    Every step is done. Nice work.
                </li>
            </ul>

            <details v-if="done.length" class="rounded-lg border p-3 text-sm">
                <summary class="cursor-pointer font-medium">
                    Done ({{ done.length }})
                </summary>
                <ul class="mt-2 space-y-1">
                    <li
                        v-for="t in done"
                        :key="t.id"
                        class="flex items-center justify-between gap-2 text-muted-foreground"
                    >
                        <span class="line-through">{{ t.title }}</span>
                        <span class="flex items-center gap-2">
                            {{ formatDate(t.done_on!, false) }}
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="tick(t, false)"
                                >Undo</Button
                            >
                        </span>
                    </li>
                </ul>
            </details>
            <Button
                v-if="hasBirthDate"
                type="button"
                variant="ghost"
                size="sm"
                @click="
                    router.post(
                        generate.url(slug),
                        {},
                        { preserveScroll: true },
                    )
                "
                >Add any missing usual steps</Button
            >
        </template>

        <form
            class="grid gap-3 rounded-lg border border-dashed p-3 sm:grid-cols-[1fr_auto_auto] sm:items-end"
            @submit.prevent="add"
        >
            <Field
                label="Add your own step"
                for="task-title"
                :error="form.errors.title"
            >
                <Input
                    id="task-title"
                    v-model="form.title"
                    placeholder="Vet visit, nail trim…"
                />
            </Field>
            <Field label="Due" for="task-due" :error="form.errors.due_on">
                <Input id="task-due" v-model="form.due_on" type="date" />
            </Field>
            <Button
                type="submit"
                variant="secondary"
                :disabled="form.processing"
                >Add step</Button
            >
            <InputError :message="form.errors.detail" />
        </form>
    </div>
</template>
