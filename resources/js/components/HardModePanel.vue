<script setup lang="ts">
import type { Student } from '@/types/student';
import { defineProps } from 'vue';
import type { Clue } from '@/types';

const props = defineProps<{
    dailyStudent: Student;
    hardModeClues?: Clue[];
}>();
</script>

<template>
    <div>
        <table class="m-[1rem] listing-table bg-gray-500 mx-auto">
            <thead class="bg-gray-600 text-small md:text-base tracking-wide">
                <tr>
                    <th v-for="clue in props.hardModeClues" :key="clue.label">
                        {{ clue.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td v-for="clue in props.hardModeClues" :key="clue.label" class="p-2 text-center">
                        <div v-if="clue.label === 'Image'" class="card">
                            <img :src="clue.value" :alt="`${dailyStudent.first_name} ${dailyStudent.second_name}`" />
                        </div>
                        <div v-else>
                            {{ clue.value }}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
