<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatDate, labelFor } from '@/lib/format';
import type { Options } from '@/types';

const props = defineProps<{
    dog: {
        slug: string;
        name: string;
        registered_name: string | null;
        sex: string;
        role: string;
        color: string | null;
        birth_date: string | null;
        age_label: string | null;
        bio: string | null;
        photos: {
            id: number;
            url: string;
            thumb_url: string;
            caption: string | null;
            credit: string | null;
        }[];
        health_tests: {
            name: string;
            result: string;
            tested_on: string | null;
            certificate_number: string | null;
            verification_url: string | null;
        }[];
    };
}>();
const options = computed(() => usePage().props.options as Options);
const active = ref(0);
const photo = computed(() => props.dog.photos[active.value]);
</script>

<template>
    <Head :title="dog.name">
        <meta
            name="description"
            :content="`${dog.name}${dog.registered_name ? ' (' + dog.registered_name + ')' : ''}, a ${labelFor(options.color, dog.color).toLowerCase()} Labrador retriever at Snow Goose Kennels.`"
        />
    </Head>

    <div class="mx-auto max-w-5xl space-y-8 px-5 pt-10">
        <Link
            href="/our-dogs"
            class="text-sm text-muted-foreground underline-offset-4 hover:underline"
            >← All dogs</Link
        >

        <div class="grid gap-10 md:grid-cols-2">
            <div class="space-y-3">
                <div class="photo-frame">
                    <div class="aspect-[4/5] overflow-hidden bg-muted">
                        <img
                            v-if="photo"
                            :src="photo.url"
                            :alt="photo.caption ?? dog.name"
                            class="size-full object-cover"
                        />
                    </div>
                    <p
                        v-if="photo?.caption || photo?.credit"
                        class="mt-2 text-center text-xs text-muted-foreground"
                    >
                        {{ photo.caption
                        }}<span v-if="photo.credit"> · {{ photo.credit }}</span>
                    </p>
                </div>
                <div v-if="dog.photos.length > 1" class="flex flex-wrap gap-2">
                    <button
                        v-for="(p, i) in dog.photos"
                        :key="p.id"
                        type="button"
                        :class="[
                            'size-16 overflow-hidden rounded ring-offset-2 ring-offset-background',
                            i === active
                                ? 'ring-2 ring-foreground'
                                : 'opacity-70 hover:opacity-100',
                        ]"
                        @click="active = i"
                    >
                        <img
                            :src="p.thumb_url"
                            :alt="''"
                            class="size-full object-cover"
                        />
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                <header class="space-y-1">
                    <p
                        class="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase"
                    >
                        {{ labelFor(options.role, dog.role) }}
                    </p>
                    <h1
                        class="font-serif text-5xl font-semibold tracking-tight"
                    >
                        {{ dog.name }}
                    </h1>
                    <p
                        v-if="dog.registered_name"
                        class="text-muted-foreground italic"
                    >
                        {{ dog.registered_name }}
                    </p>
                </header>

                <dl class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Colour</dt>
                        <dd class="font-medium">
                            {{ labelFor(options.color, dog.color) || '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Sex</dt>
                        <dd class="font-medium">
                            {{ labelFor(options.sex, dog.sex) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Born</dt>
                        <dd class="font-medium">
                            {{ formatDate(dog.birth_date) }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="dog.bio"
                    class="text-lg leading-relaxed whitespace-pre-line"
                >
                    {{ dog.bio }}
                </p>

                <section v-if="dog.health_tests.length" class="space-y-2">
                    <h2 class="font-serif text-xl font-semibold">
                        Health clearances
                    </h2>
                    <ul class="divide-y rounded-lg border bg-card">
                        <li
                            v-for="t in dog.health_tests"
                            :key="t.name"
                            class="flex justify-between gap-4 px-4 py-2 text-sm"
                        >
                            <span class="font-medium">{{ t.name }}</span>
                            <span class="text-right"
                                >{{ t.result
                                }}<span
                                    v-if="t.tested_on"
                                    class="text-muted-foreground"
                                >
                                    · {{ formatDate(t.tested_on) }}</span
                                >
                                <span
                                    v-if="
                                        t.certificate_number ||
                                        t.verification_url
                                    "
                                    class="block text-xs text-muted-foreground"
                                >
                                    <template v-if="t.certificate_number"
                                        >#{{ t.certificate_number }}</template
                                    >
                                    <a
                                        v-if="t.verification_url"
                                        :href="t.verification_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="ml-2 underline"
                                        >Verify</a
                                    >
                                </span></span
                            >
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</template>
