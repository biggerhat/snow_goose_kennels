<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { quick } from '@/routes/admin/dogs';

const props = defineProps<{
    /** The sex this parent must be. */
    sex: 'male' | 'female';
    label: string;
    contacts: { id: number; name: string }[];
    /** Which page prop holds the list of dogs (so it can refresh). */
    reload?: string;
}>();

const emit = defineEmits<{ added: [name: string] }>();

const open = ref(false);
const form = useForm({
    name: '',
    registered_name: '',
    registry_number: '',
    sex: props.sex,
    owner_contact_id: null as number | null,
});

function save() {
    form.post(quick.url(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('added', form.name);
            form.reset();
            open.value = false;
            router.reload({ only: [props.reload ?? 'parents'] });
        },
    });
}
</script>

<template>
    <div>
        <button
            v-if="!open"
            type="button"
            class="min-h-11 text-left text-sm text-primary underline underline-offset-2"
            @click="open = true"
        >
            {{ label }} not in the list? Add a dog from another kennel
        </button>

        <div
            v-else
            class="space-y-3 rounded-lg border border-dashed bg-background/60 p-3"
        >
            <p class="text-sm font-medium">
                Add a {{ sex === 'male' ? 'father' : 'mother' }} from another
                kennel
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
                <Field
                    label="Everyday name"
                    required
                    :error="form.errors.name"
                    hint="If you have a dog with that name already, add the kennel name."
                >
                    <Input v-model="form.name" placeholder="Duke" />
                </Field>
                <Field
                    label="Registered name"
                    :error="form.errors.registered_name"
                >
                    <Input v-model="form.registered_name" />
                </Field>
                <Field
                    label="Registration number"
                    :error="form.errors.registry_number"
                    hint="From the papers, such as AKC."
                >
                    <Input v-model="form.registry_number" />
                </Field>
                <Field
                    label="Owner"
                    :error="form.errors.owner_contact_id"
                    hint="Choose from your Contacts, if you have them there."
                >
                    <NativeSelect
                        v-model="form.owner_contact_id"
                        :options="
                            contacts.map((c) => ({
                                value: String(c.id),
                                label: c.name,
                            }))
                        "
                        placeholder="Not in my Contacts"
                    />
                </Field>
            </div>
            <div class="flex gap-2">
                <Button
                    type="button"
                    size="sm"
                    :disabled="form.processing"
                    @click="save"
                    >Add dog</Button
                >
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    @click="open = false"
                    >Cancel</Button
                >
            </div>
        </div>
    </div>
</template>
