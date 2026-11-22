<script setup lang="ts">
import GlobalSearch from '@/components/admin/GlobalSearch.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// On a dog, litter or contact page, add its name as the last crumb.
const page = usePage();
const trail = computed<BreadcrumbItem[]>(() => {
    const p = page.props as Record<string, any>;
    const name: string | undefined =
        p.dog?.name ??
        p.litter?.title ??
        p.contact?.name ??
        p.entry?.contact?.name;

    return name && props.breadcrumbs.length
        ? [...props.breadcrumbs, { title: name, href: page.url }]
        : props.breadcrumbs;
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="trail.length > 0">
                <Breadcrumbs :breadcrumbs="trail" />
            </template>
        </div>
        <GlobalSearch />
    </header>
</template>
