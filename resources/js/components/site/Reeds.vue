<script setup lang="ts">
// Decorative cattail divider.
</script>

<template>
    <div
        class="flex items-end justify-center gap-3 text-foreground/70"
        aria-hidden="true"
    >
        <span class="h-px w-16 bg-current/30 sm:w-32" />
        <svg viewBox="0 0 64 40" class="h-8 w-12 fill-current">
            <g class="sway">
                <rect x="30" y="8" width="1.6" height="32" />
                <ellipse cx="30.8" cy="11" rx="3.2" ry="7.5" />
                <path d="M30.8 0 L31.4 4 L30.2 4 Z" />
            </g>
            <g class="sway" style="animation-delay: -2s">
                <rect x="15" y="18" width="1.4" height="22" />
                <ellipse cx="15.7" cy="20" rx="2.6" ry="6" />
            </g>
            <g class="sway" style="animation-delay: -4s">
                <rect x="45" y="16" width="1.4" height="24" />
                <ellipse cx="45.7" cy="18" rx="2.6" ry="6.5" />
            </g>
        </svg>
        <span class="h-px w-16 bg-current/30 sm:w-32" />
    </div>
</template>
