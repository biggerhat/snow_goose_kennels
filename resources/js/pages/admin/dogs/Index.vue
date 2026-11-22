<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Dog as DogIcon } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import { formatDate } from '@/lib/format';
import { restore } from '@/routes/admin/dogs';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { labelFor } from '@/lib/format';
import { router } from '@inertiajs/vue3';
import { create, edit, index } from '@/routes/admin/dogs';
import type { Dog, Options } from '@/types';

defineOptions({ layout: { breadcrumbs: [{ title: 'Dogs', href: index() }] } });
const props = defineProps<{
    dogs: Dog[];
    archived: Dog[];
}>();
const options = computed(() => usePage().props.options as Options);

const groups = computed(() =>
    [
        {
            title: 'Our dogs',
            note: '',
            items: props.dogs.filter((d) => d.is_ours !== false && !d.left_on),
        },
        {
            title: 'Dogs from other kennels',
            note: 'Sires and dams that belong to someone else. They are never shown on your website’s Our Dogs page.',
            items: props.dogs.filter((d) => d.is_ours === false),
        },
        {
            title: 'No longer here',
            note: 'Dogs that were sold, given away or passed away. Keep them for your breeding records.',
            items: props.dogs.filter((d) => d.is_ours !== false && d.left_on),
        },
    ].filter((g) => g.items.length),
);
</script>

<template>
    <Head title="Dogs" />

    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            title="Dogs"
            description="Your studs, dams and retired family members."
            :help="[
                'Every dog you keep or breed is listed here. Tap a dog to see or change its details, photos and health tests.',
                'A dam is a mother and a stud is a father. Retired dogs are no longer bred.',
                'Dogs that are not shown on the website are marked Private.',
                'A stud or mother that belongs to someone else, or a dog that was sold or passed away, is kept here too so your breeding records stay complete. They are listed in their own groups below.',
            ]"
            help-title="Your dogs"
        >
            <Button as-child><Link :href="create()">Add a dog</Link></Button>
            <Button as-child variant="outline"
                ><Link :href="create({ query: { outside: 1 } })"
                    >Add a dog from another kennel</Link
                ></Button
            >
        </PageHeader>

        <EmptyState
            v-if="!dogs.length"
            title="No dogs yet"
            description="Add each dog with a photo, a name and some info."
        >
            <Button as-child
                ><Link :href="create()">Add your first dog</Link></Button
            >
        </EmptyState>

        <section v-for="g in groups" v-else :key="g.title" class="space-y-3">
            <div>
                <h2 class="font-serif text-xl font-semibold">{{ g.title }}</h2>
                <p v-if="g.note" class="text-sm text-muted-foreground">
                    {{ g.note }}
                </p>
            </div>
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <li v-for="dog in g.items" :key="dog.id">
                    <Link
                        :href="edit(dog.slug)"
                        class="flex gap-4 rounded-xl border bg-card p-3 transition-colors hover:bg-muted/50"
                    >
                        <div
                            class="size-24 shrink-0 overflow-hidden rounded-lg bg-muted"
                        >
                            <img
                                v-if="dog.photos?.[0]"
                                :src="dog.photos[0].thumb_url"
                                :alt="dog.name"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center text-muted-foreground"
                            >
                                <DogIcon class="size-8" />
                            </div>
                        </div>
                        <div class="min-w-0 space-y-1.5">
                            <p
                                class="truncate font-serif text-lg font-semibold"
                            >
                                {{ dog.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    [
                                        labelFor(options.color, dog.color),
                                        labelFor(options.sex, dog.sex),
                                        dog.age_label,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <StatusBadge
                                    :value="dog.role"
                                    :label="labelFor(options.role, dog.role)"
                                />
                                <StatusBadge
                                    v-if="
                                        dog.is_ours !== false && !dog.is_public
                                    "
                                    value="hidden"
                                    label="Hidden from site"
                                />
                                <StatusBadge
                                    v-if="
                                        dog.is_ours === false &&
                                        dog.owner_contact
                                    "
                                    value="other"
                                    :label="`Owner: ${dog.owner_contact.name}`"
                                />
                            </div>
                        </div>
                    </Link>
                </li>
            </ul>
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
                    v-for="d in archived"
                    :key="d.id"
                    class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"
                >
                    <Link
                        :href="edit(d.slug)"
                        class="font-medium hover:underline"
                        >{{ d.name }}</Link
                    >
                    <span class="flex items-center gap-3">
                        <span
                            v-if="d.left_on"
                            class="text-xs text-muted-foreground"
                            >left {{ formatDate(d.left_on) }}</span
                        >
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="router.post(restore.url(d.slug))"
                            >Bring back</Button
                        >
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
