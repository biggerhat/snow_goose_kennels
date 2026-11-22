<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    label?: string;
    message: string;
    icon?: boolean;
}>();
const emit = defineEmits<{ confirm: [] }>();

function ask() {
    if (window.confirm(props.message)) {
        emit('confirm');
    }
}
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        :size="icon ? 'icon' : 'sm'"
        class="text-destructive hover:text-destructive"
        :aria-label="label ?? 'Delete'"
        @click="ask"
    >
        <Trash2 class="size-4" />
        <span v-if="!icon">{{ label ?? 'Delete' }}</span>
    </Button>
</template>
