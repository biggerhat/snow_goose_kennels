<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Trophy } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, labelFor } from '@/lib/format';
import { destroy, store, update } from '@/routes/admin/achievements';
import type { Achievement, Options } from '@/types';

const props = defineProps<{ slug: string; items: Achievement[] }>();
const options = computed(() => usePage().props.options as Options);
const adding = ref(false);

const form = useForm({
    title: '',
    full_name: '',
    kind: 'hunt_test',
    earned_on: '',
    event: '',
    notes: '',
});

function add() {
    form.post(store.url(props.slug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            adding.value = false;
        },
    });
}

const editing = ref<number | null>(null);
const edit = useForm({
    title: '',
    full_name: '',
    kind: 'other',
    earned_on: '',
    event: '',
    notes: '',
});

function startEdit(a: Achievement) {
    editing.value = a.id;
    edit.title = a.title;
    edit.full_name = a.full_name ?? '';
    edit.kind = a.kind;
    edit.earned_on = a.earned_on ?? '';
    edit.event = a.event ?? '';
    edit.notes = a.notes ?? '';
    edit.clearErrors();
}

function save(a: Achievement) {
    edit.put(update.url(a.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}
</script>

<template>
    <div class="space-y-3">
        <ul v-if="items.length" class="divide-y rounded-lg border">
            <li v-for="a in items" :key="a.id" class="p-3 text-sm">
                <div
                    v-if="editing !== a.id"
                    class="flex flex-wrap items-start justify-between gap-2"
                >
                    <div class="flex gap-3">
                        <Trophy
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <div>
                            <p class="font-medium">
                                {{ a.title }}
                                <span
                                    v-if="a.full_name"
                                    class="font-normal text-muted-foreground"
                                    >· {{ a.full_name }}</span
                                >
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ labelFor(options.achievementKind, a.kind) }}
                                <template v-if="a.earned_on">
                                    · {{ formatDate(a.earned_on) }}</template
                                >
                                <template v-if="a.event">
                                    · {{ a.event }}</template
                                >
                            </p>
                            <p v-if="a.notes" class="mt-0.5 text-xs">
                                {{ a.notes }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="startEdit(a)"
                            >Edit</Button
                        >
                        <ConfirmDelete
                            icon
                            message="Remove this title?"
                            @confirm="
                                router.delete(destroy.url(a.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </div>
                </div>
                <form
                    v-else
                    class="grid gap-3 sm:grid-cols-2"
                    @submit.prevent="save(a)"
                >
                    <Field label="Letters" required :error="edit.errors.title"
                        ><Input v-model="edit.title"
                    /></Field>
                    <Field
                        label="What it stands for"
                        :error="edit.errors.full_name"
                        ><Input v-model="edit.full_name"
                    /></Field>
                    <Field label="Kind" :error="edit.errors.kind"
                        ><NativeSelect
                            v-model="edit.kind"
                            :options="options.achievementKind"
                    /></Field>
                    <Field label="Earned on" :error="edit.errors.earned_on"
                        ><Input v-model="edit.earned_on" type="date"
                    /></Field>
                    <Field
                        label="Event or club"
                        :error="edit.errors.event"
                        class="sm:col-span-2"
                        ><Input v-model="edit.event"
                    /></Field>
                    <Field
                        label="Notes"
                        :error="edit.errors.notes"
                        class="sm:col-span-2"
                        ><Input v-model="edit.notes"
                    /></Field>
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
            No titles recorded yet.
        </p>

        <Button
            v-if="!adding"
            type="button"
            size="sm"
            variant="secondary"
            @click="adding = true"
        >
            <Trophy class="size-4" /> Add a title or award
        </Button>
        <form
            v-else
            class="space-y-3 rounded-lg border bg-background/50 p-3"
            @submit.prevent="add"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <Field
                    label="Letters"
                    required
                    :error="form.errors.title"
                    hint="Like MH or SH."
                >
                    <Input v-model="form.title" placeholder="MH" />
                </Field>
                <Field
                    label="What it stands for"
                    :error="form.errors.full_name"
                    hint="Like Master Hunter."
                >
                    <Input
                        v-model="form.full_name"
                        placeholder="Master Hunter"
                    />
                </Field>
                <Field label="Kind" :error="form.errors.kind"
                    ><NativeSelect
                        v-model="form.kind"
                        :options="options.achievementKind"
                /></Field>
                <Field label="Earned on" :error="form.errors.earned_on"
                    ><Input v-model="form.earned_on" type="date"
                /></Field>
                <Field
                    label="Event or club"
                    :error="form.errors.event"
                    class="sm:col-span-2"
                    ><Input v-model="form.event"
                /></Field>
            </div>
            <Field label="Notes" :error="form.errors.notes"
                ><Input v-model="form.notes"
            /></Field>
            <div class="flex gap-2">
                <Button type="submit" size="sm" :disabled="form.processing"
                    >Add</Button
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
