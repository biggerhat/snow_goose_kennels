<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" :data-field-error="message ? '' : undefined">
        <p class="text-sm text-red-700 dark:text-red-400" role="alert">
            {{ message }}
        </p>
    </div>
</template>
