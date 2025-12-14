<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps } from '@/types';

const HARD_MODE_CLUE_THRESHOLDS = [0, 0, 0, 5, 6, 10];

const props = defineProps<{
  mode: 'classic' | 'hard' | 'image';
  errors?: Record<string, string>;
  secondsUntilReset?: number;
  attempts?: number;
}>();

const page = usePage();
const modeKey = (mode: string) => mode.charAt(0).toUpperCase() + mode.slice(1);

const guessCorrect = computed<boolean | null>(() => {
  const key = `guessCorrect${modeKey(props.mode)}` as keyof AppPageProps;
  const value = page.props[key];
  return typeof value === 'boolean' ? value : null;
});

const messageBoxShow = computed<boolean>(() => {
  const key = `messageBoxShow${modeKey(props.mode)}` as keyof AppPageProps;
  return Boolean(page.props[key]);
});

const showAlreadySolved = computed<boolean>(() => messageBoxShow.value || guessCorrect.value === true);

const now = ref<Date>(new Date());
let intervalId: number | undefined;

onMounted(() => {
  intervalId = window.setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});

const explicitNextReset = computed<Date | null>(() => {
  const key = `nextResetAt${modeKey(props.mode)}`;
  const raw = (page.props as Record<string, unknown>)[key];
  if (raw) {
    const d = new Date(raw as string);
    if (!isNaN(d.getTime())) return d;
  }
  if (props.secondsUntilReset && props.secondsUntilReset > 0) {
    return new Date(Date.now() + props.secondsUntilReset * 1000);
  }
  return null;
});

const nextResetAt = computed<Date>(() => {
  const d = explicitNextReset.value;
  if (d) return d;
  const fallback = new Date(now.value);
  fallback.setHours(24, 0, 0, 0);
  return fallback;
});

const remainingSeconds = computed<number>(() => {
  const diffMs = nextResetAt.value.getTime() - now.value.getTime();
  return Math.max(0, Math.floor(diffMs / 1000));
});

const formatted = computed<string>(() => {
  const total = remainingSeconds.value;
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;
  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
});

const hardModeClueProgress = computed(() => {
  if (props.mode !== 'hard') {
    return { nextIn: null as number | null, noMore: false };
  }

  const attempts = props.attempts ?? 0;
  const nextThreshold = HARD_MODE_CLUE_THRESHOLDS.find((threshold) => threshold > attempts);

  if (typeof nextThreshold === 'number') {
    return { nextIn: nextThreshold - attempts, noMore: false };
  }

  return { nextIn: null, noMore: true };
});

const attemptsUntilNextClue = computed<number | null>(() => hardModeClueProgress.value.nextIn);
const allHardCluesUnlocked = computed<boolean>(() => hardModeClueProgress.value.noMore);
</script>

<template>
  <div>
    <div
      class="game-field-top flex flex-col items-center pt-[0.5rem]"
      v-if="showAlreadySolved"
    >
      <h1>You've already guessed today's student in this mode.</h1>
      <p>Time until the next game: <span>{{ formatted }}</span></p>
    </div>

    <div v-else class="game-field-top flex flex-col items-center justify-center">
      <h1 class="justify-center flex">
        Enter a student's name to start
      </h1>
      <div v-if="errors?.student_name" class="text-red-500 mb-2">
        {{ errors.student_name }}
      </div>
      <p v-if="guessCorrect === false" class="text-red-500">
        Incorrect guess. Try again!
      </p>
      <p
        v-if="guessCorrect === false && props.mode === 'hard' && attemptsUntilNextClue !== null"
        class="text-sm text-neutral-200 mt-1 text-center"
      >
        {{ attemptsUntilNextClue === 1 ? '1 guess left until the next clue unlocks.' : `${attemptsUntilNextClue} guesses left until the next clue unlocks.` }}
      </p>
      <p
        v-else-if="guessCorrect === false && props.mode === 'hard' && allHardCluesUnlocked"
        class="text-sm text-neutral-200 mt-1 text-center"
      >
        All available clues have been unlocked.
      </p>
    </div>
  </div>
</template>
