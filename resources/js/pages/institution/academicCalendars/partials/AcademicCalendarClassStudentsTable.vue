<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import { normalizeGender } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import type { AcademicCalendarClassMoveTarget, AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { UserIcon, UserRoundIcon } from 'lucide-vue-next';

defineProps<{
    sortedStudents: AcademicCalendarClassPreviewStudent[];
    canMoveStudents: boolean;
    moveTargetClasses: AcademicCalendarClassMoveTarget[];
}>();

const emit = defineEmits<{
    toggleSelectAll: [];
    selectAllKeydown: [event: KeyboardEvent];
    openMoveStudents: [];
}>();

const selectedStudentProgramIds = defineModel<number[]>('selectedStudentProgramIds', { required: true });
const selectAllChangeClassModel = defineModel<boolean>('selectAllChangeClassModel', { required: true });
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">#</th>
                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.gender', 1) }}</th>
                <th v-if="canMoveStudents" class="j-th text-center">
                    <span class="text-xs font-semibold uppercase">{{ $t('academic_calendar.move_to_another_class') }}</span>
                </th>
                <th class="j-th text-right">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <tr
                v-if="canMoveStudents"
                class="j-tr cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50"
                role="button"
                tabindex="0"
                :aria-label="$t('trans.select_all')"
                @click="emit('toggleSelectAll')"
                @keydown="emit('selectAllKeydown', $event)"
            >
                <td class="j-td" colspan="4">
                    <span class="text-xs font-semibold uppercase">{{ $t('trans.select_all') }}</span>
                </td>
                <td class="j-td text-center" @click.stop>
                    <div class="flex justify-center">
                        <BaseCheckbox v-model="selectAllChangeClassModel" input-id="select_all_change_class" :label="''" />
                    </div>
                </td>
                <td class="j-td text-right" @click.stop>
                    <BaseButton
                        v-if="selectedStudentProgramIds.length > 0 && moveTargetClasses.length > 0"
                        :size="ButtonSize.xs"
                        :variant="ColorVariant.danger"
                        type="button"
                        classes="rounded-full"
                        @click="emit('openMoveStudents')"
                    >
                        {{ $t('academic_calendar.move_students') }}
                    </BaseButton>
                </td>
            </tr>
            <tr v-for="(student, index) in sortedStudents" :key="student.studentProgramId" class="j-tr">
                <td class="j-td">{{ index + 1 }}</td>
                <td class="j-td">{{ student.name }}</td>
                <td class="j-td">{{ student.studentNumber ?? '---' }}</td>
                <td class="j-td text-center">
                    <span class="inline-flex items-center gap-1">
                        <UserRoundIcon v-if="normalizeGender(student.gender) === 'female'" class="h-4 w-4 text-pink-600" />
                        <UserIcon v-else-if="normalizeGender(student.gender) === 'male'" class="h-4 w-4 text-blue-600" />
                        <UserIcon v-else class="h-4 w-4 text-gray-500" />
                    </span>
                </td>
                <td v-if="canMoveStudents" class="j-td text-center">
                    <BaseCheckbox
                        :input-id="`change_class_${student.studentProgramId}`"
                        v-model="selectedStudentProgramIds"
                        :value="student.studentProgramId"
                    />
                </td>
                <td class="j-td text-right">
                    <InertiaLink :href="route('students.profile', String(student.studentId))">
                        <BaseButton
                            :size="ButtonSize.xs"
                            :variant="ColorVariant.success"
                            :title="$tChoice('students.profile', 1)"
                            classes="rounded-full"
                        />
                    </InertiaLink>
                </td>
            </tr>
        </tbody>
    </table>
</template>
