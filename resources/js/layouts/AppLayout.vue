<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';

const props = defineProps<{
    activeMode: string;
}>();

const emit = defineEmits<{
    (e: 'mode-change', mode: string): void;
    (e: 'toggle-debug'): void;
}>();
</script>

<template>
    <AppHeaderLayout :active-mode="props.activeMode" @mode-change="emit('mode-change', $event)"
        @toggle-debug="emit('toggle-debug')">
        <slot />
    </AppHeaderLayout>
</template>
