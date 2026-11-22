<script setup lang="ts">
import PageHelp from '@/components/admin/PageHelp.vue';

defineProps<{
    title: string;
    description?: string;
    /** Plain-language steps shown under the title. */
    help?: string[];
    helpTitle?: string;
}>();
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-serif text-3xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <slot />
            </div>
        </div>
        <PageHelp
            v-if="help?.length"
            :id="title"
            :title="helpTitle"
            :steps="help"
        />
    </div>
</template>
