<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import AmbientTriangles from '@/components/AmbientTriangles.vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
  activeMode: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'mode-change', mode: string): void;
  (e: 'toggle-debug'): void;
}>();

const isLoaded = ref(false);

const handleLoaded = () => {
  isLoaded.value = true;
};

onMounted(() => {
  if (document.readyState === 'complete') {
    handleLoaded();
    return;
  }
  window.addEventListener('load', handleLoaded, { once: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('load', handleLoaded);
});
</script>

<template>
  <div class="relative bg-cover bg-center overflow-hidden bg-[url('/videos/poster.jpg')]">
    <video class="absolute inset-0 w-full h-full object-cover z-0 pointer-events-none soft-blur-video" autoplay loop
      muted playsinline poster="/videos/poster.jpg" src="/videos/arona.webm"></video>
    <AmbientTriangles
      :count="83"
      :xPadLeftPct="0"
      :seed="83"
      force-motion
    />
    <div class="relative z-10 flex h-screen text-neutral-100">
      <AppHeader
        :activeMode="props.activeMode"
        @mode-change="emit('mode-change', $event)"
        @toggle-debug="emit('toggle-debug')"
      />
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
      <div
        v-if="!isLoaded"
        class="absolute inset-0 z-50 flex items-center justify-center bg-neutral-900 transition-opacity duration-500"
      >
        <div class="text-center text-white">
          <p class="text-lg tracking-wide uppercase">Loading</p>
          <div class="mt-4 h-1 w-48 overflow-hidden rounded bg-white/20">
            <div class="loading-bar h-full bg-white"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
