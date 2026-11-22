<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCopy, Download, ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/admin/litters';
import { litterForm } from '@/routes/admin/akc';

const props = defineProps<{
    litter: { id: number; slug: string; title: string };
    application: {
        fields: Record<string, string>;
        sections: { title: string; rows: [string, string][] }[];
        missing: { text: string; href: string | null }[];
    };
    onlineUrl: string;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Litters', href: '/admin/litters' }] },
});

const working = ref(false);

async function copy(value: string) {
    try {
        await navigator.clipboard.writeText(value);
        toast.success('Copied.');
    } catch {
        toast.error('Could not copy. Select the text and copy it by hand.');
    }
}

/** Fill AKC's own PDF in the browser, so nothing is sent anywhere and no server tools are needed. */
async function download() {
    working.value = true;

    try {
        const [{ PDFDocument }, response] = await Promise.all([
            import('pdf-lib'),
            fetch(litterForm.url(), { credentials: 'same-origin' }),
        ]);

        if (!response.ok) {
            throw new Error(
                response.status === 502
                    ? 'AKC’s website did not answer. Try again in a minute, or use the worksheet below.'
                    : 'The form could not be loaded.',
            );
        }

        const pdf = await PDFDocument.load(await response.arrayBuffer());
        const form = pdf.getForm();
        const skipped: string[] = [];

        for (const [name, value] of Object.entries(props.application.fields)) {
            try {
                form.getTextField(name).setText(value);
            } catch {
                skipped.push(name);
            }
        }

        const bytes = await pdf.save();
        const url = URL.createObjectURL(
            new Blob([bytes as BlobPart], { type: 'application/pdf' }),
        );
        const a = document.createElement('a');
        a.href = url;
        a.download = `AKC litter application - ${props.litter.title}.pdf`;
        a.click();
        URL.revokeObjectURL(url);

        if (skipped.length) {
            toast.warning(
                `Downloaded, but AKC seems to have changed its form: ${skipped.length} box(es) could not be filled. Check it and use the worksheet below for those.`,
            );
        } else {
            toast.success('Filled in. Check every box, print it, and sign it.');
        }
    } catch (e) {
        toast.error(
            e instanceof Error ? e.message : 'The form could not be filled in.',
        );
    } finally {
        working.value = false;
    }
}
</script>

<template>
    <Head title="AKC litter application" />

    <div class="max-w-3xl space-y-6 p-4 md:p-6">
        <PageHeader
            title="AKC litter application"
            :description="`Everything AKC asks for, ready for ${litter.title}.`"
            :help="[
                'This page takes what you have already entered and lines it up with AKC’s Litter Registration Application, so you do not type it all again.',
                'Download filled-in AKC form fills in AKC’s own PDF for you. Check every box, print it, sign it in black ink and mail it with a check or money order. AKC needs the sire’s owner and all owners of the dam to sign.',
                'Or register online at akc.org. Use the copy buttons to paste each answer.',
                'After you send it, go back to the litter and enter the date you sent it. When AKC replies, enter the AKC litter number.',
            ]"
            help-title="The AKC litter application"
            help-topic="Fill in the AKC litter application"
        >
            <Button as-child variant="outline">
                <Link :href="edit(litter.slug)">← Back to the litter</Link>
            </Button>
        </PageHeader>

        <section
            v-if="application.missing.length"
            class="space-y-2 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm"
        >
            <p class="font-medium">A few things are still missing:</p>
            <ul class="list-disc space-y-1 pl-5">
                <li v-for="m in application.missing" :key="m.text">
                    <Link
                        v-if="m.href"
                        :href="m.href"
                        class="underline underline-offset-2"
                        >{{ m.text }}</Link
                    >
                    <template v-else>{{ m.text }}</template>
                </li>
            </ul>
        </section>

        <div class="flex flex-wrap gap-2">
            <Button :disabled="working" @click="download">
                <Download class="size-4" />
                {{
                    working
                        ? 'Filling in the form…'
                        : 'Download filled-in AKC form (PDF)'
                }}
            </Button>
            <Button as-child variant="outline">
                <a :href="onlineUrl" target="_blank" rel="noopener">
                    <ExternalLink class="size-4" /> Register online at akc.org
                </a>
            </Button>
        </div>

        <section
            v-for="s in application.sections"
            :key="s.title"
            class="space-y-2 rounded-xl border bg-card p-5"
        >
            <h2 class="font-serif text-lg font-semibold">{{ s.title }}</h2>
            <dl class="divide-y text-sm">
                <div
                    v-for="[label, value] in s.rows"
                    :key="label"
                    class="flex flex-wrap items-center justify-between gap-2 py-2"
                >
                    <dt class="text-muted-foreground">{{ label }}</dt>
                    <dd class="flex items-center gap-2 font-medium">
                        <span v-if="value">{{ value }}</span>
                        <span v-else class="text-amber-700 dark:text-amber-300"
                            >Missing</span
                        >
                        <button
                            v-if="value"
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                            :aria-label="`Copy ${label}`"
                            @click="copy(value)"
                        >
                            <ClipboardCopy class="size-4" />
                        </button>
                    </dd>
                </div>
            </dl>
        </section>

        <section
            class="space-y-2 rounded-xl border border-dashed bg-card p-5 text-sm"
        >
            <h2 class="font-serif text-lg font-semibold">Before you send it</h2>
            <ul class="list-disc space-y-1.5 pl-5">
                <li>
                    The fees are not filled in. The form lists them, including a
                    late fee if the puppies are more than 6 months old. They
                    change, so use what the form says.
                </li>
                <li>
                    Use black ink. The sire’s owner signs for the date of
                    mating, and every owner or lessee of the dam signs.
                </li>
                <li>
                    If ownership of the dam changed, or a lease ended, while she
                    was in whelp, the form has an extra section for that.
                </li>
                <li>
                    AKC sends the litter kit, with an application for each
                    puppy, to the dam owner’s mailing address.
                </li>
                <li>
                    This is a convenience, not advice. If you are unsure about
                    anything on the form, check akc.org or call AKC.
                </li>
            </ul>
        </section>
    </div>
</template>
