import { nextTick, ref, watch, type ComputedRef, type Ref } from 'vue';
import type { History } from '@/types/history';
import { preloadStudentAssets } from '@/composables/GameHelpers';

type RowState = 'visible' | 'pending';

export function useCardAnimation(
  animate: Ref<boolean>,
  rows: ComputedRef<History[]>,
  onRowAnimationComplete?: (rowId: number) => void
) {
    const rowRefs = ref(new Map<number, HTMLTableRowElement>());
    const rowStates = ref<Record<number, RowState>>({});
    const hasHydrated = ref(false);
    const runningAnimations = new Set<number>();

    watch(
        rows,
        (students, prevStudents) => {
            if (!hasHydrated.value) {
                const initial: Record<number, RowState> = {};
                students.forEach((student) => {
                    initial[student.id] = 'visible';
                });
                rowStates.value = initial;
                hasHydrated.value = true;
                return;
            }

            const next = { ...rowStates.value };
            const currentIds = new Set(students.map((s) => s.id));
            Object.keys(next).forEach((id) => {
                const numericId = Number(id);
                if (!currentIds.has(numericId)) {
                    delete next[numericId];
                    runningAnimations.delete(numericId);
                }
            });

            const previous = prevStudents ?? [];
            const previousIds = new Set(previous.map((s) => s.id));

            students.forEach((student, index) => {
                if (next[student.id]) return;
                const isNewRow = !previousIds.has(student.id);
                const shouldAnimate = isNewRow && animate.value && index === 0;
            next[student.id] = shouldAnimate ? 'pending' : 'visible';
            if (shouldAnimate) {
                startRowAnimation(student.id);
            }
            });

            rowStates.value = next;
        },
        { immediate: true }
    );

    watch(
        animate,
        (enabled) => {
            if (!enabled) return;
            const latest = rows.value?.[0];
            if (!latest) return;
            if (rowStates.value[latest.id] !== 'pending') {
                rowStates.value = { ...rowStates.value, [latest.id]: 'pending' };
                startRowAnimation(latest.id);
            }
        }
    );

    function registerRowRef(id: number, el: HTMLTableRowElement | null) {
        if (!el) {
            rowRefs.value.delete(id);
        } else {
            rowRefs.value.set(id, el);
        }
    }

    async function startRowAnimation(rowId: number) {
        if (runningAnimations.has(rowId)) return;
        runningAnimations.add(rowId);
        try {
            let rowEl = rowRefs.value.get(rowId);
            if (!rowEl) {
                await nextTick();
                rowEl = rowRefs.value.get(rowId);
            }
            const student = rows.value.find((s) => s.id === rowId);
            if (!rowEl || !student) {
                rowStates.value = { ...rowStates.value, [rowId]: 'visible' };
                onRowAnimationComplete?.(rowId);
                return;
            }

            await preloadStudentAssets(student, 3000);
            const entries = Array.from(rowEl.querySelectorAll<HTMLElement>('.card-shadow'));
            const [first, ...rest] = entries;
            if (first) {
                await runEntryAnimation(first);
            }
            for (const entry of rest) {
                await runEntryAnimation(entry);
            }
            rowStates.value = { ...rowStates.value, [rowId]: 'visible' };
            onRowAnimationComplete?.(rowId);
        } finally {
            runningAnimations.delete(rowId);
        }
    }

    function runEntryAnimation(entry?: HTMLElement) {
        if (!entry) return Promise.resolve();
        entry.classList.remove('entry-fade-in', 'entry-visible');
        void entry.offsetWidth;
        return new Promise<void>((resolve) => {
            entry.addEventListener(
                'animationend',
                () => {
                    entry.classList.remove('entry-fade-in');
                    entry.classList.add('entry-visible');
                    resolve();
                },
                { once: true }
            );
            entry.classList.add('entry-fade-in');
        });
    }

    function shouldAnimateRow(studentId: number) {
        return rowStates.value[studentId] === 'pending';
    }

    return {
        registerRowRef,
        shouldAnimateRow
    };
}
