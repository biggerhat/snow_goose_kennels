<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Printer } from '@lucide/vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { exportMethod, records } from '@/routes/admin';
import {
    all as printAll,
    dog as printDog,
    litter as printLitter,
} from '@/routes/admin/records';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Records', href: records() }] },
});

defineProps<{
    dogs: {
        id: number;
        slug: string;
        name: string;
        registry_number: string | null;
        dna_profile: string | null;
        microchip: string | null;
        birth_date: string | null;
        acquired_on: string | null;
        left_on: string | null;
        deleted_at?: string | null;
        is_ours: boolean;
        owner_contact?: { name: string } | null;
    }[];
    litters: {
        id: number;
        slug: string;
        title: string;
        status: string;
        born_on: string | null;
        due_on: string | null;
        akc_litter_number: string | null;
        akc_applied_on: string | null;
        puppies_count: number;
    }[];
}>();

const sheets = [
    ['dogs', 'Dogs'],
    ['litters', 'Litters'],
    ['puppies', 'Puppies and buyers'],
    ['health', 'Vaccines and vet records'],
    ['documents', 'Document list'],
    ['contacts', 'Contacts'],
    ['waitlist', 'Waitlist'],
] as const;
</script>

<template>
    <Head title="Records" />

    <div class="max-w-5xl space-y-8 p-4 md:p-6">
        <PageHeader
            title="Records"
            description="Printable breeding and ownership records, ready if AKC ever asks to see them."
            :help="[
                'AKC asks breeders to keep an ownership record for each adult dog and a record for each litter, and to keep them for 5 years after a dog dies, is sold or is given away.',
                'You do not need to write these. They are built from what you have already entered on the Dogs and Litters pages, so the more you fill in there, the more complete they are.',
                'Tap Print under a dog or litter to open a clean page, then use Print or Save as PDF. Print everything makes one big file with every dog and litter.',
                'The spreadsheets are extra copies you can open in Excel or Google Sheets. They do not include photos or documents, only their details.',
            ]"
            help-title="Your record book"
        >
            <Button as-child>
                <a :href="printAll.url()" target="_blank" rel="noopener">
                    <Printer class="size-4" /> Print everything
                </a>
            </Button>
        </PageHeader>

        <section class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">Dog records</h2>
            <p class="text-sm text-muted-foreground">
                Identification, ownership, matings, health tests and vaccines
                for each dog.
            </p>
            <div class="divide-y rounded-xl border bg-card">
                <div
                    v-for="d in dogs"
                    :key="d.id"
                    class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"
                >
                    <div class="min-w-0">
                        <Link
                            :href="`/admin/dogs/${d.slug}/edit`"
                            class="font-medium hover:underline"
                            >{{ d.name }}</Link
                        >
                        <span v-if="!d.is_ours" class="text-muted-foreground">
                            · another kennel<template v-if="d.owner_contact">
                                ({{ d.owner_contact.name }})</template
                            ></span
                        >
                        <span v-if="d.left_on" class="text-muted-foreground">
                            · left {{ formatDate(d.left_on) }}</span
                        >
                        <span v-if="d.deleted_at" class="text-muted-foreground">
                            · archived</span
                        >
                        <p class="text-xs text-muted-foreground">
                            {{ d.registry_number ?? 'no registration number' }}
                            <template v-if="d.dna_profile">
                                · DNA {{ d.dna_profile }}</template
                            >
                            <template v-if="d.microchip">
                                · chip {{ d.microchip }}</template
                            >
                        </p>
                    </div>
                    <Button as-child size="sm" variant="outline">
                        <a
                            :href="printDog.url(d.slug)"
                            target="_blank"
                            rel="noopener"
                            ><Printer class="size-4" /> Print</a
                        >
                    </Button>
                </div>
                <p
                    v-if="!dogs.length"
                    class="p-4 text-sm text-muted-foreground"
                >
                    No dogs yet.
                </p>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="font-serif text-xl font-semibold">Litter records</h2>
            <p class="text-sm text-muted-foreground">
                The mating, whelping, puppies, buyers and AKC details for each
                litter.
            </p>
            <div class="divide-y rounded-xl border bg-card">
                <div
                    v-for="l in litters"
                    :key="l.id"
                    class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm"
                >
                    <div class="min-w-0">
                        <Link
                            :href="`/admin/litters/${l.slug}/edit`"
                            class="font-medium hover:underline"
                            >{{ l.title }}</Link
                        >
                        <p class="text-xs text-muted-foreground">
                            {{
                                l.born_on
                                    ? `Born ${formatDate(l.born_on)}`
                                    : l.due_on
                                      ? `Due ${formatDate(l.due_on)}`
                                      : 'No date yet'
                            }}
                            · {{ l.puppies_count }} puppies
                            <template v-if="l.akc_litter_number">
                                · AKC litter {{ l.akc_litter_number }}</template
                            >
                            <template
                                v-else-if="l.born_on && !l.akc_applied_on"
                            >
                                · AKC application not sent</template
                            >
                        </p>
                    </div>
                    <Button as-child size="sm" variant="outline">
                        <a
                            :href="printLitter.url(l.slug)"
                            target="_blank"
                            rel="noopener"
                            ><Printer class="size-4" /> Print</a
                        >
                    </Button>
                </div>
                <p
                    v-if="!litters.length"
                    class="p-4 text-sm text-muted-foreground"
                >
                    No litters yet.
                </p>
            </div>
        </section>

        <section class="space-y-3 rounded-xl border border-dashed bg-card p-5">
            <h2 class="font-serif text-xl font-semibold">Spreadsheets</h2>
            <p class="text-sm text-muted-foreground">
                Extra copies you can open in Excel or Google Sheets. A good
                thing to save somewhere safe now and then.
            </p>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="[key, label] in sheets"
                    :key="key"
                    as-child
                    size="sm"
                    variant="outline"
                >
                    <a :href="exportMethod.url(key)" download
                        ><Download class="size-4" /> {{ label }}</a
                    >
                </Button>
            </div>
        </section>
    </div>
</template>
