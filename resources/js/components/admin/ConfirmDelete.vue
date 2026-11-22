<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    label?: string;
    message: string;
    icon?: boolean;
    /** Wording for the confirm button, e.g. "Archive". */
    confirmLabel?: string;
}>();
defineEmits<{ confirm: [] }>();

const open = ref(false);
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        :size="icon ? 'icon' : 'sm'"
        class="text-destructive hover:text-destructive"
        :aria-label="label ?? 'Delete'"
        @click="open = true"
    >
        <Trash2 class="size-4" />
        <span v-if="!icon">{{ label ?? 'Delete' }}</span>
    </Button>

    <ConfirmDialog
        v-model:open="open"
        :message="message"
        :confirm-label="confirmLabel ?? label ?? 'Delete'"
        @confirm="$emit('confirm')"
    />
</template>
