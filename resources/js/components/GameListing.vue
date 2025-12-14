<script setup lang="ts">
import { computed } from 'vue';
import type { HeightStatus, History } from '@/types/history';
import { cardTextClass, equipmentIcons, equipmentLabel, equipmentsMatch, matchHandler } from '@/composables/GameHelpers';
import { getSchoolLogo } from '@/types/student';
import { useCardAnimation } from '@/composables/cardAnimation';

const props = defineProps<{
    guessHistoryClassic: History[];
    matches?: Record<string, any>;
    heightStatus?: HeightStatus;
    animate?: boolean;
}>();

const emit = defineEmits<{
    (e: 'animationComplete', rowId: number): void;
}>();

const reversedHistory = computed(() => (props.guessHistoryClassic || []).slice().reverse());

const { registerRowRef, shouldAnimateRow } = useCardAnimation(
    computed(() => Boolean(props.animate)),
    reversedHistory,
    (rowId) => emit('animationComplete', rowId)
);

const heightIndicatorClass = (status?: HeightStatus) => {
    if (!status) return 'card-height';
    return `card-height card-height-${status}`;
};
</script>

<template>
    <div class="overflow-x-auto">
        <table v-if="reversedHistory.length !== 0" class="m-[0.5rem] listing-table mx-auto">
            <thead class="text-sm md:text-base tracking-wide listing-head">
                <tr>
                    <th><span class="th-underline">Student</span></th>
                    <th><span class="th-underline">Role</span></th>
                    <th><span class="th-underline">School</span></th>
                    <th><span class="th-underline">Club</span></th>
                    <th><span class="th-underline">Age</span></th>
                    <th><span class="th-underline">Equipment</span></th>
                    <th><span class="th-underline">Height</span></th>
                </tr>
            </thead>
            
            <tbody class="text-center">
                <tr
                    v-for="student in reversedHistory"
                    :key="student.id"
                    :ref="(el) => registerRowRef(student.id, el as HTMLTableRowElement | null)">
                    <td class="p-2">
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div class="student-card">
                                    <img :src="student.image" :alt="`${student.first_name} ${student.second_name}`" />
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div :class="['clue-card', matchHandler(student.matches, 'role')]">
                                    <p :class="cardTextClass(student.role)">{{ student.role }}</p>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div :class="['clue-card', matchHandler(student.matches, 'school')]">
                                    <template v-if="getSchoolLogo(student.school)">
                                        <img :src="getSchoolLogo(student.school)" :alt="student.school"
                                            class="school-logo" :data-school="student.school" />
                                    </template>
                                    <template v-else>
                                        <p :class="cardTextClass(student.school)">{{ student.school }}</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div :class="['clue-card', matchHandler(student.matches, 'club')]">
                                    <p :class="cardTextClass(student.club)">{{ student.club }}</p>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div :class="['clue-card', matchHandler(student.matches, 'age')]">
                                    <p :class="cardTextClass(student.age)">{{ student.age }}</p>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div
                                    :class="['clue-card', equipmentsMatch(student.matches, ['equipment_1', 'equipment_2', 'equipment_3']) ? 'bg-cyan-500' : 'bg-pink-700']">
                                    <template v-if="equipmentIcons(student).length">
                                        <div class="equipment-icon">
                                            <img v-for="icon in equipmentIcons(student)" :key="`${student.id}-${icon.name}`"
                                                :src="icon.icon" :alt="icon.name" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p :class="cardTextClass(equipmentLabel(student))">
                                            {{ equipmentLabel(student) }}
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div :class="['card-shadow', shouldAnimateRow(student.id) ? 'card-entry' : 'entry-visible']">
                            <div class="card-border shape-border">
                                <div
                                    :class="['clue-card', matchHandler(student.matches, 'height', student.heightStatus), heightIndicatorClass(student.heightStatus)]">
                                    <p :class="cardTextClass(student.height)">
                                        {{ student.height }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
