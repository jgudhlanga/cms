<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseCheckbox } from '@/components/core/form';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { normalizeGender } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import { getInitials } from '@/composables/core/useInitials';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { buildStudentShowUrl, currentPageReturnPath } from '@/lib/studentShowNavigation';
import type { AcademicCalendarClassMoveTarget, AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import { Link as InertiaLink, usePage } from '@inertiajs/vue3';
import { UserIcon, UserRoundIcon } from 'lucide-vue-next';

defineProps<{
    sortedStudents: AcademicCalendarClassPreviewStudent[];
    canMoveStudents: boolean;
    canViewCourseWork: boolean;
    canAdvancePhase: boolean;
    canCompleteLevel: boolean;
    advancePhaseDisabled?: boolean;
    advancePhaseBlockReason?: string;
    moveTargetClasses: AcademicCalendarClassMoveTarget[];
    studentCourseWorkUrl: (student: AcademicCalendarClassPreviewStudent) => string;
}>();

const page = usePage();

const studentShowUrl = (studentId: number) =>
    buildStudentShowUrl(studentId, {
        from: 'academic-calendar',
        return: currentPageReturnPath(page.url, window.location.origin),
    });

const emit = defineEmits<{
    toggleSelectAll: [];
    selectAllKeydown: [event: KeyboardEvent];
    openMoveStudents: [studentEnrolmentId?: number];
    advancePhase: [];
    completeLevel: [];
    removeStudent: [student: AcademicCalendarClassPreviewStudent];
}>();

const selectedStudentEnrolmentIds = defineModel<number[]>('selectedStudentEnrolmentIds', { required: true });
const selectAllChangeClassModel = defineModel<boolean>('selectAllChangeClassModel', { required: true });

const genderLetter = (gender: string | null | undefined): string => {
    const normalized = normalizeGender(gender);
    if (normalized === 'female') {
        return 'F';
    }
    if (normalized === 'male') {
        return 'M';
    }

    return '—';
};

const avatarClasses = (gender: string | null | undefined): string => {
    const normalized = normalizeGender(gender);
    if (normalized === 'female') {
        return 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300';
    }
    if (normalized === 'male') {
        return 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300';
    }

    return 'bg-muted text-muted-foreground';
};
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">#</th>
                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.gender', 1) }}</th>
                <th v-if="canMoveStudents" class="j-th text-center">
                    <span class="text-xs font-semibold uppercase">{{ $t('academic_calendar.move_to_another_class') }}</span>
                </th>
                <th class="j-th w-10 text-right">{{ $tChoice('trans.action', 2) }}</th>
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
                <td class="j-td" colspan="3">
                    <span class="text-xs font-semibold uppercase">{{ $t('trans.select_all') }}</span>
                </td>
                <td class="j-td text-center" @click.stop>
                    <div class="flex justify-center">
                        <BaseCheckbox v-model="selectAllChangeClassModel" input-id="select_all_change_class" :label="''" />
                    </div>
                </td>
                <td class="j-td text-right" @click.stop>
                    <div class="flex flex-nowrap items-center justify-end gap-1.5">
                        <span
                            v-if="canAdvancePhase && selectedStudentEnrolmentIds.length > 0"
                            class="inline-flex"
                            :title="advancePhaseDisabled ? advancePhaseBlockReason || $t('academic_calendar.advance_phase_none') : undefined"
                            @click="emit('advancePhase')"
                        >
                            <BaseButton
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.primary"
                                type="button"
                                :disabled="advancePhaseDisabled"
                                classes="whitespace-nowrap rounded-full pointer-events-none"
                            >
                                {{ $t('academic_calendar.continue_next_phase') }}
                            </BaseButton>
                        </span>
                        <BaseButton
                            v-if="canCompleteLevel && selectedStudentEnrolmentIds.length > 0"
                            :size="ButtonSize.xs"
                            :variant="ColorVariant.success"
                            type="button"
                            classes="whitespace-nowrap rounded-full"
                            @click="emit('completeLevel')"
                        >
                            {{ $t('academic_calendar.mark_level_completed') }}
                        </BaseButton>
                        <BaseButton
                            v-if="selectedStudentEnrolmentIds.length > 0 && moveTargetClasses.length > 0"
                            :size="ButtonSize.xs"
                            :variant="ColorVariant.danger"
                            type="button"
                            classes="whitespace-nowrap rounded-full"
                            @click="emit('openMoveStudents')"
                        >
                            {{ $t('academic_calendar.move_students') }}
                        </BaseButton>
                    </div>
                </td>
            </tr>
            <tr v-for="(student, index) in sortedStudents" :key="student.studentEnrolmentId" class="j-tr">
                <td class="j-td py-1">{{ index + 1 }}</td>
                <td class="j-td py-1">
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold"
                            :class="avatarClasses(student.gender)"
                        >
                            {{ getInitials(student.name) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ student.name }}</p>
                            <p class="truncate text-[10px] leading-tight text-muted-foreground">
                                {{ student.studentNumber ?? '---' }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="j-td py-1 text-center">
                    <span class="inline-flex items-center gap-0.5 text-xs">
                        <UserRoundIcon v-if="normalizeGender(student.gender) === 'female'" class="h-3.5 w-3.5 text-pink-600" />
                        <UserIcon v-else-if="normalizeGender(student.gender) === 'male'" class="h-3.5 w-3.5 text-blue-600" />
                        <UserIcon v-else class="h-3.5 w-3.5 text-gray-500" />
                        <span>{{ genderLetter(student.gender) }}</span>
                    </span>
                </td>
                <td v-if="canMoveStudents" class="j-td py-1 text-center">
                    <BaseCheckbox
                        :input-id="`change_class_${student.studentEnrolmentId}`"
                        v-model="selectedStudentEnrolmentIds"
                        :value="student.studentEnrolmentId"
                    />
                </td>
                <td class="j-td py-1 text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
                                :aria-label="$tChoice('trans.action', 2)"
                            >
                                <component :is="icons[IconName.dots_vertical]" class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="min-w-40">
                            <DropdownMenuGroup>
                                <DropdownMenuItem as-child>
                                    <InertiaLink :href="studentShowUrl(student.studentId)" class="cursor-pointer">
                                        {{ $t('academic_calendar.view_profile') }}
                                    </InertiaLink>
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="canViewCourseWork" as-child>
                                    <InertiaLink :href="studentCourseWorkUrl(student)" class="cursor-pointer">
                                        {{ $tChoice('academic_calendar.course_work', 1) }}
                                    </InertiaLink>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="canMoveStudents && moveTargetClasses.length > 0"
                                    class="cursor-pointer"
                                    @click="emit('openMoveStudents', student.studentEnrolmentId)"
                                >
                                    {{ $t('academic_calendar.move_class') }}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="canMoveStudents"
                                    class="cursor-pointer text-destructive"
                                    @click="emit('removeStudent', student)"
                                >
                                    {{ $t('academic_calendar.remove_from_class') }}
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </td>
            </tr>
        </tbody>
    </table>
</template>
