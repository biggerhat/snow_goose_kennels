<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { countdown, formatDate, labelFor } from '@/lib/format';
import { dashboard } from '@/routes';
import { help } from '@/routes/admin';
import { snooze as snoozeUrl } from '@/routes/admin/attention';
import { index as dogsIndex, create as newDog } from '@/routes/admin/dogs';
import { index as contactsIndex } from '@/routes/admin/contacts';
import { index as photosIndex } from '@/routes/admin/photos';
import { index as waitlistIndex } from '@/routes/admin/waitlist';
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

const props = defineProps<{
    setup: {
        key: string;
        title: string;
        hint: string;
        done: boolean;
        href: string;
    }[];
    attention: {
        key: string;
        kind: string;
        title: string;
        detail: string;
        date: string;
        overdue: boolean;
        href: string;
    }[];
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
const doneCount = computed(() => props.setup.filter((s) => s.done).length);
const allDone = computed(() => doneCount.value === props.setup.length);
const showAllAttention = ref(false);

function snooze(key: string) {
    router.post(snoozeUrl.url(), { key, days: 7 }, { preserveScroll: true });
}

const welcomed = ref(true);
onMounted(() => {
    try {
        welcomed.value = localStorage.getItem('welcome-seen') === '1';
    } catch {
        welcomed.value = true;
    }
});
function dismissWelcome() {
    welcomed.value = true;

    try {
        localStorage.setItem('welcome-seen', '1');
    } catch {
        // ignore
    }
}
const shownAttention = computed(() =>
    showAllAttention.value ? props.attention : props.attention.slice(0, 6),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8 p-4 md:p-6">
        <PageHeader
            title="Good to see you"
            description="Here is what is happening at the kennel."
            :help="[
                'This is your home page. It shows what needs you today.',
                'Needs attention lists things that are due or running out: vaccines, papers, steps in a litter’s puppy schedule and AKC deadlines. Tap a card to go to it, or tap Remind me in a week to put it off.',
                'The quick buttons at the top start the things you do most: a new litter, someone on the waitlist, a dog, photos.',
                'Everything else is in the menu: Dogs, Litters, Contacts, Photos, Records, Money and Website text. On a phone, tap More at the bottom.',
                'Stuck? Tap Help in the menu. It has step-by-step answers and explains the words.',
            ]"
            help-title="Your home page"
        >
            <Button as-child
                ><Link :href="newLitter()">New litter</Link></Button
            >
            <Button as-child variant="outline"
                ><Link :href="newWaitlist()">Add to waitlist</Link></Button
            >
        </PageHeader>

        <aside
            v-if="!welcomed"
            class="flex items-start justify-between gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4 text-sm"
        >
            <p>
                <strong>Welcome.</strong> Everything you need is in the menu. On
                any page, tap <em>“How does this page work?”</em> for a short
                explanation, or open
                <Link :href="help()" class="underline">Help</Link>
                for step-by-step answers.
            </p>
            <button
                type="button"
                class="shrink-0 rounded px-2 py-1 text-muted-foreground hover:bg-muted"
                @click="dismissWelcome"
            >
                Got it
            </button>
        </aside>

        <section aria-label="Quick actions" class="flex flex-wrap gap-2">
            <Button as-child variant="outline" size="sm"
                ><Link :href="newDog()">Add a dog</Link></Button
            >
            <Button as-child variant="outline" size="sm"
                ><Link :href="photosIndex()">Add photos</Link></Button
            >
            <Button as-child variant="outline" size="sm"
                ><Link :href="contactsIndex()">Find a contact</Link></Button
            >
        </section>

        <section
            v-if="!allDone"
            class="space-y-3 rounded-xl border border-primary/30 bg-primary/5 p-5"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-serif text-xl font-semibold">
                    Getting started
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ doneCount }} of {{ setup.length }} done. Not sure what
                    something means? Open
                    <Link :href="help()" class="underline">Help</Link>.
                </p>
            </div>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li v-for="s in setup" :key="s.key">
                    <Link
                        :href="s.href"
                        :class="[
                            'flex items-start gap-3 rounded-lg border bg-card p-3 transition-colors hover:bg-muted/50',
                            s.done && 'opacity-60',
                        ]"
                    >
                        <span
                            :class="[
                                'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border text-xs',
                                s.done
                                    ? 'border-emerald-600 bg-emerald-600 text-white'
                                    : 'border-muted-foreground',
                            ]"
                            aria-hidden="true"
                            >{{ s.done ? '✓' : '' }}</span
                        >
                        <span>
                            <span class="block text-sm font-medium">{{
                                s.title
                            }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                s.hint
                            }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <section v-if="attention.length" class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">Needs attention</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li v-for="a in shownAttention" :key="a.key">
                    <div
                        :class="[
                            'rounded-lg border text-sm',
                            a.overdue
                                ? 'border-red-500/40 bg-red-500/5'
                                : 'border-amber-500/40 bg-amber-500/5',
                        ]"
                    >
                        <Link
                            :href="a.href"
                            class="block rounded-t-lg p-3 transition-colors hover:bg-muted/50"
                        >
                            <span class="block font-medium">{{ a.title }}</span>
                            <span class="block text-xs text-muted-foreground"
                                >{{ a.overdue ? 'Overdue · ' : 'Due '
                                }}{{ formatDate(a.date) }} ·
                                {{ a.detail }}</span
                            >
                        </Link>
                        <button
                            type="button"
                            class="min-h-9 w-full border-t px-3 py-1.5 text-left text-xs text-muted-foreground hover:text-foreground"
                            @click="snooze(a.key)"
                        >
                            Remind me in a week
                        </button>
                    </div>
                </li>
            </ul>
            <button
                v-if="attention.length > 6"
                type="button"
                class="text-sm text-primary underline underline-offset-2"
                @click="showAllAttention = !showAllAttention"
            >
                {{
                    showAllAttention
                        ? 'Show fewer'
                        : `Show ${attention.length - 6} more`
                }}
            </button>
        </section>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <Link
                v-for="s in [
                    { label: 'Dogs', value: stats.dogs, href: dogsIndex() },
                    {
                        label: 'On the waitlist',
                        value: stats.waiting,
                        href: waitlistIndex(),
                    },
                    {
                        label: 'Pups available',
                        value: stats.available,
                        href: '/admin/litters',
                    },
                    {
                        label: 'Contacts',
                        value: stats.contacts,
                        href: contactsIndex(),
                    },
                ]"
                :key="s.label"
                :href="s.href"
                class="rounded-xl border bg-card p-4 transition-colors hover:bg-muted/50"
            >
                <p class="font-serif text-3xl font-semibold">{{ s.value }}</p>
                <p class="text-sm text-muted-foreground">{{ s.label }}</p>
            </Link>
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
