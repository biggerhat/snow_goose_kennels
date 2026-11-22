<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { countdown, formatDate, labelFor, money } from '@/lib/format';
import { create, edit, index, restore } from '@/routes/admin/litters';
import { router } from '@inertiajs/vue3';
import type { Litter, Options } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Litters', href: index() }] },
});
const props = defineProps<{
    litters: Litter[];
    archived: {
        id: number;
        slug: string;
        title: string;
        born_on: string | null;
    }[];
}>();
const options = computed(() => usePage().props.options as Options);

const groups = computed(() =>
    [
        {
            title: 'Coming up',
            items: props.litters.filter((l) =>
                ['planned', 'expected'].includes(l.status),
            ),
        },
        {
            title: 'On the ground',
            items: props.litters.filter((l) => l.status === 'on_ground'),
        },
        {
            title: 'All gone home',
            items: props.litters.filter((l) => l.status === 'all_home'),
        },
    ].filter((g) => g.items.length),
);
</script>

<template>
    <Head title="Litters" />

    <div class="space-y-8 p-4 md:p-6">
        <PageHeader
            title="Litters"
            description="Planned, expected and past litters."
            :help="[
                'A litter is one set of puppies from the same mother. They are grouped by where they are: planned or expected, on the ground (born), and all gone home.',
                'Tap a litter to add its puppies, weigh them, post updates and see who is waiting.',
                'Litters you untick “Show on the website” for stay private.',
            ]"
            help-title="Your litters"
        >
            <Button as-child><Link :href="create()">New litter</Link></Button>
        </PageHeader>

        <EmptyState
            v-if="!litters.length"
            title="No litters yet"
            description="Start with the pairing and a due date; add puppies once they arrive."
        >
            <Button as-child
                ><Link :href="create()">Create a litter</Link></Button
            >
        </EmptyState>

        <section v-for="g in groups" :key="g.title" class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">{{ g.title }}</h2>
            <div class="grid gap-3 lg:grid-cols-2">
                <Link
                    v-for="l in g.items"
                    :key="l.id"
                    :href="edit(l.slug)"
                    class="space-y-2 rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-serif text-lg font-semibold">
                            {{ l.title }}
                        </p>
                        <StatusBadge
                            :value="l.status"
                            :label="labelFor(options.litterStatus, l.status)"
                        />
                    </div>
                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-4"
                    >
                        <div>
                            <dt class="text-muted-foreground">Due</dt>
                            <dd>
                                {{ formatDate(l.due_on)
                                }}<span
                                    v-if="
                                        l.status === 'expected' ||
                                        l.status === 'planned'
                                    "
                                    class="block text-xs text-muted-foreground"
                                    >{{ countdown(l.due_on) }}</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Home</dt>
                            <dd>{{ formatDate(l.go_home_on) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Price / deposit
                            </dt>
                            <dd>
                                {{ money(l.price) }} / {{ money(l.deposit) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Pups / waitlist
                            </dt>
                            <dd>
                                {{ l.puppies_count }} /
                                {{ l.waitlist_entries_count }}
                            </dd>
                        </div>
                    </dl>
                    <p
                        v-if="!l.is_public"
                        class="text-xs text-muted-foreground"
                    >
                        Hidden from the website
                    </p>
                </Link>
            </div>
        </section>

        <section v-if="archived.length" class="space-y-3">
            <div>
                <h2 class="font-serif text-xl font-semibold">Archived</h2>
                <p class="text-sm text-muted-foreground">
                    Hidden from your lists and the website. Their records are
                    kept for AKC.
                </p>
            </div>
            <ul class="divide-y rounded-xl border bg-card">
                <li
                    v-for="l in archived"
                    :key="l.id"
                    class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"
                >
                    <Link
                        :href="edit(l.slug)"
                        class="font-medium hover:underline"
                        >{{ l.title }}</Link
                    >
                    <span class="flex items-center gap-3">
                        <span
                            v-if="l.born_on"
                            class="text-xs text-muted-foreground"
                            >born {{ formatDate(l.born_on) }}</span
                        >
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="router.post(restore.url(l.slug))"
                            >Bring back</Button
                        >
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
