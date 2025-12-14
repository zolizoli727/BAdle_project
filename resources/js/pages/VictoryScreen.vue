<script setup lang="ts">
import type { Student } from '@/types/student';
import { defineEmits, defineProps } from 'vue';

defineProps<{
    dailyStudent?: Student;
    attempts?: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'next-page', next: 'classic' | 'hard' | 'image'): void
}>();

function closePanel() {
    emit('close');
}

function nextMode() {
    const current = 'classic';
    const next =
        current === 'classic'
            ? 'hard'
            : current === 'hard'
                ? 'image'
                : 'classic';

    emit('next-page', next);
}

</script>
<template>
    <div class="flex flex-col items-center">
        <h1 class="text-xl">Congratulations!</h1>
        <p>You guessed the correct student!</p>
        <p class="text-sm">Number of attempts: <span>{{ attempts }}</span></p>
        <img :src="dailyStudent?.image" :alt="`${dailyStudent?.first_name} ${dailyStudent?.second_name}`"
            class="object-cover" />
        <div>
            <button @click="closePanel" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                Close
            </button>
            <button @click="nextMode" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                Next Mode
            </button>
        </div>
    </div>
</template>
