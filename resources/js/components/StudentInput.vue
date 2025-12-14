<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, toRef, watch } from 'vue';
import type { Student } from '@/types/student';
import { useKeyboardNavigation } from '@/composables/useKeyboardNavigation';
// -----------------------------------------------------------------
const suggestions = ref<string[]>([]);
const showSuggestions = ref(false);
const isValidStudent = ref(false);
const emit = defineEmits<{
    (e: 'guessAttempted', payload: { name: string; isValid: boolean }): void;
    (e: 'validGuess', payload: { isValid: boolean }): void;
}>();
const props = defineProps<{ mode: string }>();
const form = useForm({
    student_name: '',
    mode: ''
});
form.mode = props.mode;

let activeController: AbortController | null = null;

// validation logic
watch(() => form.student_name, async (currentStudentInput: string, _prev, onCleanup) => {
    const trimmedInput = currentStudentInput.trim();

    if (activeController) {
        activeController.abort();
        activeController = null;
    }

    if (!trimmedInput) {
        suggestions.value = [];
        showSuggestions.value = false;
        isValidStudent.value = false;
        return;
    }

    const controller = new AbortController();
    activeController = controller;
    onCleanup(() => controller.abort());

    try {
        const response = await fetch(`/students/search?term=${encodeURIComponent(trimmedInput)}&mode=${props.mode}`, {
            signal: controller.signal
        });
        const data: Student[] = await response.json();

        // ignore responses that are no longer relevant
        if (form.student_name.trim().toLowerCase() !== trimmedInput.toLowerCase()) {
            return;
        }

        activeController = null;

        suggestions.value = data.map((student: Student) => `${student.first_name} ${student.second_name}`);
        showSuggestions.value = suggestions.value.length > 0;

        const normalizedInput = trimmedInput.toLowerCase();
        isValidStudent.value = data.some((student: Student) => {
            const fullName = `${student.first_name} ${student.second_name}`.toLowerCase();
            const reverseName = `${student.second_name} ${student.first_name}`.toLowerCase();

            return normalizedInput === fullName ||
                normalizedInput === reverseName ||
                normalizedInput === student.first_name.toLowerCase() ||
                normalizedInput === student.second_name.toLowerCase();
        });
    } catch (error: any) {
        activeController = null;
        if (error?.name === 'AbortError') return;
        console.error('Fetch error:', error);
        suggestions.value = [];
        showSuggestions.value = false;
        isValidStudent.value = false;
    }
}, { flush: 'post' });

function selectSuggestion(name: string) {
    form.student_name = name;
    showSuggestions.value = false;
    isValidStudent.value = true;
}

function submit() {
    //csakj db-ben létező studentet lehet
    if (!form.student_name.trim() || !isValidStudent.value) return;
    console.log('Submitting:', form.student_name);
    emit('guessAttempted', {
        name: form.student_name,
        isValid: isValidStudent.value
    });
    form.post('/guess-student', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            suggestions.value = [];
            form.mode = props.mode;
            emit('validGuess', { isValid: true });
        },
    });
}

const { highlightedIndex, handleKeyDown } = useKeyboardNavigation(
    suggestions,
    showSuggestions,
    selectSuggestion,
    submit,
    isValidStudent,
    toRef(form, 'student_name')
);

function handleBlur() {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 100);
}

</script>

<template>
    <!-- form -->
    <div class="pt-4 flex justify-center">
        <form @submit.prevent="submit" class="relative" novalidate>
            <input autocomplete="off" spellcheck="false" v-model="form.student_name" type="text" placeholder="Type here..."
                class="guess-input -skew-x-12" @blur="handleBlur" @focus="showSuggestions = true"
                @keydown="handleKeyDown" />
            <!--dropdown-->
            <div v-if="showSuggestions && suggestions.length"
                class="suggestion max-w-[14rem] absolute mt-1 w-full border rounded shadow-lg max-h-30 overflow-auto z-50">
                <!--active selected-->
                <div v-for="(name, index) in suggestions" :key="index"
                    class="px-3 py-2 hover:bg-gray-500 cursor-pointer"
                    :class="{ 'bg-gray-600 text-white': index === highlightedIndex }"
                    @mouseenter="highlightedIndex = index"
                    @mousedown="selectSuggestion(name)">
                    {{ name }}
                </div>
            </div>
            <button type="submit" :disabled="form.processing"
                class="guess-button -skew-x-12">
                <p class="skew-x-12">Guess</p>
            </button>
        </form>
    </div>
</template>
