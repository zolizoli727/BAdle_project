<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import DeleteUser from '@/components/DeleteUser.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useAuth } from '@/composables/useAuth'

defineProps<{ embedded?: boolean }>()
const emit = defineEmits<{ (e: 'mode-change', mode: string): void }>()

const { user, isLoggedIn } = useAuth()
if (!isLoggedIn.value) emit('mode-change', 'login')

const form = useForm({
  name: user.value?.name ?? '',
  email: user.value?.email ?? '',
})

function submit() {
  form.patch(route('profile.update'), { preserveScroll: true })
}

function logout() {
  router.post(
    route('logout'),
    {},
    {
      onSuccess: () => {
        emit('mode-change', 'login')
      },
    }
  )
}
</script>

<template>
  <div class="p-6 profile bg-gray-900 pl-[2rem]">
    <!-- If logged in -->
    <div v-if="isLoggedIn">
      <HeadingSmall title="Profile information" description="Update your name and email address" />

      <!-- Profile form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Name -->
        <div class="grid gap-2">
          <Label for="name">Name</Label>
          <Input class="w-[20rem]" id="name" v-model="form.name" required autocomplete="name" placeholder="Full name" />
          <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <!-- Email -->
        <div class="grid gap-2">
          <Label for="email">Email address</Label>
          <Input class="w-[20rem]" id="email" type="email" v-model="form.email" required autocomplete="username"
            placeholder="Email address" />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <!-- Save -->
        <div class="flex items-center gap-4">
          <Button :disabled="form.processing">Save</Button>
          <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
              Saved.
            </p>
          </Transition>
        </div>
      </form>
      <div>
        <Button variant="destructive" class="mt-6" @click="logout">Log Out</Button>
      </div>
      <!-- Delete user -->
      <DeleteUser class="mt-6" />
    </div>

    <!-- If not logged in -->
    <div v-else class="p-4 text-neutral-400 text-center">
      <p>You must be logged in to view your profile.</p>
      <Button @click="emit('mode-change', 'login')">Go to Login</Button>
    </div>
  </div>
</template>
