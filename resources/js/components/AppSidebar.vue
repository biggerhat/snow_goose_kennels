<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Baby,
    BookUser,
    Dog,
    CircleHelp,
    ExternalLink,
    ImageIcon,
    LayoutGrid,
    ListChecks,
    MessageSquareQuote,
    PenLine,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { help } from '@/routes/admin';
import { index as contacts } from '@/routes/admin/contacts';
import { index as dogs } from '@/routes/admin/dogs';
import { index as litters } from '@/routes/admin/litters';
import { index as photos } from '@/routes/admin/photos';
import { edit as siteContent } from '@/routes/admin/site';
import { index as testimonials } from '@/routes/admin/testimonials';
import { index as waitlist } from '@/routes/admin/waitlist';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Litters', href: litters(), icon: Baby },
    { title: 'Dogs', href: dogs(), icon: Dog },
    { title: 'Waitlist', href: waitlist(), icon: ListChecks },
    { title: 'Contacts', href: contacts(), icon: BookUser },
    { title: 'Photos', href: photos(), icon: ImageIcon },
    { title: 'Happy families', href: testimonials(), icon: MessageSquareQuote },
    { title: 'Website text', href: siteContent(), icon: PenLine },
];

const footerNavItems: NavItem[] = [
    { title: 'Help', href: help(), icon: CircleHelp },
    { title: 'View website', href: home(), icon: ExternalLink },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
