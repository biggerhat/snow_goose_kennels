<script setup lang="ts">
defineProps<{ name: string; active: boolean }>();
</script>

<template>
    <div
        v-show="active"
        :id="`panel-${name}`"
        role="tabpanel"
        :aria-labelledby="`tab-${name}`"
        class="space-y-6"
    >
        <slot />
    </div>
</template>
