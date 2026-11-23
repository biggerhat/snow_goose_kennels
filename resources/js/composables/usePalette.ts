import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Palette } from '@/types';

export const palettes: { value: Palette; label: string; swatch: string }[] = [
    { value: 'black', label: 'Black Lab', swatch: 'oklch(0.2 0.01 80)' },
    {
        value: 'chocolate',
        label: 'Chocolate Lab',
        swatch: 'oklch(0.38 0.07 45)',
    },
    { value: 'yellow', label: 'Yellow Lab', swatch: 'oklch(0.8 0.15 85)' },
];

const isPalette = (v: unknown): v is Palette =>
    palettes.some((p) => p.value === v);

// Set in the browser only. On the server the saved cookie (shared as a page prop) decides, per request.
const chosen = ref<Palette | null>(null);

function applyPalette(value: Palette): void {
    document.documentElement.dataset.theme = value;
}

export function initializePalette(): void {
    if (typeof window === 'undefined') {
        return;
    }

    let stored: string | null = null;

    try {
        stored = localStorage.getItem('palette');
    } catch {
        // storage unavailable; fall back to the server-rendered theme
    }

    const current = document.documentElement.dataset.theme;
    const value = isPalette(stored)
        ? stored
        : isPalette(current)
          ? current
          : 'black';
    chosen.value = value;
    applyPalette(value);
}

export function usePalette() {
    const page = usePage();
    const palette = computed<Palette>(
        () =>
            chosen.value ??
            (isPalette(page.props.palette) ? page.props.palette : 'black'),
    );

    function updatePalette(value: Palette): void {
        chosen.value = value;

        try {
            localStorage.setItem('palette', value);
        } catch {
            // ignore
        }

        document.cookie = `palette=${value};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
        applyPalette(value);
    }

    return { palette, palettes, updatePalette };
}
