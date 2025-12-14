<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { usePage } from '@inertiajs/vue3';
import { Menu, Gamepad2, Crown, Image, LogIn, UserRoundPen, ChartBarBig, Info, Bug } from 'lucide-vue-next';
import type { NavItem, AppPageProps } from '@/types';
import { computed, onBeforeUnmount, onMounted, ref, watch, type WatchStopHandle } from 'vue';

interface Props {
  activeMode: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'mode-change', mode: string): void;
  (e: 'toggle-debug'): void;
}>();
const page = usePage();
const auth = computed(() => (page.props as AppPageProps).auth ?? { user: null });
type StatisticPayload = {
  todayGuesses?: number;
  todayCorrectGuessesByMode?: Record<string, number>;
};
type SidebarStats = {
  todayGuesses: number;
  todayCorrectGuesses: number;
  currentAttempts: number;
};

const allNavItems: NavItem[] = [
  { title: 'Normal', mode: 'classic', icon: Gamepad2, href: '/classic' },
  { title: 'Hard', mode: 'hard', icon: Crown, href: '/hard' },
  { title: 'Image', mode: 'image', icon: Image, href: '/image' },
  { title: 'About', mode: 'about', icon: Info, href: '/about' },
];

const canViewStatistics = computed(() => auth.value?.user?.user_level === 2);
const mainNavItems = computed<NavItem[]>(() => {
  const items = [...allNavItems];

  if (canViewStatistics.value) {
    items.splice(3, 0, { title: 'Statistics', mode: 'statistics', icon: ChartBarBig, href: '/statistics' });
  }

  return items;
});
const statistics = computed<StatisticPayload>(() => ((page.props as AppPageProps).statistics ?? {}) as StatisticPayload);
const modeAttemptLookup: Record<string, string> = {
  classic: 'Classic',
  hard: 'Hard',
  image: 'Image',
};
const statsMode = computed(() => (modeAttemptLookup[props.activeMode] ? props.activeMode : 'classic'));
const statsModeLabel = computed(() => modeAttemptLookup[statsMode.value] ?? 'Classic');
const attemptsForActiveMode = computed(() => {
  const modeKey = modeAttemptLookup[statsMode.value];

  if (!modeKey) {
    return 0;
  }

  const attemptsKey = `attempts${modeKey}` as keyof AppPageProps;
  const attemptValue = (page.props as AppPageProps)[attemptsKey];
  return typeof attemptValue === 'number' ? attemptValue : 0;
});
const sidebarStats = ref<SidebarStats>({
  todayGuesses: statistics.value?.todayGuesses ?? 0,
  todayCorrectGuesses: statistics.value?.todayCorrectGuessesByMode?.[statsMode.value] ?? 0,
  currentAttempts: attemptsForActiveMode.value,
});

const getCorrectGuessesFromProps = () =>
  statistics.value?.todayCorrectGuessesByMode?.[statsMode.value];

watch(
  () => statistics.value,
  () => {
    if (typeof statistics.value?.todayGuesses === 'number') {
      sidebarStats.value.todayGuesses = statistics.value.todayGuesses;
    }

    const modeCorrect = getCorrectGuessesFromProps();
    if (typeof modeCorrect === 'number') {
      sidebarStats.value.todayCorrectGuesses = modeCorrect;
    }
  },
  { deep: true, immediate: true }
);

watch(
  statsMode,
  () => {
    sidebarStats.value.currentAttempts = attemptsForActiveMode.value;
    const modeCorrect = getCorrectGuessesFromProps();
    if (typeof modeCorrect === 'number') {
      sidebarStats.value.todayCorrectGuesses = modeCorrect;
    }
  },
  { immediate: true }
);

watch(
  () => attemptsForActiveMode.value,
  (newAttempts) => {
    sidebarStats.value.currentAttempts = newAttempts;
  }
);
const fetchSidebarStats = async () => {
  const mode = statsMode.value;

  if (!mode) {
    return;
  }

  try {
    const response = await fetch(`/sidebar-stats?mode=${encodeURIComponent(mode)}`, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      return;
    }

    const payload = await response.json() as Partial<SidebarStats>;

    if (mode !== statsMode.value) {
      return;
    }

    if (typeof payload.todayGuesses === 'number') {
      sidebarStats.value.todayGuesses = payload.todayGuesses;
    }

    if (typeof payload.todayCorrectGuesses === 'number') {
      sidebarStats.value.todayCorrectGuesses = payload.todayCorrectGuesses;
    }

    if (typeof payload.currentAttempts === 'number') {
      sidebarStats.value.currentAttempts = payload.currentAttempts;
    }
  } catch (error) {
    console.error('Failed to refresh sidebar stats', error);
  }
};

const POLL_INTERVAL_MS = 15000;
let pollHandle: number | null = null;
let stopModeWatcher: WatchStopHandle | null = null;

const startPolling = () => {
  if (typeof window === 'undefined') {
    return;
  }

  stopPolling();
  fetchSidebarStats();
  pollHandle = window.setInterval(fetchSidebarStats, POLL_INTERVAL_MS);
};

const stopPolling = () => {
  if (pollHandle) {
    window.clearInterval(pollHandle);
    pollHandle = null;
  }
};

