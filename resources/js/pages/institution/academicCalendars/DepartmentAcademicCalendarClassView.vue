<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { useModalStore } from '@/store/core/useModalStore';
import {
    AcademicCalendar,
    AcademicCalendarClassDetail,
    AcademicCalendarClassMoveTarget,
    ClassConfig,
} from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head, Link as InertiaLink, useForm } from '@inertiajs/vue3';
import { UserIcon, UserRoundIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { hasAbility } from '@/lib/permissions';

const MOVE_STUDENTS_MODAL = 'academic_calendar_move_students';

const props = withDefaults(
    defineProps<{
        department: InstitutionDepartment;
        academicCalendar: AcademicCalendar;
        course: DepartmentCourse;
        level: DepartmentLevel;
        mode: ModeOfStudy;
        classConfig: ClassConfig | null;
        academicCalendarClass: AcademicCalendarClassDetail;
        moveTargetClasses: AcademicCalendarClassMoveTarget[];
    }>(),
    {
        moveTargetClasses: () => [],
    },
);

const { department, academicCalendar, academicCalendarClass, course, level, mode, classConfig } = props;

const { openModal, closeModal } = useModalStore();

const selectedStudentProgramIds = ref<number[]>([]);

const moveForm = useForm({
    student_program_ids: [] as number[],
    target_academic_calendar_class_id: null as number | null,
});

const departmentClassesUrl = computed(() =>
    route('academic-calendars.department-classes', {
        institution_department: String(department.id),
        academic_calendar: String(academicCalendar.id),
        department_level_id: String(level.id),
        department_course_id: String(course.id),
        mode_of_study_id: String(mode.id),
        ...(classConfig?.id ? { class_config_id: String(classConfig.id) } : {}),
    }),
);

const normalizeGender = (gender: string | null | undefined): 'female' | 'male' | 'unknown' => {
    const normalized = String(gender ?? '').trim().toLowerCase();

    if (normalized.includes('female')) {
        return 'female';
    }

    if (normalized.includes('male')) {
        return 'male';
    }

    return 'unknown';
};

const sortedStudents = computed(() => {
    return [...academicCalendarClass.students].sort((a, b) => {
        const genderPriority: Record<'female' | 'male' | 'unknown', number> = {
            female: 0,
            male: 1,
            unknown: 2,
        };

        const genderSort = genderPriority[normalizeGender(a.gender)] - genderPriority[normalizeGender(b.gender)];

        if (genderSort !== 0) {
            return genderSort;
        }

        return a.name.localeCompare(b.name);
    });
});

const selectAllChangeClassModel = computed({
    get() {
        const list = sortedStudents.value;
        if (list.length === 0) {
            return false;
        }
        return selectedStudentProgramIds.value.length === list.length;
    },
    set(checked: boolean) {
        if (checked) {
            selectedStudentProgramIds.value = sortedStudents.value.map((s) => s.studentProgramId);
        } else {
            selectedStudentProgramIds.value = [];
        }
    },
});

const breadcrumbs = computed<Array<Link>>(() => {
    const departmentShowUrl = route('institution-departments.show', String(department.id));

    return [
        { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
        { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
        { title: department.attributes.departmentCode, href: departmentShowUrl },
        { title: level.attributes.level, href: departmentShowUrl },
        { title: course.attributes.course, href: departmentShowUrl },
        { title: mode.attributes.name, href: departmentShowUrl },
        { transChoiceKey: 'class', href: departmentClassesUrl.value },
        { title: academicCalendarClass.name },
    ];
});

const moveStudentsUrl = computed(() =>
    route('academic-calendars.department-classes.move-students', {
        institution_department: String(department.id),
        academic_calendar: String(academicCalendar.id),
        academic_calendar_class: String(academicCalendarClass.id),
    }),
);

const openMoveStudentsModal = () => {
    if (props.moveTargetClasses.length === 0) {
        return;
    }
    moveForm.student_program_ids = [...selectedStudentProgramIds.value];
    moveForm.target_academic_calendar_class_id = props.moveTargetClasses[0]?.id ?? null;
    moveForm.clearErrors();
    openModal(MOVE_STUDENTS_MODAL);
};

const submitMoveStudents = () => {
    moveForm.post(moveStudentsUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal(MOVE_STUDENTS_MODAL);
            selectedStudentProgramIds.value = [];
        },
    });
};

const resetMoveFormOnModalClose = () => {
    moveForm.clearErrors();
};

const toggleSelectAllChangeClassFromRow = (): void => {
    const list = sortedStudents.value;
    if (list.length === 0) {
        return;
    }
    if (selectedStudentProgramIds.value.length === list.length) {
        selectedStudentProgramIds.value = [];
    } else {
        selectedStudentProgramIds.value = list.map((s) => s.studentProgramId);
    }
};

const onSelectAllRowKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleSelectAllChangeClassFromRow();
    }
};

