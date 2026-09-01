<script setup lang="ts">
import AcademicCalendarClassNavComboSelect from '@/components/academicCalendars/AcademicCalendarClassNavComboSelect.vue';
import AssignClassTutorModal from '@/components/academicCalendars/AssignClassTutorModal.vue';
import ClassListExportModal from '@/components/academicCalendars/ClassListExportModal.vue';
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import { openAssignClassTutorModal } from '@/composables/academicCalendars/useAcademicCalendarClassTutor';
import { ADD_STUDENTS_MODAL, useAcademicCalendarClassAddStudents } from '@/composables/academicCalendars/useAcademicCalendarClassAddStudents';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { EDIT_CLASS_MODAL, useAcademicCalendarClassEdit } from '@/composables/academicCalendars/useAcademicCalendarClassEdit';
import { openClassListExportModal } from '@/composables/academicCalendars/useClassListExport';
import { MOVE_STUDENTS_MODAL, useAcademicCalendarClassMoveStudents } from '@/composables/academicCalendars/useAcademicCalendarClassMoveStudents';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { useAcademicCalendarClassStudentFilters } from '@/composables/academicCalendars/useAcademicCalendarClassStudentFilters';
import { useAcademicCalendarClassStudentSelection } from '@/composables/academicCalendars/useAcademicCalendarClassStudentSelection';
import { useAcademicCalendarClassStudents } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import { useDepartmentAcademicCalendarClassNavigation } from '@/composables/academicCalendars/useDepartmentAcademicCalendarClassNavigation';
import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { hasAbility } from '@/lib/permissions';
import { AcademicCalendar, AcademicCalendarClassDetail, AcademicCalendarClassMoveTarget, AcademicCalendarClassPreviewStudent, ClassConfig, ClassSemesterModule } from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, toRefs, watch } from 'vue';
import AcademicCalendarClassStudentFilters from './partials/AcademicCalendarClassStudentFilters.vue';
import AcademicCalendarClassHeaderCard from './partials/AcademicCalendarClassHeaderCard.vue';
import AcademicCalendarClassModulesPanel from './partials/AcademicCalendarClassModulesPanel.vue';
import AcademicCalendarClassStudentsTable from './partials/AcademicCalendarClassStudentsTable.vue';
import AddAcademicCalendarClassStudentsModal from './partials/AddAcademicCalendarClassStudentsModal.vue';
import EditAcademicCalendarClassModal from './partials/EditAcademicCalendarClassModal.vue';
import MoveAcademicCalendarStudentsModal from './partials/MoveAcademicCalendarStudentsModal.vue';

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
        siblingAcademicCalendarClasses: AcademicCalendarClassMoveTarget[];
        canUpdateAcademicCalendarClass?: boolean;
        canViewCourseWork?: boolean;
        canExportClassList?: boolean;
        semesterModules?: ClassSemesterModule[];
        selectedSemesterId?: number | null;
        calendarType?: 'term' | 'semester' | 'abma';
        semesterConfigHasSyllabi?: boolean;
        canAssignStaffing?: boolean;
        isLastProgrammePhase?: boolean;
        unassignedStudents?: AcademicCalendarClassPreviewStudent[];
    }>(),
    {
        moveTargetClasses: () => [],
        siblingAcademicCalendarClasses: () => [],
        unassignedStudents: () => [],
        canUpdateAcademicCalendarClass: false,
        canViewCourseWork: false,
        canExportClassList: false,
        semesterModules: () => [],
        selectedSemesterId: null,
        calendarType: 'semester',
        semesterConfigHasSyllabi: false,
        canAssignStaffing: false,
        isLastProgrammePhase: false,
    },
);

const { department, academicCalendar, academicCalendarClass, course, level, mode, classConfig, moveTargetClasses, siblingAcademicCalendarClasses, selectedSemesterId } =
    toRefs(props);

const { departmentClassesUrl, moveStudentsUrl, addStudentsUrl, removeStudentsUrl, advancePhaseUrl, completeLevelUrl, updateClassUrl, breadcrumbs, studentCourseWorkUrl, classConfigQuery } =
    useDepartmentAcademicCalendarClassNavigation(
    department,
    academicCalendar,
    course,
    level,
    mode,
    classConfig,
    academicCalendarClass,
);

const { sortedStudents } = useAcademicCalendarClassStudents(academicCalendarClass);

const { filters, filteredStudents, onFiltersChange } = useAcademicCalendarClassStudentFilters(sortedStudents);

const { selectedStudentEnrolmentIds, selectAllChangeClassModel, toggleSelectAllChangeClassFromRow, onSelectAllRowKeydown } =
    useAcademicCalendarClassStudentSelection(filteredStudents);

