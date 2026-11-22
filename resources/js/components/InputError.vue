<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" :data-field-error="message ? '' : undefined">
        <p class="text-sm text-red-600 dark:text-red-500" role="alert">
            {{ message }}
        </p>
    </div>
</template>
