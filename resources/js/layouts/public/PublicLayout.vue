<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PaletteHeads from '@/components/PaletteHeads.vue';
import Reeds from '@/components/site/Reeds.vue';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, home, login } from '@/routes';

const page = usePage();
const name = computed(() => page.props.name as string);
const user = computed(() => page.props.auth.user);

const nav = computed(() => [
    { label: 'Litters', href: '/litters' },
    { label: 'Our Dogs', href: '/our-dogs' },
    ...((page.props.site as { hasProcess: boolean }).hasProcess
        ? [{ label: 'How it works', href: '/how-it-works' }]
        : []),
    ...((page.props.site as { hasFamilies: boolean }).hasFamilies
        ? [{ label: 'Happy families', href: '/happy-families' }]
        : []),
    { label: 'Gallery', href: '/gallery' },
    { label: 'Contact', href: '/contact' },
]);

const current = computed(() => page.url.split('?')[0]);
const isActive = (href: string) =>
    current.value === href || current.value.startsWith(`${href}/`);
</script>

<template>
    <div class="paper flex min-h-screen flex-col text-foreground">
        <header class="border-b border-border/60">
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-4"
            >
                <Link :href="home()" class="flex items-center gap-3">
                    <AppLogoIcon class-name="h-12 w-auto" />
                    <span
                        class="font-serif text-xl leading-none font-semibold tracking-tight sm:text-2xl"
                        >{{ name }}</span
                    >
                </Link>
                <nav
                    class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm font-medium"
                >
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'underline-offset-8 hover:underline',
                            isActive(item.href) && 'underline decoration-2',
                        ]"
                        >{{ item.label }}</Link
                    >
                    <Link
                        href="/apply"
                        class="rounded-full bg-primary px-4 py-1.5 text-primary-foreground hover:opacity-90"
                        >Apply</Link
                    >
                </nav>
            </div>
        </header>

        <main class="flex-1"><slot /></main>

        <footer class="mt-16 border-t border-border/60 py-10">
            <Reeds />
            <div
                class="mx-auto mt-6 flex max-w-6xl flex-col items-center gap-5 px-5 text-center text-sm text-muted-foreground"
            >
                <PaletteHeads />
                <p>© {{ new Date().getFullYear() }} {{ name }}</p>
                <Link
                    :href="user ? dashboard() : login()"
                    class="underline-offset-4 hover:underline"
                >
                    {{ user ? 'Back to dashboard' : 'Owner login' }}
                </Link>
            </div>
        </footer>
        <Toaster />
    </div>
</template>
