<script setup lang="ts">
import { Check, Circle, Info, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';

export type AkcItem = {
    key: string;
    status: 'ok' | 'todo' | 'warn' | 'info';
    title: string;
    detail?: string | null;
};

const props = defineProps<{
    akc: { items: AkcItem[]; todo: number; warn: number };
    title?: string;
}>();

const counted = computed(() =>
    props.akc.items.filter((i) => i.status !== 'info'),
);
const done = computed(
    () => counted.value.filter((i) => i.status === 'ok').length,
);
const percent = computed(() =>
    counted.value.length
        ? Math.round((done.value / counted.value.length) * 100)
        : 100,
);
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="counted.length"
            class="flex items-center gap-3"
            role="img"
            :aria-label="`${done} of ${counted.length} done`"
        >
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-emerald-600 transition-all"
                    :style="{ width: percent + '%' }"
                />
            </div>
            <span class="text-sm font-medium tabular-nums"
                >{{ done }} of {{ counted.length }}</span
            >
        </div>
        <p class="text-sm text-muted-foreground">
            <template v-if="akc.warn || akc.todo">
                <strong class="text-foreground">{{
                    akc.warn + akc.todo
                }}</strong>
                thing{{ akc.warn + akc.todo === 1 ? '' : 's' }} to look at.
            </template>
            <template v-else>Everything on this list is done.</template>
            This list helps you remember what AKC asks for. AKC’s own rules are
            the final word, so check akc.org if you are unsure.
        </p>
        <ul class="divide-y rounded-lg border">
            <li
                v-for="i in akc.items"
                :key="i.key"
                class="flex gap-3 p-3 text-sm"
            >
                <span class="mt-0.5 shrink-0">
                    <Check
                        v-if="i.status === 'ok'"
                        class="size-4 text-emerald-600"
                    />
                    <TriangleAlert
                        v-else-if="i.status === 'warn'"
                        class="size-4 text-red-600"
                    />
                    <Circle
                        v-else-if="i.status === 'todo'"
                        class="size-4 text-amber-600"
                    />
                    <Info v-else class="size-4 text-muted-foreground" />
                </span>
                <span class="min-w-0">
                    <span class="block font-medium">{{ i.title }}</span>
                    <span v-if="i.detail" class="block text-muted-foreground">{{
                        i.detail
                    }}</span>
                </span>
            </li>
        </ul>
    </div>
</template>
