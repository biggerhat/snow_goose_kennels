<script setup lang="ts">
import { ExternalLink, Mail, MapPin, Phone } from '@lucide/vue';

defineProps<{
    contact: {
        phone?: string | null;
        email?: string | null;
        location?: string | null;
        facebook?: string | null;
    };
}>();
</script>

<template>
    <ul class="space-y-2 text-lg">
        <li v-if="contact.phone">
            <a
                :href="`tel:${contact.phone}`"
                class="inline-flex min-h-11 items-center gap-3 hover:underline"
                ><Phone class="size-5" />{{ contact.phone }}</a
            >
        </li>
        <li v-if="contact.email">
            <a
                :href="`mailto:${contact.email}`"
                class="inline-flex min-h-11 items-center gap-3 hover:underline"
                ><Mail class="size-5" />{{ contact.email }}</a
            >
        </li>
        <li v-if="contact.location" class="flex min-h-11 items-center gap-3">
            <MapPin class="size-5" />{{ contact.location }}
        </li>
        <li v-if="contact.facebook">
            <a
                :href="contact.facebook"
                target="_blank"
                rel="noopener"
                class="inline-flex min-h-11 items-center gap-3 hover:underline"
                ><ExternalLink class="size-5" />Find us on Facebook</a
            >
        </li>
    </ul>
</template>
