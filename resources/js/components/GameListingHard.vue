<script setup lang="ts">
import { computed } from 'vue';
import type { HeightStatus, History } from '@/types/history';
import type { Student } from '@/types/student';
import { cardTextClass, equipmentIcons, equipmentLabel, equipmentsMatch, matchHandler, formatStudentValue, attributeDotClass } from '@/composables/GameHelpers';
import type { Clue } from '@/types';
import { getSchoolLogo } from '@/types/student';
import { useCardAnimation } from '@/composables/cardAnimation';

const props = defineProps<{
  attempts: number;
  dailyStudent: Student;
  guessHistoryHard: History[];
  matches?: Record<string, any>;
  heightStatus?: HeightStatus;
  hardModeClues?: Clue[];
  animate?: boolean;
}>();

const emit = defineEmits<{
  (e: 'animationComplete', rowId: number): void;
}>();

const reversedHistory = computed(() => (props.guessHistoryHard || []).slice().reverse());
const hardModeClues = computed(() => props.hardModeClues ?? []);
const attemptCount = computed(() => props.attempts ?? reversedHistory.value.length);

const visibleClues = computed(() => hardModeClues.value.slice(1));

const { registerRowRef, shouldAnimateRow } = useCardAnimation(
  computed(() => Boolean(props.animate)),
  reversedHistory,
  rowId => emit('animationComplete', rowId)
);

// thresholds map to visibleClues indices (starting from original index 1)
// original: [1]=hard, [2]=hard, [3]=hard, [4]=medium, [5]=medium, [6]=easy
const revealThresholds = [0, 0, 0, 5, 6, 10];

function isClueRevealed(iVisible: number, attemptNum: number) {
  const t = revealThresholds[iVisible] ?? 0;
  return attemptNum >= t;
}

const heightIndicatorClass = (status?: HeightStatus) => {
  if (!status) return 'card-height';
  return `card-height card-height-${status}`;
};


const clueCardClass = (student: History, cluePair: keyof Student, revealed: boolean) => {
  if (!revealed) {
    return 'bg-black text-white';
  }
  if (cluePair === 'equipment_1') {
    return equipmentsMatch(student.matches, ['equipment_1', 'equipment_2', 'equipment_3'])
      ? 'bg-cyan-500'
      : 'bg-pink-700';
  }
  return matchHandler(
    student.matches,
    cluePair as string,
    cluePair === 'height' ? student.heightStatus : undefined
  );
};

const resolveSchoolLogo = (school: Student['school']) => getSchoolLogo(school);

</script>

<template>
  <div class="overflow-x-auto">
    <table v-if="reversedHistory.length > 0" class="m-[0.5rem] listing-table mx-auto">
      <thead class="text-sm md:text-base tracking-wide listing-head">
        <tr>
          <th><span class="th-underline">Student</span></th>
          <th v-for="(clue, i) in visibleClues" :key="`h-${i}`">
            <span class="th-underline" v-if="isClueRevealed(i, attemptCount)">{{ clue.label }}</span>
            <span class="th-underline" v-else>???</span>
          </th>
        </tr>
      </thead>

      <tbody class="text-center">
        <tr
          v-for="(student, index) in reversedHistory"
          :key="student.id"
          :ref="(el) => registerRowRef(student.id, el as HTMLTableRowElement | null)"
        >
          <td class="p-2">
            <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
              <div class="card-border shape-border">
                <div class="student-card">
                  <img :src="student.image" :alt="`${student.first_name} ${student.second_name}`" />
                </div>
              </div>
            </div>
          </td>

          <td
            v-for="(clue, i) in visibleClues"
            :key="`c-${i}`"
            class="p-2"
          >
            <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
              <div class="card-border shape-border">
                <div class="clue-card"
                     :class="[
                       clueCardClass(student, clue.pair, isClueRevealed(i, reversedHistory.length - index)),
                       clue.pair === 'height' && isClueRevealed(i, reversedHistory.length - index)
                         ? heightIndicatorClass(student.heightStatus)
                         : ''
                     ]">
                  <template v-if="!isClueRevealed(i, reversedHistory.length - index)">
                    <p class="card-content">???</p>
                  </template>
                  <template v-else>
                    <template v-if="clue.pair === 'equipment_1'">
                      <template v-if="equipmentIcons(student).length">
                        <div class="equipment-icon">
                          <img
                            v-for="icon in equipmentIcons(student)"
                            :key="`${student.id}-${icon.name}`"
                            :src="icon.icon"
                            :alt="icon.name"
                          />
                        </div>
                      </template>
                      <template v-else>
                        <p :class="cardTextClass(equipmentLabel(student))">
                          {{ equipmentLabel(student) }}
                        </p>
                      </template>
                    </template>
                    <template v-else-if="clue.pair === 'height'">
                      <p :class="cardTextClass(student.height)">
                        {{ student.height }}
                      </p>
                    </template>
                    <template v-else-if="clue.pair === 'school' && resolveSchoolLogo(student.school)">
                      <img :src="resolveSchoolLogo(student.school) ?? ''"
                           :alt="student.school"
                           class="school-logo"
                           :data-school="student.school" />
                    </template>
                    <template v-else>
                      <p
                        :class="[
                          cardTextClass(formatStudentValue(student, clue.pair)),
                          attributeDotClass(clue.pair, formatStudentValue(student, clue.pair))
                        ]"
                      >
                        <span>
                          {{ formatStudentValue(student, clue.pair) ?? '-' }}
                        </span>
                      </p>
                    </template>
                  </template>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
