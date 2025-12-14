<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import type { Student } from '@/types/student';
import type { HeightStatus } from '@/types/history';
import type { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const isAdmin = computed(() => {
    const userLevel = (page.props.auth?.user?.user_level ?? 0) as number;
    return userLevel === 2;
});

const props = defineProps<{
    attempts: number;
    matches?: {
        fields: Record<string, boolean>;
        height: HeightStatus;
    };
    guessedStudentData?: Student | null;
    guessCorrect?: boolean | null;
    dailyStudent?: Student;
    classicGameState?: boolean;
    currentMode: 'classic' | 'hard' | 'image';
}>();

const showMatches = ref(false);
const showGuessedStudent = ref(false);
const showGuessCorrect = ref(false);
const showDailyStudent = ref(false);
const localGameState = ref(page.props[`${props.currentMode}GameState`] ?? false);
const showClues = ref(false);
const clues = ref<any>(null);
const loadingClues = ref(false);

watch(
    () => page.props[`${props.currentMode}GameState`],
    (newVal) => {
        localGameState.value = newVal ?? false;
    }
);

function clearHistory() {
    if (!isAdmin.value) {
        return;
    }
    router.post(route('clear-history', { mode: props.currentMode }), {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function forceChangeGameState() {
    if (!isAdmin.value) {
        return;
    }
    axios.post(route('toggle-game-state', { mode: props.currentMode }))
        .then(() => {
            router.reload({ only: [`${props.currentMode}GameState`] });
        });
}

async function fetchClues(force = false) {
    if (!isAdmin.value) {
        return;
    }
    loadingClues.value = true;
    try {
        const response = await axios.post(route('get-hard-mode-clues'), { force });
        clues.value = response.data;
        showClues.value = true;
    } catch (error) {
        console.error('Failed to fetch clues:', error);
    } finally {
        loadingClues.value = false;
    }
}

async function toggleClues() {
    if (showClues.value) {
        showClues.value = false;
        clues.value = null;
        return;
    }
    await fetchClues(false);
}

async function regenerateClues() {
    await fetchClues(true);
    router.reload({ only: ['hardModeClues'] });
}


function toggleAll(show: boolean) {
    showMatches.value = show;
    showGuessedStudent.value = show;
    showGuessCorrect.value = show;
    showDailyStudent.value = show;
}
</script>

<template>
    <div v-if="isAdmin" class="debug-info p-4 bg-gray-700 text-white">
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="toggleAll(true)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                Show All
            </button>
            <button @click="toggleAll(false)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                Hide All
            </button>
            <button @click="showMatches = !showMatches" class="px-3 py-1 bg-gray-600 hover:bg-gray-500 rounded text-sm">
                {{ showMatches ? 'Hide' : 'Show' }} Matches
            </button>
            <button @click="showGuessedStudent = !showGuessedStudent"
                class="px-3 py-1 bg-gray-600 hover:bg-gray-500 rounded text-sm">
                {{ showGuessedStudent ? 'Hide' : 'Show' }} Guessed Student
            </button>
            <button @click="showDailyStudent = !showDailyStudent"
                class="px-3 py-1 bg-gray-600 hover:bg-gray-500 rounded text-sm">
                {{ showDailyStudent ? 'Hide' : 'Show' }} Daily Student
            </button>
            <div v-if="props.currentMode === 'hard'" class="flex gap-2">
                <button @click="toggleClues" :disabled="loadingClues"
                    class="px-3 py-1 bg-gray-600 hover:bg-gray-500 rounded text-sm disabled:opacity-50">
                    {{ loadingClues ? 'Loading...' : (showClues ? 'Hide' : 'Show') }} Hard Mode Clues
                </button>
                <button @click="regenerateClues" :disabled="loadingClues"
                    class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-sm disabled:opacity-50">
                    Regenerate Clues
                </button>
            </div>
            <p>Number of Attempts: {{ attempts }}</p>
            <button @click="clearHistory" class="ml-auto px-4 py-1 bg-red-700 hover:bg-red-800 rounded text-sm">
                Clear History
            </button>
            <button @click="forceChangeGameState" class="px-4 py-1 bg-red-700 hover:bg-red-800 rounded text-sm">
                Force change {{ props.currentMode }} game state {{ localGameState }}
            </button>
        </div>

        <div v-if="showMatches">
            <p>matches value:</p>
            <pre>{{ JSON.stringify(matches, null, 2) }}</pre>
        </div>

        <div v-if="showGuessedStudent">
            <p>Guessed student exists: {{ !!guessedStudentData }}</p>
            <pre v-if="guessedStudentData">{{ JSON.stringify(guessedStudentData, null, 2) }}</pre>
        </div>

        <div v-if="showDailyStudent && dailyStudent" class="mt-4 p-4 rounded">
            <h3 class="font-bold">Today's Student:</h3>
            <p>{{ dailyStudent.first_name }} {{ dailyStudent.second_name }}</p>
            <img :src="dailyStudent.image" :alt="`${dailyStudent.first_name} ${dailyStudent.second_name}`"
                class="w-24 h-24 mt-2 object-cover" />
        </div>

        <div v-if="showClues && clues" class="mt-4 p-4">
            <table class="w-full text-sm border-collapse">
                <tbody>
                    <tr v-for="(clue, index) in clues" :key="index">
                        <td>{{ clue.label }}</td>
                        <td>{{ clue.value }}</td>
                    </tr>
                </tbody>
            </table>
            {{ JSON.stringify(clues, null, 2) }}
        </div>


    </div>
</template>
