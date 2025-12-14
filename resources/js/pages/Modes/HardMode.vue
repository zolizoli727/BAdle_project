<script setup lang="ts">
import StudentInput from '@/components/StudentInput.vue';
import GameFeedback from '@/components/GameFeedback.vue';
import VictoryScreen from '@/pages/VictoryScreen.vue';
import type { Student } from '@/types/student';
import type { History } from '@/types/history';
import type { Clue } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import GameListingHard from '@/components/GameListingHard.vue';
// -----------------------------------------------------------------
const page = usePage();
const localHardGameState = ref(page.props.hardGameState ?? false);
const animateCards = ref(false);
const showVictoryOverlay = ref(localHardGameState.value ?? false);

watch(() => page.props.hardGameState, (newVal) => {
  localHardGameState.value = newVal ?? false;
  if (!newVal) {
    showVictoryOverlay.value = false;
    animateCards.value = false;
  }
});

function cardAnim() {
    animateCards.value = true;
    showVictoryOverlay.value = false;
}

function handleAnimationComplete() {
    animateCards.value = false;
    if (localHardGameState.value) {
        showVictoryOverlay.value = true;
    }
}

watch(localHardGameState, (val) => {
    if (!val) {
        showVictoryOverlay.value = false;
        animateCards.value = false;
    } else if (!animateCards.value) {
        showVictoryOverlay.value = true;
    }
});

defineProps<{
    dailyStudent: Student;
    guessedStudentData: Student | null;
    gameState: boolean;
    attempts: number;
    guessHistory: History[];
    hardModeClues?: Clue[];
}>();
</script>

<template>
    <div class="game-container">
        <div class="gradient">
            <div v-if="showVictoryOverlay" class="w-full h-full fixed top-0 left-0 bg-gray-900/70 z-10 rounded-[15px]">
                <div v-if="showVictoryOverlay"
                    class="vic-sc bg-gray-800 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    <VictoryScreen :daily-student="dailyStudent" :attempts="attempts"
                        @close="localHardGameState = false" @next-page="$emit('next-page', $event)" />
                </div>
            </div>

            <div class="game-field">
                <GameFeedback mode="hard" :errors="page.props.errors" :attempts="attempts" />

                <div v-if="!gameState">
                    <StudentInput mode="hard" @validGuess="cardAnim" />
                </div>

                <GameListingHard :attempts="attempts" :guess-history-hard="guessHistory" :daily-student="dailyStudent"
                    :hard-mode-clues="hardModeClues" :animate="animateCards"
                    @animation-complete="handleAnimationComplete" />
            </div>
        </div>
    </div>
</template>
