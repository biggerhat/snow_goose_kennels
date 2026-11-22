<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import Field from '@/components/admin/Field.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TextArea from '@/components/admin/TextArea.vue';
import ToggleField from '@/components/admin/ToggleField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, index, store, update } from '@/routes/admin/testimonials';
import type { Testimonial } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Happy families', href: index() }] },
});
defineProps<{ testimonials: Testimonial[] }>();

const form = useForm({
    name: '',
    location: '',
    puppy_name: '',
    body: '',
    is_public: true,
});
const editing = ref<number | null>(null);

function start(t: Testimonial) {
    editing.value = t.id;
    form.name = t.name;
    form.location = t.location ?? '';
    form.puppy_name = t.puppy_name ?? '';
    form.body = t.body;
    form.is_public = t.is_public ?? true;
}

function cancel() {
    editing.value = null;
    form.reset();
}

function submit() {
    const opts = { preserveScroll: true, onSuccess: cancel };

    if (editing.value) {
        form.put(update.url(editing.value), opts);
    } else {
        form.post(store.url(), opts);
    }
}
</script>

<template>
    <Head title="Happy families" />

    <div class="max-w-3xl space-y-8 p-4 md:p-6">
        <PageHeader
            title="Happy families"
            description="Kind words from buyers. Add them only with the person's permission; first names are plenty."
        />

        <form
            class="space-y-4 rounded-xl border bg-card p-5"
            @submit.prevent="submit"
        >
            <h2 class="font-serif text-lg font-semibold">
                {{ editing ? 'Edit story' : 'Add a story' }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <Field label="Name" :error="form.errors.name"
                    ><Input
                        v-model="form.name"
                        required
                        placeholder="The Millers"
                /></Field>
                <Field label="Where" :error="form.errors.location"
                    ><Input v-model="form.location" placeholder="Boise, ID"
                /></Field>
                <Field label="Their puppy" :error="form.errors.puppy_name"
                    ><Input v-model="form.puppy_name" placeholder="Maggie"
                /></Field>
            </div>
            <Field label="What they said" :error="form.errors.body"
                ><TextArea v-model="form.body" :rows="4"
            /></Field>
            <ToggleField v-model="form.is_public" label="Show on the website" />
            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{
                    editing ? 'Save' : 'Add story'
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
            v-if="!testimonials.length"
            title="No stories yet"
            description="Photos and notes from families are some of the best proof a kennel can show."
        />

        <ul class="space-y-3">
            <li
                v-for="t in testimonials"
                :key="t.id"
                class="space-y-2 rounded-xl border bg-card p-4"
            >
                <p class="whitespace-pre-line">“{{ t.body }}”</p>
                <div
                    class="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground"
                >
                    <span
                        >{{ t.name
                        }}<span v-if="t.location">, {{ t.location }}</span
                        ><span v-if="t.puppy_name"> · {{ t.puppy_name }}</span
                        ><span v-if="!t.is_public"> · hidden</span></span
                    >
                    <span class="flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="start(t)"
                            >Edit</Button
                        >
                        <ConfirmDelete
                            icon
                            message="Delete this story?"
                            @confirm="
                                router.delete(destroy.url(t.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </span>
                </div>
            </li>
        </ul>
    </div>
</template>