onMounted(() => {
  startPolling();
  stopModeWatcher = watch(statsMode, () => {
    fetchSidebarStats();
  });
});

onBeforeUnmount(() => {
  stopPolling();
  stopModeWatcher?.();
});
</script>

<template>
  <div class="flex">
    <!-- Desktop Sidebar -->
    <div
      class="hidden lg:flex lg:flex-col bg-neutral-900 w-[15rem] h-screen p-4 border-r border-neutral-800 text-neutral-200">
      <div class="flex items-center justify-center mb-2">
        <AppLogoIcon class="h-auto w-[15rem]" />
      </div>

      <nav class="flex flex-col space-y-2">
        <button v-for="item in mainNavItems" :key="item.title"
          class="flex items-center rounded px-4 py-3 hover:bg-neutral-800 transition-colors"
          :class="props.activeMode === item.mode ? 'bg-neutral-800 text-neutral-100' : ''"
          @click="emit('mode-change', item.mode ?? '')">
          <component v-if="item.icon" :is="item.icon" class="mr-3 h-5 w-5" />
          {{ item.title }}
        </button>
      </nav>

      <div class="sidebar-inline bg-neutral-800">
        <br>
        <p>Global guesses today:</p>
        <p><span>{{ sidebarStats.todayGuesses }}</span></p><br>
        <p>Global correct guesses today ({{ statsModeLabel }}):</p>
        <p><span>{{ sidebarStats.todayCorrectGuesses }}</span></p><br>
        <p>Your own {{ statsModeLabel }} attempts:</p>
        <p><span>{{ sidebarStats.currentAttempts }}</span></p>
      </div>

      <!-- Bottom section -->
      <div class="mt-auto pt-6 border-t border-neutral-800 space-y-2">
        <!-- Logged in -->
        <template v-if="auth.user">
          <button size="icon" variant="ghost"
            class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors"
            @click="emit('mode-change', 'profile')">
            <UserRoundPen class="mr-3 h-6 w-6" /><span class="text-lg truncate">{{ auth.user?.name }}</span>
          </button>
        </template>

        <!-- Logged out -->
        <template v-else>
          <button @click="emit('mode-change', 'login')"
            class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors">
            <LogIn class="mr-3 h-5 w-5" />Login
          </button>
        </template>

        <button v-if="auth.user?.user_level === 2"
          class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors"
          @click="emit('toggle-debug')">
          <Bug class="mr-3 h-5 w-5" />Toggle Debug Panel
        </button>
      </div>
    </div>

    <!-- Mobile -->
    <div class="lg:hidden flex h-[3rem] w-full items-center px-2">
      <Sheet>
        <SheetTrigger :as-child="true">
          <Button variant="outline" size="icon"
            class="mr-2 aspect-square w-[2.5rem] h-[2.5rem] p-[0.7rem] focus-within:ring-1 focus-within:ring-primary">
            <Menu class="h-5 w-5" />
          </Button>
        </SheetTrigger>
        <SheetContent side="left" class="w-[15rem] p-6 bg-neutral-900">
          <AppLogoIcon class="h-auto w-full mb-4" />
          <nav class="flex flex-col space-y-5">
            <button v-for="item in mainNavItems" :key="item.title"
              class="flex items-center rounded px-4 py-3 hover:bg-neutral-800 text-white"
              :class="props.activeMode === item.mode ? 'bg-neutral-800 text-neutral-100' : ''"
              @click="emit('mode-change', item.mode ?? '')">
              <component v-if="item.icon" :is="item.icon" class="mr-3 h-5 w-5" />
              {{ item.title }}
            </button>
          </nav>
          <div>
            <div class="sidebar-inline bg-neutral-800 text-white">
              <p>Total guesses today:</p>
              <p><span>{{ sidebarStats.todayGuesses }}</span></p><br>
              <p>Correct guesses today ({{ statsModeLabel }}):</p>
              <p><span>{{ sidebarStats.todayCorrectGuesses }}</span></p><br>
              <p>Your {{ statsModeLabel }} attempts:</p>
              <p><span>{{ sidebarStats.currentAttempts }}</span></p>
            </div>
          </div>
          <div class="mt-auto pt-6 border-t border-neutral-800 space-y-2">
            <!-- Logged in -->
            <template v-if="auth.user">
              <button size="icon" variant="ghost"
                class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors text-white"
                @click="emit('mode-change', 'profile')">
                <UserRoundPen class="mr-3 h-6 w-6" /><span class="text-lg truncate">{{ auth.user?.name }}</span>
              </button>
            </template>

            <!-- Logged out -->
            <template v-else>
              <button @click="emit('mode-change', 'login')"
                class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors text-white">
                <LogIn class="mr-3 h-5 w-5" />Login
              </button>
            </template>

            <button v-if="auth.user?.user_level === 2"
              class="flex items-center rounded px-3 py-2 w-full text-left hover:bg-neutral-800 transition-colors text-white"
              @click="emit('toggle-debug')">
              <Bug class="mr-3 h-5 w-5" />Toggle Debug Panel
            </button>
          </div>
        </SheetContent>
      </Sheet>
    </div>
    <!-- Page content slot -->
    <main class="flex-1 bg-neutral-950 text-neutral-100 overflow-y-auto">
      <slot />
    </main>
  </div>
</template>
