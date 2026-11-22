<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { countdown, formatDate, labelFor } from '@/lib/format';
import { dashboard } from '@/routes';
import { edit as editContact } from '@/routes/admin/contacts';
import {
    create as newLitter,
    edit as editLitter,
} from '@/routes/admin/litters';
import { create as newWaitlist } from '@/routes/admin/waitlist';
import type { Litter, Options, WaitlistEntry } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

defineProps<{
    stats: {
        dogs: number;
        contacts: number;
        waiting: number;
        available: number;
    };
    upcoming: Litter[];
    onGround: Litter[];
    soon: { kind: string; date: string; title: string; slug: string }[];
    needsFollowUp: WaitlistEntry[];
    recentNotes: {
        id: number;
        body: string;
        noted_at: string;
        contact: { id: number; name: string };
    }[];
}>();

const options = computed(() => usePage().props.options as Options);
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8 p-4 md:p-6">
        <PageHeader
            title="Good to see you"
            description="Here is what is happening at the kennel."
        >
            <Button as-child
                ><Link :href="newLitter()">New litter</Link></Button
            >
            <Button as-child variant="outline"
                ><Link :href="newWaitlist()">Add to waitlist</Link></Button
            >
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div
                v-for="s in [
                    { label: 'Dogs', value: stats.dogs },
                    { label: 'On the waitlist', value: stats.waiting },
                    { label: 'Pups available', value: stats.available },
                    { label: 'Contacts', value: stats.contacts },
                ]"
                :key="s.label"
                class="rounded-xl border bg-card p-4"
            >
                <p class="font-serif text-3xl font-semibold">{{ s.value }}</p>
                <p class="text-sm text-muted-foreground">{{ s.label }}</p>
            </div>
        </div>

        <section v-if="soon.length" class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">
                Coming up in the next three weeks
            </h2>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="(r, i) in soon" :key="i">
                    <Link
                        :href="editLitter(r.slug)"
                        class="flex items-center justify-between gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 transition-colors hover:bg-amber-500/15"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold">
                                {{ r.kind }} · {{ formatDate(r.date, false) }}
                                <span class="font-normal text-muted-foreground"
                                    >({{ countdown(r.date) }})</span
                                >
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ r.title }}
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3">
                <h2 class="font-serif text-xl font-semibold">
                    Upcoming litters
                </h2>
                <p
                    v-if="!upcoming.length"
                    class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
                >
                    No planned or expected litters.
                </p>
                <Link
                    v-for="l in upcoming"
                    :key="l.id"
                    :href="editLitter(l.slug)"
                    class="flex items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ l.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            Due {{ formatDate(l.due_on) }}
                            <span v-if="countdown(l.due_on)"
                                >· {{ countdown(l.due_on) }}</span
                            >
                        </p>
                    </div>
                    <StatusBadge
                        :value="l.status"
                        :label="labelFor(options.litterStatus, l.status)"
                    />
                </Link>
            </section>

            <section class="space-y-3">
                <h2 class="font-serif text-xl font-semibold">On the ground</h2>
                <p
                    v-if="!onGround.length"
                    class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
                >
                    No litters here right now.
                </p>
                <Link
                    v-for="l in onGround"
                    :key="l.id"
                    :href="editLitter(l.slug)"
                    class="flex items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ l.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            Born {{ formatDate(l.born_on) }} ·
                            {{ l.puppies_count }} pups · home
                            {{ formatDate(l.go_home_on) }}
                            <span v-if="countdown(l.go_home_on)"
                                >({{ countdown(l.go_home_on) }})</span
                            >
                        </p>
                    </div>
                </Link>
            </section>

            <section class="space-y-3">
                <h2 class="font-serif text-xl font-semibold">
                    Waiting to hear from you
                </h2>
                <p
                    v-if="!needsFollowUp.length"
                    class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
                >
                    Nobody is waiting on a callback.
                </p>
                <Link
                    v-for="e in needsFollowUp"
                    :key="e.id"
                    :href="editContact(e.contact_id)"
                    class="block rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <p class="font-medium">{{ e.contact?.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{
                            e.contact?.phone ??
                            e.contact?.email ??
                            'No contact info'
                        }}
                        <span v-if="e.litter">· {{ e.litter.title }}</span>
                    </p>
                </Link>
            </section>

            <section class="space-y-3">
                <h2 class="font-serif text-xl font-semibold">Recent notes</h2>
                <p
                    v-if="!recentNotes.length"
                    class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground"
                >
                    Notes you add to contacts show up here.
                </p>
                <Link
                    v-for="n in recentNotes"
                    :key="n.id"
                    :href="editContact(n.contact.id)"
                    class="block rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <p class="text-sm font-medium">{{ n.contact.name }}</p>
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ n.body }}
                    </p>
                </Link>
            </section>
        </div>
    </div>
</template>
