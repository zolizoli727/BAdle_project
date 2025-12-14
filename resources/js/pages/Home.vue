<script setup lang="ts">
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import ClassicMode from './Modes/ClassicMode.vue';
import HardMode from './Modes/HardMode.vue';
import ImageMode from './Modes/ImageMode.vue';
import DebugPanel from '@/components/DebugPanel.vue'
import ProfilePage from '@/pages/settings/Profile.vue';
import LoginPage from '@/pages/auth/Login.vue';
import About from './Modes/About.vue';
import StatisticsPage from './Modes/Statistics.vue';
import RegisterPage from '@/pages/auth/Register.vue';
import { ref, computed, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3'
import type { Student } from '@/types/student'
import type { HeightStatus, History } from '@/types/history';
import type { Clue, AppPageProps } from '@/types';
import ForgotPassword from './auth/ForgotPassword.vue';

type PageMode =
  | 'classic'
  | 'hard'
  | 'image'
  | 'profile'
  | 'login'
  | 'register'
  | 'forgot'
  | 'about'
  | 'statistics';

const page = usePage();
const auth = computed(() => (page.props as AppPageProps).auth ?? { user: null });
const flashSuccessMessage = ref<string | null>((page.props as any).flash?.success as string | null);
const canViewStatistics = computed(() => auth.value?.user?.user_level === 2);
const baseAllowedPages: PageMode[] = ['classic', 'hard', 'image', 'profile', 'login', 'register', 'forgot', 'about'];
const allowedPages = computed<PageMode[]>(() => {
  const pages: PageMode[] = [...baseAllowedPages];

  if (canViewStatistics.value) {
    pages.push('statistics');
  }

  return pages;
});
const currentPage = ref<PageMode>('classic');
function setPage(mode: string) {
  if (allowedPages.value.includes(mode as PageMode)) {
    currentPage.value = mode as PageMode;
  }
}
function modeKey(mode: string) { return mode.charAt(0).toUpperCase() + mode.slice(1); }

watch(
  () => (page.props as any).flash?.success as string | null,
  (val) => {
    if (!val) return;
    flashSuccessMessage.value = val;
    setTimeout(() => {
      if (flashSuccessMessage.value === val) {
        flashSuccessMessage.value = null;
      }
    }, 4000);
  },
  { immediate: true },
);

// ----------------------------------------------------------------------------------------

const dailyStudent = computed<Student>(() => {
  const key = `dailyStudent${modeKey(currentPage.value)}` as keyof typeof page.props;
  return page.props[key] as Student ?? {} as Student;
});

const matches = computed<{ fields: Record<string, boolean>; height: HeightStatus }>(() => {
  const props = page.props as AppPageProps;
  const key = modeKey(currentPage.value);
  const fieldsKey = `matches${key}` as keyof AppPageProps;
  const heightKey = `heightStatus${key}` as keyof AppPageProps;
  return {
    fields: (props[fieldsKey] as Record<string, boolean> | undefined) ?? {},
    height: (props[heightKey] as HeightStatus | undefined) ?? null,
  };
});

const gameState = computed<boolean>(() =>
  page.props[`${currentPage.value}GameState`] as boolean ?? false
);

const guessHistory = computed<History[]>(() =>
  page.props[`guessHistory${modeKey(currentPage.value)}`] as History[] ?? []
);

const attempts = computed<number>(() => page.props[`attempts${modeKey(currentPage.value)}`] as number ?? 0);
const hardModeClues = computed(() => page.props.hardModeClues as Clue[]);
const guessedStudentData = computed<Student | null>(() => {
  const props = page.props as AppPageProps;
  const key = `guessedStudentData${modeKey(currentPage.value)}` as keyof AppPageProps;
  return (props[key] as Student | null | undefined) ?? null;
});
const showDebugPanel = ref(true);
const playableDebugModes = ['classic', 'hard', 'image'] as const;
type DebugPanelMode = (typeof playableDebugModes)[number];
const debugPanelMode = computed<DebugPanelMode>(() => {
  return playableDebugModes.includes(currentPage.value as DebugPanelMode)
    ? currentPage.value as DebugPanelMode
    : 'classic';
});

function toggleDebugPanel() {
  showDebugPanel.value = !showDebugPanel.value;
}

</script>

<template>
  <Head title="BAdle — Blue Archive Guessing Game">
    <meta name="description" content="Guess the daily Blue Archive student." />
    <meta property="og:title" content="BAdle — Blue Archive Guessing Game" />
    <meta property="og:description" content="Guess the daily Blue Archive student." />
    <meta property="og:image" content="/videos/poster.jpg" />
  </Head>
  <AppLayout :activeMode="currentPage" @mode-change="setPage" @toggle-debug="toggleDebugPanel">
    <div>
      <div
        v-if="flashSuccessMessage"
        class="fixed left-1/2 top-6 z-50 w-full max-w-xl -translate-x-1/2 transform px-4"
      >
        <div class="rounded-lg border border-emerald-400 bg-emerald-800/85 px-4 py-3 text-center text-emerald-50 shadow-lg backdrop-blur">
          {{ flashSuccessMessage }}
        </div>
      </div>
      <ClassicMode v-show="currentPage === 'classic'" :daily-student="dailyStudent"
        :guessed-student-data="guessedStudentData" :game-state="gameState" :attempts="attempts"
        :guess-history="guessHistory" @next-page="setPage" />

      <HardMode v-show="currentPage === 'hard'" :daily-student="dailyStudent" :guessed-student-data="guessedStudentData"
        :game-state="gameState" :attempts="attempts" :guess-history="guessHistory" :hard-mode-clues="hardModeClues"
        @next-page="setPage" />

      <ImageMode v-show="currentPage === 'image'" />

      <About v-if="currentPage === 'about'" />
      <StatisticsPage v-if="currentPage === 'statistics' && canViewStatistics" />
      
      <KeepAlive>
        <ProfilePage v-if="currentPage === 'profile'" @mode-change="setPage" />
      </KeepAlive>
      <LoginPage v-if="currentPage === 'login'" @mode-change="setPage" :can-reset-password="true" />
      <RegisterPage v-if="currentPage === 'register'" @mode-change="setPage" />
      <ForgotPassword v-if="currentPage === 'forgot'" @mode-change="setPage" />
    </div>

    <DebugPanel v-if="
      showDebugPanel &&
      ['classic', 'hard', 'image'].includes(currentPage) &&
      auth.user &&
      auth.user.user_level === 2
    " :current-mode="debugPanelMode" :matches="matches" :attempts="attempts"
      :guessed-student-data="guessedStudentData" :daily-student="dailyStudent" :game-state="gameState"
      :page-props="page.props" />
  </AppLayout>
</template>
