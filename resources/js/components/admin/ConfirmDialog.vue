<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    title?: string;
    message: string;
    confirmLabel?: string;
    /** Red button for things that cannot be taken back. */
    destructive?: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });
defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title ?? 'Are you sure?' }}</DialogTitle>
                <DialogDescription class="text-sm leading-relaxed">
                    {{ message }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:justify-end">
                <Button
                    type="button"
                    variant="outline"
                    autofocus
                    @click="open = false"
                    >Cancel</Button
                >
                <Button
                    type="button"
                    :variant="destructive === false ? 'default' : 'destructive'"
                    @click="
                        open = false;
                        $emit('confirm');
                    "
                >
                    {{ confirmLabel ?? 'Yes, do it' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
