<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { FilePlus, FileText } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import Field from '@/components/admin/Field.vue';
import FileInput from '@/components/admin/FileInput.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { daysUntil, formatDate, labelFor } from '@/lib/format';
import { destroy, store, update } from '@/routes/admin/documents';
import type { DocumentFile, Options } from '@/types';

const props = defineProps<{
    type: 'dog' | 'litter' | 'puppy' | 'contact' | 'kennel';
    id: number;
    documents: DocumentFile[];
    /** Start the category on this value. */
    category?: string;
    /** Smaller headings when used inside a puppy card. */
    compact?: boolean;
}>();

const options = computed(() => usePage().props.options as Options);
const adding = ref(false);

const form = useForm({
    file: null as File | null,
    documentable_type: props.type,
    documentable_id: props.id,
    category: props.category ?? 'other',
    title: '',
    issued_on: '',
    expires_on: '',
    notes: '',
});

function add() {
    form.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file', 'title', 'issued_on', 'expires_on', 'notes');
            adding.value = false;
        },
    });
}

function size(bytes: number): string {
    return bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function expiry(d: DocumentFile): { text: string; tone: string } | null {
    const days = daysUntil(d.expires_on);

    if (days === null) {
        return null;
    }

    if (days < 0) {
        return {
            text: `Expired ${formatDate(d.expires_on)}`,
            tone: 'bg-red-500/15 text-red-700 dark:text-red-300',
        };
    }

    if (days <= 60) {
        return {
            text: `Expires ${formatDate(d.expires_on)}`,
            tone: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
        };
    }

    return {
        text: `Valid until ${formatDate(d.expires_on)}`,
        tone: 'bg-muted text-muted-foreground',
    };
}

// Editing one document at a time.
const editing = ref<number | null>(null);
const edit = useForm({
    category: 'other',
    title: '',
    issued_on: '',
    expires_on: '',
    notes: '',
});

function startEdit(d: DocumentFile) {
    editing.value = d.id;
    edit.category = d.category;
    edit.title = d.title;
    edit.issued_on = d.issued_on ?? '';
    edit.expires_on = d.expires_on ?? '';
    edit.notes = d.notes ?? '';
    edit.clearErrors();
}

function saveEdit(d: DocumentFile) {
    edit.put(update.url(d.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}
</script>

<template>
    <div class="space-y-3">
        <ul v-if="documents.length" class="divide-y rounded-lg border">
            <li v-for="d in documents" :key="d.id" class="p-3 text-sm">
                <div
                    v-if="editing !== d.id"
                    class="flex flex-wrap items-start justify-between gap-2"
                >
                    <div class="flex min-w-0 gap-3">
                        <FileText
                            class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0">
                            <a
                                :href="d.url"
                                target="_blank"
                                rel="noopener"
                                class="font-medium underline-offset-2 hover:underline"
                                >{{ d.title }}</a
                            >
                            <p class="text-xs text-muted-foreground">
                                {{
                                    labelFor(
                                        options.documentCategory,
                                        d.category,
                                    )
                                }}
                                · {{ d.original_name }} · {{ size(d.size) }}
                                <template v-if="d.issued_on">
                                    · dated
                                    {{ formatDate(d.issued_on) }}</template
                                >
                            </p>
                            <p v-if="d.notes" class="mt-0.5 text-xs">
                                {{ d.notes }}
                            </p>
                            <span
                                v-if="expiry(d)"
                                :class="[
                                    'mt-1 inline-block rounded-full px-2 py-0.5 text-xs',
                                    expiry(d)?.tone,
                                ]"
                                >{{ expiry(d)?.text }}</span
                            >
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            @click="startEdit(d)"
                            >Edit</Button
                        >
                        <ConfirmDelete
                            icon
                            :message="`Delete “${d.title}”? The file is removed for good.`"
                            @confirm="
                                router.delete(destroy.url(d.id), {
                                    preserveScroll: true,
                                })
                            "
                        />
                    </div>
                </div>

                <form
                    v-else
                    class="grid gap-3 sm:grid-cols-2"
                    @submit.prevent="saveEdit(d)"
                >
                    <Field label="Name" required :error="edit.errors.title">
                        <Input v-model="edit.title" />
                    </Field>
                    <Field label="What it is" :error="edit.errors.category">
                        <NativeSelect
                            v-model="edit.category"
                            :options="options.documentCategory"
                        />
                    </Field>
                    <Field label="Dated" :error="edit.errors.issued_on">
                        <Input v-model="edit.issued_on" type="date" />
                    </Field>
                    <Field
                        label="Runs out on"
                        :error="edit.errors.expires_on"
                        hint="Only for papers that expire."
                    >
                        <Input v-model="edit.expires_on" type="date" />
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
        <p v-else class="text-sm text-muted-foreground">No documents yet.</p>

        <Button
            v-if="!adding"
            type="button"
            size="sm"
            variant="secondary"
            @click="adding = true"
        >
            <FilePlus class="size-4" /> Add a document
        </Button>

        <form
            v-else
            class="space-y-3 rounded-lg border bg-background/50 p-3"
            @submit.prevent="add"
        >
            <Field
                label="File"
                required
                :error="form.errors.file"
                hint="A PDF, a photo of the papers (JPG or PNG), or a Word or Excel file. Up to 25 MB. These files are private: only you can open them."
            >
                <FileInput
                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                    @change="(files) => (form.file = files?.[0] ?? null)"
                />
            </Field>
            <div class="grid gap-3 sm:grid-cols-2">
                <Field
                    label="What it is"
                    required
                    :error="form.errors.category"
                >
                    <NativeSelect
                        v-model="form.category"
                        :options="options.documentCategory"
                    />
                </Field>
                <Field
                    label="Name"
                    :error="form.errors.title"
                    hint="Leave empty to use the file’s name."
                >
                    <Input v-model="form.title" />
                </Field>
                <Field
                    label="Dated"
                    :error="form.errors.issued_on"
                    hint="The date on the paper, if there is one."
                >
                    <Input v-model="form.issued_on" type="date" />
                </Field>
                <Field
                    label="Runs out on"
                    :error="form.errors.expires_on"
                    help="Only for papers that expire, like a rabies certificate. You will see a reminder before the date."
                >
                    <Input v-model="form.expires_on" type="date" />
                </Field>
            </div>
            <Field label="Notes" :error="form.errors.notes">
                <Input v-model="form.notes" />
            </Field>
            <div class="flex gap-2">
                <Button
                    type="submit"
                    size="sm"
                    :disabled="form.processing || !form.file"
                >
                    {{ form.processing ? 'Adding…' : 'Add document' }}
                </Button>
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
