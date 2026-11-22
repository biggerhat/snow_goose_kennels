<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Dog as DogIcon } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { labelFor } from '@/lib/format';
import { create, edit, index } from '@/routes/admin/dogs';
import type { Dog, Options } from '@/types';

defineOptions({ layout: { breadcrumbs: [{ title: 'Dogs', href: index() }] } });
defineProps<{ dogs: Dog[] }>();
const options = computed(() => usePage().props.options as Options);
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
            ]"
            help-title="Your dogs"
        >
            <Button as-child><Link :href="create()">Add a dog</Link></Button>
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

        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="dog in dogs" :key="dog.id">
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
                        <p class="truncate font-serif text-lg font-semibold">
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
                                v-if="!dog.is_public"
                                value="hidden"
                                label="Hidden from site"
                            />
                        </div>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
