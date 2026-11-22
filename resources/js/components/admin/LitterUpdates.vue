<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import Field from '@/components/admin/Field.vue';
import TextArea from '@/components/admin/TextArea.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import { store } from '@/routes/admin/litter-messages';
import type { LitterMessage, WaitlistEntry } from '@/types';

const props = defineProps<{
    slug: string;
    litterTitle: string;
    entries: WaitlistEntry[];
    messages: LitterMessage[];
}>();

// Only people who are still waiting and have an email address can be written to.
const people = computed(() =>
    props.entries.filter(
        (e) => !['placed', 'passed'].includes(e.status) && e.contact?.email,
    ),
);
const without = computed(
    () =>
        props.entries.filter(
            (e) =>
                !['placed', 'passed'].includes(e.status) && !e.contact?.email,
        ).length,
);

const form = useForm({
    subject: '',
    body: '',
    entry_ids: people.value.map((e) => e.id),
});
const confirm = ref(false);

const chosen = computed(() => form.entry_ids.length);
const send = () =>
    form.post(store.url(props.slug), {
        preserveScroll: true,
        onSuccess: () => form.reset('subject', 'body'),
    });

function toggle(id: number, on: boolean) {
    form.entry_ids = on
        ? [...form.entry_ids, id]
        : form.entry_ids.filter((x) => x !== id);
}
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-muted-foreground">
            Send the same news to everyone waiting for {{ litterTitle }}. Each
            person gets their own email, so nobody sees anyone else’s address.
            Replies come to your email.
        </p>

        <p v-if="!people.length" class="rounded-lg border p-3 text-sm">
            Nobody with an email address is waiting for this litter yet.
        </p>

        <form v-else class="space-y-4" @submit.prevent="confirm = true">
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">Send to</legend>
                <ul class="divide-y rounded-lg border">
                    <li v-for="e in people" :key="e.id">
                        <label
                            class="flex min-h-11 cursor-pointer items-center gap-3 px-3 py-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                class="size-5 accent-primary"
                                :checked="form.entry_ids.includes(e.id)"
                                @change="
                                    toggle(
                                        e.id,
                                        ($event.target as HTMLInputElement)
                                            .checked,
                                    )
                                "
                            />
                            <span class="min-w-0">
                                <span class="font-medium">{{
                                    e.contact?.name
                                }}</span>
                                <span
                                    class="block truncate text-muted-foreground"
                                    >{{ e.contact?.email }}</span
                                >
                            </span>
                        </label>
                    </li>
                </ul>
                <p v-if="without" class="text-xs text-muted-foreground">
                    {{ without }} waiting
                    {{ without === 1 ? 'person has' : 'people have' }} no email
                    address and will not get this. Call or text them.
                </p>
                <InputError :message="form.errors.entry_ids" />
            </fieldset>

            <Field
                label="Subject"
                required
                for="lu-subject"
                :error="form.errors.subject"
            >
                <Input
                    id="lu-subject"
                    v-model="form.subject"
                    placeholder="The puppies are here!"
                />
            </Field>
            <Field
                label="Your message"
                required
                for="lu-body"
                :error="form.errors.body"
                hint="It starts with “Hi” and their first name, and ends with your name and phone number."
            >
                <TextArea id="lu-body" v-model="form.body" :rows="6" />
            </Field>
            <Button type="submit" :disabled="form.processing || !chosen">{{
                form.processing
                    ? 'Sending…'
                    : `Send to ${chosen} ${chosen === 1 ? 'person' : 'people'}`
            }}</Button>
        </form>

        <ConfirmDialog
            v-model:open="confirm"
            title="Send this email?"
            :message="`This sends “${form.subject || 'your message'}” to ${chosen} ${chosen === 1 ? 'person' : 'people'}. You cannot take an email back once it is sent.`"
            confirm-label="Yes, send it"
            :destructive="false"
            @confirm="send"
        />

        <details v-if="messages.length" class="rounded-lg border p-3 text-sm">
            <summary class="cursor-pointer font-medium">
                Sent so far ({{ messages.length }})
            </summary>
            <ul class="mt-2 divide-y">
                <li v-for="m in messages" :key="m.id" class="space-y-1 py-2">
                    <p class="font-medium">
                        {{ m.subject }}
                        <span class="font-normal text-muted-foreground"
                            >· {{ formatDate(m.sent_at, false) }} · sent to
                            {{ m.recipient_count }}</span
                        >
                    </p>
                    <p class="whitespace-pre-line text-muted-foreground">
                        {{ m.body }}
                    </p>
                </li>
            </ul>
        </details>
    </div>
</template>
