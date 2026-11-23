<script setup lang="ts">
import { Archive } from '@lucide/vue';
import { formatDate } from '@/lib/format';
import ConfirmDelete from '@/components/admin/ConfirmDelete.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    /** What it is, in a sentence: "This dog", "This litter". */
    what: string;
    /** Date before which it cannot be erased, if any. */
    blockedUntil?: string | null;
}>();

defineEmits<{ restore: []; purge: [] }>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm"
    >
        <div class="flex items-start gap-3">
            <Archive
                class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
            />
            <div>
                <p class="font-medium">{{ what }} is archived.</p>
                <p class="text-muted-foreground">
                    It is hidden from your lists and the website, but its
                    records are kept. Bring it back any time.
                    <template v-if="blockedUntil">
                        AKC asks you to keep it until
                        {{ formatDate(blockedUntil) }}, so it cannot be erased
                        before then.
                    </template>
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <Button type="button" size="sm" @click="$emit('restore')"
                >Bring it back</Button
            >
            <ConfirmDelete
                label="Delete for good"
                message="Delete this for good? Everything saved with it is erased and this cannot be undone."
                @confirm="$emit('purge')"
            />
        </div>
    </div>
</template>
