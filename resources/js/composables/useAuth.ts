import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { AppPageProps, User } from '@/types';

export function useAuth() {
  const page = usePage<AppPageProps>();

  const user = computed<User | null>(() => page.props.auth?.user ?? null);
  const isLoggedIn = computed(() => !!user.value);

  function logout() {
    router.post(route('logout'));
  }

  function requireAuth(callback?: () => void) {
    if (!user.value) {
      router.visit('/');
      return false;
    }
    if (callback) callback();
    return true;
  }

  return { user, isLoggedIn, logout, requireAuth };
}