watch(filteredStudents, (students) => {
    const visibleIds = new Set(students.map((student) => student.studentEnrolmentId));
    selectedStudentEnrolmentIds.value = selectedStudentEnrolmentIds.value.filter((id) => visibleIds.has(id));
});

const { moveForm, openMoveStudentsModal, submitMoveStudents, resetMoveFormOnModalClose } = useAcademicCalendarClassMoveStudents(
    moveStudentsUrl,
    moveTargetClasses,
    selectedStudentEnrolmentIds,
);

const { addStudentsForm, openAddStudentsModal, submitAddStudents, resetAddStudentsFormOnModalClose } = useAcademicCalendarClassAddStudents(
    addStudentsUrl,
);

const { editClassForm, openEditClassModal, submitEditClass, resetEditClassFormOnModalClose } = useAcademicCalendarClassEdit(
    updateClassUrl,
    academicCalendarClass,
);

const canMoveStudents = computed(() => hasAbility(['update:academic-calendar-student-enrolments']));
const canAdvancePhase = computed(() => canMoveStudents.value && props.isLastProgrammePhase !== true);
const canCompleteLevel = computed(() => canMoveStudents.value && props.isLastProgrammePhase === true);

const singleClassExportOption = computed(() => [
    {
        academicCalendarClassId: academicCalendarClass.value.id,
        name: academicCalendarClass.value.name,
        studentCount: academicCalendarClass.value.studentCount,
    },
]);

const onAssignTutor = (): void => {
    openAssignClassTutorModal({
        academicCalendarClassId: academicCalendarClass.value.id,
        staffId: academicCalendarClass.value.tutor?.id ?? null,
    });
};

const { open: openConfirmDialog } = useCustomConfirmDialog();

