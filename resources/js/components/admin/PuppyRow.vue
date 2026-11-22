<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, update } from '@/routes/admin/puppies';
import type { Option, Puppy } from '@/types';

const props = defineProps<{
    puppy: Puppy;
    sex: Option[];
    color: Option[];
    status: Option[];
    contacts: { id: number; name: string }[];
}>();

const form = useForm({
    name: props.puppy.name ?? '',
    collar: props.puppy.collar ?? '',
    sex: props.puppy.sex,
    color: props.puppy.color,
    birth_weight_g: props.puppy.birth_weight_g,
    status: props.puppy.status,
    contact_id: props.puppy.contact_id,
    sale_price: props.puppy.sale_price,
    notes: props.puppy.notes ?? '',
});

function save() {
    form.put(update.url(props.puppy.id), {
        preserveScroll: true,
        onSuccess: () => form.defaults().reset(),
    });
}
</script>

<template>
    <form
        class="grid gap-2 rounded-lg border bg-background/50 p-3 sm:grid-cols-6"
        @submit.prevent="save"
    >
        <Input
            v-model="form.collar"
            placeholder="Collar (e.g. Red)"
            class="sm:col-span-1"
            aria-label="Collar"
        />
        <Input
            v-model="form.name"
            placeholder="Name"
            class="sm:col-span-2"
            aria-label="Name"
        />
        <NativeSelect v-model="form.sex" :options="sex" class="sm:col-span-1" />
        <NativeSelect
            v-model="form.color"
            :options="color"
            placeholder="Colour"
            class="sm:col-span-1"
        />
        <NativeSelect
            v-model="form.status"
            :options="status"
            class="sm:col-span-1"
        />
        <NativeSelect
            v-model="form.contact_id"
            :options="
                contacts.map((c) => ({ value: String(c.id), label: c.name }))
            "
            placeholder="Buyer…"
            class="sm:col-span-2"
        />
        <Input
            v-model="form.sale_price"
            type="number"
            min="0"
            placeholder="Sold for $"
            class="sm:col-span-1"
            aria-label="Sale price"
        />
        <Input
            v-model="form.birth_weight_g"
            type="number"
            min="0"
            placeholder="Birth wt (g)"
            class="sm:col-span-1"
            aria-label="Birth weight in grams"
        />
        <Input
            v-model="form.notes"
            placeholder="Notes"
            class="sm:col-span-1"
            aria-label="Notes"
        />
        <div class="flex items-center justify-end gap-1 sm:col-span-1">
            <Button
                v-if="form.isDirty"
                type="submit"
                size="sm"
                :disabled="form.processing"
                >Save</Button
            >
            <ConfirmDelete
                icon
                message="Remove this puppy?"
                @confirm="
                    router.delete(destroy.url(puppy.id), {
                        preserveScroll: true,
                    })
                "
            />
        </div>
    </form>
</template>
