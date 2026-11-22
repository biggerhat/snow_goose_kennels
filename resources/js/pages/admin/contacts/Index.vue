<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Mail, Phone } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { labelFor } from '@/lib/format';
import { create, edit, index } from '@/routes/admin/contacts';
import type { Contact, Options } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Contacts', href: index() }] },
});
const props = defineProps<{
    contacts: Contact[];
    filters: { q: string; type: string | null };
}>();
const options = computed(() => usePage().props.options as Options);

const q = ref(props.filters.q ?? '');
const type = ref<string | null>(props.filters.type ?? null);
let timer: ReturnType<typeof setTimeout>;

watch([q, type], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            index.url(),
            { q: q.value || undefined, type: type.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 250);
});
</script>

<template>
    <Head title="Contacts" />

    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            title="Contacts"
            description="Buyers, vets, stud owners and everyone else."
            :help="[
                'Your address book. Search by name, phone, email or city.',
                'Tap a person to see their details and the log of everything you have talked about.',
                'Nothing in Contacts is ever shown on the website.',
            ]"
            help-title="Your contacts"
        >
            <Button as-child><Link :href="create()">Add contact</Link></Button>
        </PageHeader>

        <div class="flex flex-wrap gap-3">
            <Input
                v-model="q"
                type="search"
                placeholder="Search name, phone, email or city…"
                aria-label="Search contacts"
                class="max-w-sm"
            />
            <div class="w-48">
                <NativeSelect
                    v-model="type"
                    :options="options.contactType"
                    placeholder="All types"
                    aria-label="Filter by type"
                />
            </div>
        </div>

        <EmptyState
            v-if="!contacts.length"
            :title="q || type ? 'Nobody matches that' : 'No contacts yet'"
            :description="
                q || type
                    ? 'Try a shorter search, or clear the type filter.'
                    : 'Add buyers and everyone you work with so their info is in one place.'
            "
        >
            <Button as-child
                ><Link :href="create()">Add a contact</Link></Button
            >
        </EmptyState>

        <ul v-else class="divide-y rounded-xl border bg-card">
            <li v-for="c in contacts" :key="c.id">
                <Link
                    :href="edit(c.id)"
                    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 p-4 transition-colors hover:bg-muted/50"
                >
                    <div class="min-w-0">
                        <p class="font-medium">{{ c.name }}</p>
                        <p
                            class="flex flex-wrap gap-x-4 text-sm text-muted-foreground"
                        >
                            <span
                                v-if="c.phone"
                                class="inline-flex items-center gap-1"
                                ><Phone class="size-3.5" />{{ c.phone }}</span
                            >
                            <span
                                v-if="c.email"
                                class="inline-flex items-center gap-1"
                                ><Mail class="size-3.5" />{{ c.email }}</span
                            >
                            <span v-if="c.city">{{
                                [c.city, c.state].filter(Boolean).join(', ')
                            }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="c.waitlist_entries_count"
                            class="text-xs text-muted-foreground"
                            >{{ c.waitlist_entries_count }} on waitlist</span
                        >
                        <StatusBadge
                            :value="c.type"
                            :label="labelFor(options.contactType, c.type)"
                        />
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
