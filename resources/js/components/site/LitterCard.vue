<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { countdown, formatDate, labelFor, money } from '@/lib/format';
import type { Options } from '@/types';

type Parent = {
    name: string;
    slug: string | null;
    color: string | null;
    photo: { thumb_url: string } | null;
} | null;
type PublicLitter = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    due_on: string | null;
    born_on: string | null;
    go_home_on: string | null;
    updated_on?: string | null;
    price: number | null;
    deposit: number | null;
    expected_count: number | null;
    sire: Parent;
    dam: Parent;
    puppies: {
        id: number;
        name: string | null;
        collar: string | null;
        sex: string;
        color: string | null;
        status: string;
        photo?: { thumb_url: string } | null;
    }[];
    diary?: {
        id: number;
        posted_on: string;
        title: string | null;
        body: string;
    }[];
    photos: {
        id: number;
        thumb_url: string;
        url: string;
        caption: string | null;
        credit: string | null;
    }[];
};

const props = defineProps<{ litter: PublicLitter; contactHref?: string }>();
const options = computed(() => usePage().props.options as Options);

const heading = computed(() => {
    const l = props.litter;

    if (l.status === 'on_ground' || l.status === 'all_home') {
        return { kicker: 'Born', date: l.born_on };
    }

    return {
        kicker: l.status === 'expected' ? 'Due' : 'Planned for',
        date: l.due_on,
    };
});

const when = computed(() =>
    props.litter.status === 'all_home' ? null : countdown(heading.value.date),
);
const statusLabel = computed(() =>
    labelFor(options.value.litterStatus, props.litter.status),
);

const available = computed(
    () => props.litter.puppies.filter((p) => p.status === 'available').length,
);
</script>