const canMoveStudents = () => hasAbility(['update:academic-calendar-student-programs']);
</script>

<template>
    <Head :title="academicCalendarClass.name" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentClassesUrl">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="academicCalendarClass.name">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <LabelValue :label="$tChoice('trans.student', 2)" :value="String(academicCalendarClass.studentCount)" />
                    <LabelValue :label="$t('academic_calendar.description')" :value="academicCalendarClass.description ?? '---'" />
                </div>
            </BaseCard>

            <BaseCard title="Class metadata">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <LabelValue v-for="meta in academicCalendarClass.metadata" :key="meta.key" :label="meta.label" :value="meta.value || '---'" />
                </div>
            </BaseCard>
            <table class="j-table">
                <thead class="j-thead">
                    <tr class="j-th">
                        <th class="j-th text-left">#</th>
                        <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                        <th class="j-th text-left">{{ $tChoice('students.student_number', 1) }}</th>
                        <th class="j-th text-center">{{ $tChoice('trans.gender', 1) }}</th>
                        <th v-if="canMoveStudents()" class="j-th text-center">
                            <span class="text-xs font-semibold uppercase">{{ $t('academic_calendar.change_class') }}</span>
                        </th>
                        <th class="j-th text-right">{{ $tChoice('trans.action', 2) }}</th>
                    </tr>
                </thead>
                <tbody class="j-tbody">
                    <tr
                        v-if="canMoveStudents()"
                        class="j-tr cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        role="button"
                        tabindex="0"
                        :aria-label="$t('trans.select_all')"
                        @click="toggleSelectAllChangeClassFromRow"
                        @keydown="onSelectAllRowKeydown"
                    >
                        <td class="j-td" colspan="4">
                            <span class="text-xs font-semibold uppercase">{{ $t('trans.select_all') }}</span>
                        </td>
                        <td class="j-td text-center" @click.stop>
                            <div class="flex justify-center">
                                <BaseCheckbox
                                    v-model="selectAllChangeClassModel"
                                    input-id="select_all_change_class"
                                    :label="''"
                                />
                            </div>
                        </td>
                        <td class="j-td text-right" @click.stop>
                            <BaseButton
                                v-if="selectedStudentProgramIds.length > 0 && moveTargetClasses.length > 0"
                                :size="ButtonSize.sm"
                                :variant="ColorVariant.danger"
                                type="button"
                                classes="rounded-full"
                                @click="openMoveStudentsModal"
                            >
                                {{ $t('academic_calendar.move_students') }}
                            </BaseButton>
                        </td>
                    </tr>
                    <tr class="j-tr" v-for="(student, index) in sortedStudents" :key="student.studentProgramId">
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
                        <td v-if="canMoveStudents()" class="j-td text-center">
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
            <BaseModal
                v-if="canMoveStudents()"
                :name="MOVE_STUDENTS_MODAL"
                :title="$t('academic_calendar.move_students_modal_title')"
                :form="moveForm"
                :on-form-action="submitMoveStudents"
                :on-close-modal="resetMoveFormOnModalClose"
                :size="SizeVariant.sm"
                cancel-btn-text="trans.close"
                action-btn-text="academic_calendar.move_students_submit"
            >
                <template #body>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium uppercase" for="move_target_class">{{ $t('academic_calendar.move_students_select_target') }}</label>
                        <select
                            id="move_target_class"
                            v-model.number="moveForm.target_academic_calendar_class_id"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                            :class="{ 'border-red-500': moveForm.errors.target_academic_calendar_class_id }"
                        >
                            <option v-for="c in moveTargetClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="moveForm.errors.target_academic_calendar_class_id" class="text-sm text-red-600">
                            {{ moveForm.errors.target_academic_calendar_class_id }}
                        </p>
                        <p v-if="moveForm.errors.student_program_ids" class="text-sm text-red-600">{{ moveForm.errors.student_program_ids }}</p>
                    </div>
                </template>
            </BaseModal>
        </div>
    </PageContainer>
</template>