const onAdvancePhase = async (): Promise<void> => {
    if (selectedStudentEnrolmentIds.value.length === 0) {
        return;
    }

    const confirmed = await openConfirmDialog({
        title: trans('academic_calendar.advance_phase_confirm_title'),
        message: trans('academic_calendar.advance_phase_confirm_message'),
        confirmText: trans('academic_calendar.continue_next_phase'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.post(
        advancePhaseUrl.value,
        { student_enrolment_ids: selectedStudentEnrolmentIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedStudentEnrolmentIds.value = [];
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.advance_phase_none')));
            },
        },
    );
};

const onCompleteLevel = async (): Promise<void> => {
    if (selectedStudentEnrolmentIds.value.length === 0) {
        return;
    }

    const confirmed = await openConfirmDialog({
        title: trans('academic_calendar.complete_level_confirm_title'),
        message: trans('academic_calendar.complete_level_confirm_message'),
        confirmText: trans('academic_calendar.mark_level_completed'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.post(
        completeLevelUrl.value,
        { student_enrolment_ids: selectedStudentEnrolmentIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedStudentEnrolmentIds.value = [];
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.complete_level_none')));
            },
        },
    );
};

const onRemoveTutor = async (): Promise<void> => {
    const confirmed = await openConfirmDialog({
        title: trans('academic_calendar.remove_tutor_confirm_title'),
        message: trans('academic_calendar.remove_tutor_confirm_message'),
        confirmText: trans('academic_calendar.remove_tutor'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('academic-calendars.department-classes.assign-tutor', {
            institution_department: String(department.value.id),
            calendar_year: String(academicCalendar.value.attributes.calendarYear),
            academic_calendar_class: String(academicCalendarClass.value.id),
        }),
        { staff_id: null },
        {
            preserveScroll: true,
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.tutor_assign_failed')));
            },
        },
    );
};

const onRemoveStudent = async (student: AcademicCalendarClassPreviewStudent): Promise<void> => {
    const confirmed = await openConfirmDialog({
        title: trans('academic_calendar.remove_from_class_confirm_title'),
        message: trans('academic_calendar.remove_from_class_confirm_message', { name: student.name }),
        confirmText: trans('academic_calendar.remove_from_class'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.post(
        removeStudentsUrl.value,
        { student_enrolment_ids: [student.studentEnrolmentId] },
        {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('academic_calendar.remove_students_success', { count: 1 }));
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('academic_calendar.remove_students_failed')));
            },
        },
    );
};
</script>

<template>
    <Head :title="academicCalendarClass.name" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentClassesUrl">
        <template #backNavigationLeading>
            <AcademicCalendarClassNavComboSelect
                :classes="siblingAcademicCalendarClasses"
                :current-class-id="academicCalendarClass.id"
                :institution-department-id="Number(department.id)"
                :calendar-year="academicCalendar.attributes.calendarYear"
            />
        </template>
        <div class="flex flex-col gap-2">
            <AcademicCalendarClassHeaderCard
                :title="academicCalendarClass.name"
                :description="academicCalendarClass.description"
                :student-count="academicCalendarClass.studentCount"
                :tutor="academicCalendarClass.tutor ?? null"
                :can-update="canUpdateAcademicCalendarClass"
                :can-assign-staffing="canAssignStaffing"
                :class-config="classConfig"
                @edit="openEditClassModal"
                @assign-tutor="onAssignTutor"
                @remove-tutor="onRemoveTutor"
            >
                <AcademicCalendarClassModulesPanel
                    embedded
                    :institution-department-id="Number(department.id)"
                    :calendar-year="String(academicCalendar.attributes.calendarYear)"
                    :academic-calendar-class-id="academicCalendarClass.id"
                    :semester-modules="semesterModules"
                    :selected-semester-id="selectedSemesterId"
                    :period-label="classConfig?.attributes?.periodLabel ?? null"
                    :semester-config-has-syllabi="semesterConfigHasSyllabi"
                    :can-assign-staffing="canAssignStaffing"
                />
            </AcademicCalendarClassHeaderCard>
            <div class="mt-4 flex flex-col gap-2">
                <AcademicCalendarClassStudentFilters class="min-w-0" :filters="filters" @change="onFiltersChange">
                    <template #actions>
                        <BaseButton
                            v-if="canMoveStudents"
                            type="button"
                            :size="ButtonSize.xs"
                            :variant="ColorVariant.primary"
                            classes="rounded-full"
                            @click="openAddStudentsModal"
                        >
                            <BaseIcon :name="IconName.add" :color="ColorVariant.white" />
                            {{ $t('academic_calendar.add_student') }}
                        </BaseButton>
                        <BaseButton
                            v-if="canExportClassList"
                            type="button"
                            :size="ButtonSize.xs"
                            :variant="ColorVariant.primary_outline"
                            classes="rounded-full"
                            @click="openClassListExportModal"
                        >
                            <BaseIcon :name="IconName.export" />
                            {{ $t('academic_calendar.export_class_list') }}
                        </BaseButton>
                    </template>
                </AcademicCalendarClassStudentFilters>
                <Empty
                    v-if="filteredStudents.length === 0 && sortedStudents.length > 0"
                    :message="$t('trans.no_data')"
                />
                <AcademicCalendarClassStudentsTable
                    v-model:selected-student-enrolment-ids="selectedStudentEnrolmentIds"
                    v-model:select-all-change-class-model="selectAllChangeClassModel"
                    :sorted-students="filteredStudents"
                    :can-move-students="canMoveStudents"
                    :can-view-course-work="canViewCourseWork"
                    :can-advance-phase="canAdvancePhase"
                    :can-complete-level="canCompleteLevel"
                    :move-target-classes="moveTargetClasses"
                    :student-course-work-url="studentCourseWorkUrl"
                    @toggle-select-all="toggleSelectAllChangeClassFromRow"
                    @select-all-keydown="onSelectAllRowKeydown"
                    @open-move-students="openMoveStudentsModal"
                    @advance-phase="onAdvancePhase"
                    @complete-level="onCompleteLevel"
                    @remove-student="onRemoveStudent"
                />
            </div>
            <EditAcademicCalendarClassModal
                v-if="canUpdateAcademicCalendarClass"
                v-model:form="editClassForm"
                :modal-name="EDIT_CLASS_MODAL"
                :on-form-action="submitEditClass"
                :on-close-modal="resetEditClassFormOnModalClose"
            />
            <MoveAcademicCalendarStudentsModal
                v-if="canMoveStudents"
                v-model:form="moveForm"
                :modal-name="MOVE_STUDENTS_MODAL"
                :move-target-classes="moveTargetClasses"
                :on-form-action="submitMoveStudents"
                :on-close-modal="resetMoveFormOnModalClose"
            />
            <AddAcademicCalendarClassStudentsModal
                v-if="canMoveStudents"
                v-model:form="addStudentsForm"
                :modal-name="ADD_STUDENTS_MODAL"
                :unassigned-students="unassignedStudents"
                :on-form-action="submitAddStudents"
                :on-close-modal="resetAddStudentsFormOnModalClose"
            />
            <ClassListExportModal
                v-if="canExportClassList"
                :institution-department-id="Number(department.id)"
                :calendar-year="String(academicCalendar.attributes.calendarYear)"
                :class-config-query="classConfigQuery"
                :classes="singleClassExportOption"
                :single-class-id="academicCalendarClass.id"
            />
            <AssignClassTutorModal
                v-if="canAssignStaffing"
                :institution-department-id="Number(department.id)"
                :calendar-year="String(academicCalendar.attributes.calendarYear)"
            />
        </div>
    </PageContainer>
</template>
