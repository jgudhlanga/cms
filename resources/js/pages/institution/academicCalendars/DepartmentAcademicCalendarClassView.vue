<script setup lang="ts">
import AcademicCalendarClassNavComboSelect from '@/components/academicCalendars/AcademicCalendarClassNavComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { EDIT_CLASS_MODAL, useAcademicCalendarClassEdit } from '@/composables/academicCalendars/useAcademicCalendarClassEdit';
import { MOVE_STUDENTS_MODAL, useAcademicCalendarClassMoveStudents } from '@/composables/academicCalendars/useAcademicCalendarClassMoveStudents';
import { useAcademicCalendarClassStudentSelection } from '@/composables/academicCalendars/useAcademicCalendarClassStudentSelection';
import { useAcademicCalendarClassStudents } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import { useDepartmentAcademicCalendarClassNavigation } from '@/composables/academicCalendars/useDepartmentAcademicCalendarClassNavigation';
import { hasAbility } from '@/lib/permissions';
import { AcademicCalendar, AcademicCalendarClassDetail, AcademicCalendarClassMoveTarget, ClassConfig } from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { computed, toRefs } from 'vue';
import AcademicCalendarClassHeaderCard from './partials/AcademicCalendarClassHeaderCard.vue';
import AcademicCalendarClassStudentsTable from './partials/AcademicCalendarClassStudentsTable.vue';
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
    }>(),
    {
        moveTargetClasses: () => [],
        siblingAcademicCalendarClasses: () => [],
        canUpdateAcademicCalendarClass: false,
    },
);

const { department, academicCalendar, academicCalendarClass, course, level, mode, classConfig, moveTargetClasses, siblingAcademicCalendarClasses } =
    toRefs(props);

const { departmentClassesUrl, moveStudentsUrl, updateClassUrl, breadcrumbs } = useDepartmentAcademicCalendarClassNavigation(
    department,
    academicCalendar,
    course,
    level,
    mode,
    classConfig,
    academicCalendarClass,
);

const { sortedStudents } = useAcademicCalendarClassStudents(academicCalendarClass);

const { selectedStudentEnrolmentIds, selectAllChangeClassModel, toggleSelectAllChangeClassFromRow, onSelectAllRowKeydown } =
    useAcademicCalendarClassStudentSelection(sortedStudents);

const { moveForm, openMoveStudentsModal, submitMoveStudents, resetMoveFormOnModalClose } = useAcademicCalendarClassMoveStudents(
    moveStudentsUrl,
    moveTargetClasses,
    selectedStudentEnrolmentIds,
);

const { editClassForm, openEditClassModal, submitEditClass, resetEditClassFormOnModalClose } = useAcademicCalendarClassEdit(
    updateClassUrl,
    academicCalendarClass,
);

const canMoveStudents = computed(() => hasAbility(['update:academic-calendar-student-enrolments']));
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
        <div class="flex flex-col space-y-6">
            <AcademicCalendarClassHeaderCard
                :title="academicCalendarClass.name"
                :description="academicCalendarClass.description"
                :student-count="academicCalendarClass.studentCount"
                :can-update="canUpdateAcademicCalendarClass"
                @edit="openEditClassModal"
            />
            <AcademicCalendarClassStudentsTable
                v-model:selected-student-enrolment-ids="selectedStudentEnrolmentIds"
                v-model:select-all-change-class-model="selectAllChangeClassModel"
                :sorted-students="sortedStudents"
                :can-move-students="canMoveStudents"
                :move-target-classes="moveTargetClasses"
                @toggle-select-all="toggleSelectAllChangeClassFromRow"
                @select-all-keydown="onSelectAllRowKeydown"
                @open-move-students="openMoveStudentsModal"
            />
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
        </div>
    </PageContainer>
</template>
