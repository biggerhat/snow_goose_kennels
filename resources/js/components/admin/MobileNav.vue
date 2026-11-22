<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Baby, Dog, LayoutGrid, ListChecks, Menu } from '@lucide/vue';
import { computed } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { index as dogs } from '@/routes/admin/dogs';
import { index as litters } from '@/routes/admin/litters';
import { index as waitlist } from '@/routes/admin/waitlist';

/** A thumb-reach bar for phones. Hidden on larger screens, where the sidebar does this job. */
const { setOpenMobile } = useSidebar();
const { isCurrentUrl } = useCurrentUrl();
const nav = computed(
    () =>
        usePage().props.nav as {
            applications: number;
            attention: number;
        } | null,
);

const tabs = computed(() => [
    {
        label: 'Home',
        href: dashboard(),
        icon: LayoutGrid,
        badge: nav.value?.attention,
    },
    { label: 'Litters', href: litters(), icon: Baby },
    { label: 'Dogs', href: dogs(), icon: Dog },
    {
        label: 'Waitlist',
        href: waitlist(),
        icon: ListChecks,
        badge: nav.value?.applications,
    },
]);
</script>

<template>
    <nav
        aria-label="Main"
        class="fixed inset-x-0 bottom-0 z-30 grid h-14 grid-cols-5 border-t bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
    >
        <Link
            v-for="t in tabs"
            :key="t.label"
            :href="t.href"
            :aria-current="isCurrentUrl(t.href) ? 'page' : undefined"
            :class="[
                'relative flex flex-col items-center justify-center gap-0.5 text-[11px]',
                isCurrentUrl(t.href)
                    ? 'font-semibold text-foreground'
                    : 'text-muted-foreground',
            ]"
        >
            <component :is="t.icon" class="size-5" />
            {{ t.label }}
            <span
                v-if="t.badge"
                class="absolute top-1.5 left-1/2 ml-2 min-w-4 rounded-full bg-primary px-1 text-center text-[10px] leading-4 text-primary-foreground"
                >{{ t.badge }}</span
            >
        </Link>
        <button
            type="button"
            class="flex flex-col items-center justify-center gap-0.5 text-[11px] text-muted-foreground"
            @click="setOpenMobile(true)"
        >
            <Menu class="size-5" />
            More
        </button>
    </nav>
</template>
