<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage<{ statistics?: Record<string, unknown> }>();
const stats = computed<Record<string, any>>(() => (page.props.statistics as Record<string, any>) ?? {});
const userPercentile = computed(() => stats.value.userPercentile ?? null);
const modeSnapshots = computed<Record<string, any>>(() => (stats.value.modes as Record<string, any>) ?? {});
const hardModeHintSuccessRates = computed<Record<string, any> | null>(
  () => (stats.value.hardModeHintSuccessRates as Record<string, any>) ?? null
);

const formatMap = (value: unknown) => {
  if (value === undefined || value === null) {
    return 'N/A';
  }

  if (typeof value === 'object') {
    return JSON.stringify(value);
  }

  return value;
};

const studentName = (student: any) => {
  if (!student || typeof student !== 'object') {
    return 'Unknown';
  }

  return `${student.first_name ?? ''} ${student.second_name ?? ''}`.trim() || 'Unknown';
};

const describeDifficulty = (entry: any) => {
  if (!entry || typeof entry !== 'object') {
    return 'N/A';
  }

  const parts: string[] = [];

  if (entry.average !== undefined) {
    parts.push(`avg ${entry.average}`);
  }

  if (entry.runs !== undefined) {
    parts.push(`${entry.runs} runs`);
  }

  const base = studentName(entry.student);

  return parts.length ? `${base} (${parts.join(', ')})` : base;
};

const describeCountEntry = (entry: any, field: string) => {
  if (!entry || typeof entry !== 'object') {
    return 'N/A';
  }

  const value = entry[field];
  const base = studentName(entry.student);

  return value !== undefined ? `${base} (${value})` : base;
};

const statEntries = computed(() => [
  { key: 'totalGuesses', label: 'Total guesses', value: stats.value.totalGuesses },
  { key: 'totalGuessesByUser', label: 'Total guesses by registered users', value: stats.value.totalGuessesByUser },
  { key: 'totalGuessesCurrentUser', label: 'Total guesses for you', value: stats.value.totalGuessesCurrentUser },
  { key: 'todayGuesses', label: 'Guesses today (all modes)', value: stats.value.todayGuesses },
  { key: 'todayGuessesByMode', label: 'Guesses today by mode', value: formatMap(stats.value.todayGuessesByMode) },
  { key: 'todayCorrectGuesses', label: 'Correct guesses today', value: stats.value.todayCorrectGuesses },
  {
    key: 'todayCorrectGuessesByMode',
    label: 'Correct guesses today by mode',
    value: formatMap(stats.value.todayCorrectGuessesByMode)
  },
  {
    key: 'historicalCorrectGuessesByUser',
    label: 'All-time correct guesses by registered users',
    value: stats.value.historicalCorrectGuessesByUser
  },
  { key: 'bestDailyStreak', label: 'Best daily streak (you)', value: stats.value.bestDailyStreak ?? 'N/A' },
  { key: 'currentDailyStreak', label: 'Current daily streak (you)', value: stats.value.currentDailyStreak ?? 'N/A' },
  {
    key: 'userPercentile',
    label: 'Your average vs community',
    value: userPercentile.value
      ? `${userPercentile.value.averageAttempts} avg attempts across ${userPercentile.value.runs} runs (percentile ${userPercentile.value.percentile}% of ${userPercentile.value.totalPlayers} players)`
      : 'N/A'
  },
  { key: 'averageGuessPerStudent', label: 'Avg. guesses per student (overall)', value: stats.value.averageGuessPerStudent },
  {
    key: 'averageGuessPerStudentByMode',
    label: 'Avg. guesses per student by mode',
    value: formatMap(stats.value.averageGuessPerStudentByMode)
  },
  {
    key: 'averageGuessPerRegisteredUser',
    label: 'Avg. guesses per registered user',
    value: stats.value.averageGuessPerRegisteredUser
  },
  {
    key: 'averageGuessCurrentUser',
    label: 'Avg. guesses for you',
    value: stats.value.averageGuessCurrentUser ?? 'N/A'
  },
  { key: 'hardestStudent', label: 'Hardest student to guess', value: describeDifficulty(stats.value.hardestStudent) },
  { key: 'easiestStudent', label: 'Easiest student to guess', value: describeDifficulty(stats.value.easiestStudent) },
  { key: 'mostGuessedStudent', label: 'Most guessed student', value: describeCountEntry(stats.value.mostGuessedStudent, 'guesses') },
  { key: 'leastGuessedStudent', label: 'Least guessed student', value: describeCountEntry(stats.value.leastGuessedStudent, 'guesses') },
  {
    key: 'mostFirstGuessStudent',
    label: 'Most common first guess',
    value: describeCountEntry(stats.value.mostFirstGuessStudent, 'count')
  },
  {
    key: 'mostGoalStudent',
    label: 'Most frequent daily student',
    value: describeCountEntry(stats.value.mostGoalStudent, 'appearances')
  }
]);

const modeEntries = computed(() => Object.entries(modeSnapshots.value).map(([key, mode]: [string, any]) => ({
  key: `mode-${key}`,
  label: `Mode ${mode?.name ?? key}`,
  value: `total:${mode?.totalGuesses ?? 0}, runs:${mode?.runs ?? 0}, avg:${mode?.averageAttempts ?? 0}, today:${mode?.todayGuesses ?? 0}, todayCorrect:${mode?.todayCorrect ?? 0}`
})));

const hintEntries = computed(() => {
  if (!hardModeHintSuccessRates.value) {
    return [];
  }

  return [
    {
      key: 'hintSuccessRates',
      label: 'Hint success rates',
      value: String(formatMap(hardModeHintSuccessRates.value))
    }
  ];
});
</script>

<template>
  <div class="game-container">
      <div class="game-field bg-black">
        <div class="m-[1rem]">
          <p v-for="stat in statEntries" :key="stat.key">{{ stat.label }}: {{ stat.value ?? 'N/A' }}</p>
        </div>
        <br />
        <div class="m-[1rem]" v-if="modeEntries.length">
          <p v-for="mode in modeEntries" :key="mode.key">{{ mode.label }}: {{ mode.value }}</p>
        </div>
        <br />
        <div class="m-[1rem]" v-if="hintEntries.length">
          <p v-for="entry in hintEntries" :key="entry.key">{{ entry.label }}: {{ entry.value }}</p>
        </div>
        <br />
      </div>
  </div>
</template>
