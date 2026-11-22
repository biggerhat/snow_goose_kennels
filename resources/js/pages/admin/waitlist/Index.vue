<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDate, labelFor, money } from '@/lib/format';
import { create, edit, index } from '@/routes/admin/waitlist';
import type { Options, WaitlistEntry } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Waitlist', href: index() }] },
});
const props = defineProps<{ entries: WaitlistEntry[] }>();
const options = computed(() => usePage().props.options as Options);

const groups = computed(() => {
    const map = new Map<string, { title: string; items: WaitlistEntry[] }>();

    for (const e of props.entries) {
        const key = e.litter ? String(e.litter.id) : 'none';

        if (!map.has(key)) {
            map.set(key, {
                title: e.litter?.title ?? 'Any upcoming litter',
                items: [],
            });
        }

        map.get(key)!.items.push(e);
    }

    return [...map.values()];
});

function prefs(e: WaitlistEntry): string {
    return [
        labelFor(options.value.sex, e.preferred_sex),
        labelFor(options.value.color, e.preferred_color),
    ]
        .filter(Boolean)
        .join(', ');
}
</script>

<template>
    <Head title="Waitlist" />

    <div class="space-y-8 p-4 md:p-6">
        <PageHeader
            title="Waitlist"
            description="Everyone waiting on a puppy, by litter."
        >
            <Button as-child
                ><Link :href="create()">Add to waitlist</Link></Button
            >
        </PageHeader>

        <EmptyState
            v-if="!entries.length"
            title="The waitlist is empty"
            description="Add people as they call or message you. You can attach them to a specific litter or leave it open."
        >
            <Button as-child
                ><Link :href="create()">Add the first person</Link></Button
            >
        </EmptyState>

        <section v-for="g in groups" :key="g.title" class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">{{ g.title }}</h2>
            <ul class="divide-y rounded-xl border bg-card">
                <li v-for="e in g.items" :key="e.id">
                    <Link
                        :href="edit(e.id)"
                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 p-4 transition-colors hover:bg-muted/50"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                <span
                                    v-if="e.pick_order"
                                    class="mr-1.5 rounded bg-muted px-1.5 py-0.5 text-xs"
                                    >Pick {{ e.pick_order }}</span
                                >{{ e.contact?.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ e.contact?.phone ?? e.contact?.email }}
                                <span v-if="prefs(e)"
                                    >· wants {{ prefs(e) }}</span
                                >
                                <span v-if="e.deposit_amount"
                                    >· deposit {{ money(e.deposit_amount)
                                    }}<template v-if="e.deposit_paid_on">
                                        paid
                                        {{
                                            formatDate(e.deposit_paid_on)
                                        }}</template
                                    ></span
                                >
                                <span v-if="e.puppy"
                                    >· matched with
                                    {{ e.puppy.name || e.puppy.collar }}</span
                                >
                            </p>
                        </div>
                        <StatusBadge
                            :value="e.status"
                            :label="labelFor(options.waitlistStatus, e.status)"
                        />
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
