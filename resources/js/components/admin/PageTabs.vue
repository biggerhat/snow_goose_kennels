<script setup lang="ts" generic="K extends string">
export type PageTab<T extends string = string> = {
    key: T;
    label: string;
    /** A small number shown beside the label. */
    count?: number | null;
    /** Colours the number amber, for "things to do". */
    alert?: boolean;
};

defineProps<{ tabs: PageTab<K>[]; label?: string }>();
const model = defineModel<K>({ required: true });

function pick(key: K) {
    model.value = key;
    history.replaceState(null, '', `#${key}`);
}
</script>

<template>
    <div
        role="tablist"
        :aria-label="label ?? 'Parts of this page'"
        class="sticky top-0 z-20 -mx-4 flex [scrollbar-width:none] gap-1 overflow-x-auto border-b bg-background/95 px-4 backdrop-blur md:-mx-6 md:px-6"
    >
        <button
            v-for="t in tabs"
            :id="`tab-${t.key}`"
            :key="t.key"
            type="button"
            role="tab"
            :aria-selected="model === t.key"
            :class="[
                'inline-flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-3 text-sm font-medium whitespace-nowrap',
                model === t.key
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground',
            ]"
            @click="pick(t.key)"
        >
            {{ t.label }}
            <span
                v-if="t.count"
                :class="[
                    'rounded-full px-1.5 text-xs',
                    t.alert
                        ? 'bg-amber-500/20 text-amber-900 dark:text-amber-200'
                        : 'bg-muted text-muted-foreground',
                ]"
                >{{ t.count }}</span
            >
        </button>
    </div>
</template>
