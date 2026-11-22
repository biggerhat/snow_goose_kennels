import { ref } from 'vue';
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

const palette = ref<Palette>('black');

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
    palette.value = isPalette(stored)
        ? stored
        : isPalette(current)
          ? current
          : 'black';
    applyPalette(palette.value);
}

export function usePalette() {
    function updatePalette(value: Palette): void {
        palette.value = value;

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