<template>
    <article class="photo-frame relative overflow-hidden !p-0">
        <div class="h-1.5 bg-primary" />
        <div class="space-y-5 p-5 sm:p-7">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                    >
                        {{ statusLabel }}
                    </p>
                    <h3
                        class="font-serif text-2xl leading-tight font-semibold sm:text-3xl"
                    >
                        {{ litter.title }}
                    </h3>
                </div>
                <div class="text-right">
                    <p
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        {{ heading.kicker }}
                    </p>
                    <p class="font-serif text-xl font-semibold">
                        {{ formatDate(heading.date) }}
                    </p>
                    <p v-if="when" class="text-sm text-muted-foreground">
                        {{ when }}
                    </p>
                </div>
            </header>

            <div
                v-if="litter.dam || litter.sire"
                class="flex flex-wrap items-center gap-x-6 gap-y-3"
            >
                <template
                    v-for="(parent, i) in [litter.dam, litter.sire]"
                    :key="i"
                >
                    <component
                        :is="parent?.slug ? 'a' : 'div'"
                        v-if="parent"
                        :href="
                            parent.slug ? `/our-dogs/${parent.slug}` : undefined
                        "
                        class="flex items-center gap-3"
                    >
                        <div
                            class="size-14 overflow-hidden rounded-full bg-muted ring-2 ring-border"
                        >
                            <img
                                v-if="parent.photo"
                                :src="parent.photo.thumb_url"
                                :alt="parent.name"
                                class="size-full object-cover"
                            />
                        </div>
                        <div>
                            <p
                                class="text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                {{ i === 0 ? 'Dam' : 'Sire' }}
                            </p>
                            <p
                                class="font-serif text-lg leading-tight font-semibold"
                            >
                                {{ parent.name }}
                            </p>
                        </div>
                    </component>
                    <span
                        v-if="i === 0 && litter.dam && litter.sire"
                        class="font-serif text-2xl text-muted-foreground"
                        >×</span
                    >
                </template>
            </div>

            <p
                v-if="litter.description"
                class="max-w-prose whitespace-pre-line text-muted-foreground"
            >
                {{ litter.description }}
            </p>

            <div class="flex flex-wrap gap-3 text-sm">
                <dl class="contents">
                    <div
                        v-if="litter.price"
                        class="rounded-full bg-secondary px-4 py-1.5"
                    >
                        <dt class="mr-1.5 inline text-muted-foreground">
                            Price
                        </dt>
                        <dd class="inline font-semibold">
                            {{ money(litter.price) }}
                        </dd>
                    </div>
                    <div
                        v-if="litter.deposit"
                        class="rounded-full bg-secondary px-4 py-1.5"
                    >
                        <dt class="mr-1.5 inline text-muted-foreground">
                            Deposit
                        </dt>
                        <dd class="inline font-semibold">
                            {{ money(litter.deposit) }}
                        </dd>
                    </div>
                    <div
                        v-if="litter.go_home_on"
                        class="rounded-full bg-secondary px-4 py-1.5"
                    >
                        <dt class="mr-1.5 inline text-muted-foreground">
                            Home
                        </dt>
                        <dd class="inline font-semibold">
                            {{ formatDate(litter.go_home_on) }}
                        </dd>
                    </div>
                    <div
                        v-if="litter.expected_count && !litter.puppies.length"
                        class="rounded-full bg-secondary px-4 py-1.5"
                    >
                        <dt class="mr-1.5 inline text-muted-foreground">
                            Expecting
                        </dt>
                        <dd class="inline font-semibold">
                            ~{{ litter.expected_count }} pups
                        </dd>
                    </div>
                </dl>
                <p
                    v-if="
                        litter.puppies.length && litter.status === 'on_ground'
                    "
                    class="rounded-full bg-accent px-4 py-1.5 font-semibold"
                >
                    {{ available }} available
                </p>
            </div>

            <ul
                v-if="litter.puppies.length && litter.status === 'on_ground'"
                class="grid gap-2 sm:grid-cols-2"
            >
                <li
                    v-for="p in litter.puppies"
                    :key="p.id"
                    class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm"
                >
                    <span class="flex items-center gap-2">
                        <img
                            v-if="p.photo"
                            :src="p.photo.thumb_url"
                            :alt="p.name || 'Puppy'"
                            class="size-10 rounded-md object-cover"
                            loading="lazy"
                        />
                        <span
                            v-if="p.collar"
                            class="size-3.5 rounded-full ring-1 ring-black/20"
                            :style="{ backgroundColor: p.collar.toLowerCase() }"
                            :title="`${p.collar} collar`"
                        />
                        <span class="font-medium">{{
                            p.name ||
                            (p.collar ? `${p.collar} collar` : 'Puppy')
                        }}</span>
                        <span class="text-muted-foreground"
                            >{{ labelFor(options.sex, p.sex)
                            }}<template v-if="p.color"
                                >,
                                {{ labelFor(options.color, p.color) }}</template
                            ></span
                        >
                    </span>
                    <span
                        :class="[
                            'text-xs font-semibold',
                            p.status === 'available'
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-muted-foreground',
                        ]"
                        >{{ labelFor(options.puppyStatus, p.status) }}</span
                    >
                </li>
            </ul>

            <section
                v-if="litter.diary?.length"
                class="space-y-3 border-t pt-4"
            >
                <h4
                    class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                >
                    From the whelping box
                </h4>
                <ol class="space-y-3">
                    <li
                        v-for="d in litter.diary.slice(0, 3)"
                        :key="d.id"
                        class="space-y-0.5"
                    >
                        <p class="text-sm">
                            <span class="font-semibold">{{
                                formatDate(d.posted_on, false)
                            }}</span>
                            <span v-if="d.title" class="font-semibold">
                                · {{ d.title }}</span
                            >
                        </p>
                        <p class="whitespace-pre-line text-muted-foreground">
                            {{ d.body }}
                        </p>
                    </li>
                </ol>
            </section>

            <div
                v-if="litter.photos.length"
                class="flex gap-2 overflow-x-auto pb-1"
                role="region"
                tabindex="0"
                aria-label="Photos of this litter"
            >
                <img
                    v-for="ph in litter.photos"
                    :key="ph.id"
                    :src="ph.thumb_url"
                    :alt="ph.caption ?? litter.title"
                    class="h-28 w-auto rounded object-cover"
                    loading="lazy"
                />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div
                    v-if="litter.status !== 'all_home'"
                    class="flex flex-wrap gap-x-5 gap-y-1"
                >
                    <a
                        :href="`/apply?litter=${litter.id}`"
                        class="inline-flex min-h-11 items-center rounded-full bg-primary px-5 font-medium text-primary-foreground hover:opacity-90"
                        >Apply for this litter</a
                    >
                    <a
                        v-if="contactHref"
                        :href="contactHref"
                        class="inline-flex min-h-11 items-center text-muted-foreground underline underline-offset-4"
                        >Ask a question</a
                    >
                </div>
                <p
                    v-if="litter.updated_on"
                    class="text-xs text-muted-foreground"
                >
                    Updated {{ formatDate(litter.updated_on) }}
                </p>
            </div>
        </div>
    </article>
</template>
