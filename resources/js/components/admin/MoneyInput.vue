<script setup lang="ts">
import { Input } from '@/components/ui/input';

defineProps<{ placeholder?: string; id?: string }>();
const model = defineModel<string | number | null>();
</script>

<template>
    <div class="relative">
        <span
            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
            >$</span
        >
        <Input
            :id="id"
            v-model="model"
            inputmode="numeric"
            autocomplete="off"
            :placeholder="placeholder"
            class="pl-7"
        />
    </div>
</template>
