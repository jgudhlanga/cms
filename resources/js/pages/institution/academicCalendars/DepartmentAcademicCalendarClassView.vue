<script setup lang="ts">
import AcademicCalendarClassNavComboSelect from '@/components/academicCalendars/AcademicCalendarClassNavComboSelect.vue';
import ClassListExportModal from '@/components/academicCalendars/ClassListExportModal.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { EDIT_CLASS_MODAL, useAcademicCalendarClassEdit } from '@/composables/academicCalendars/useAcademicCalendarClassEdit';
import { openClassListExportModal } from '@/composables/academicCalendars/useClassListExport';
import { MOVE_STUDENTS_MODAL, useAcademicCalendarClassMoveStudents } from '@/composables/academicCalendars/useAcademicCalendarClassMoveStudents';
import { useAcademicCalendarClassStudentFilters } from '@/composables/academicCalendars/useAcademicCalendarClassStudentFilters';
import { useAcademicCalendarClassStudentSelection } from '@/composables/academicCalendars/useAcademicCalendarClassStudentSelection';
import { useAcademicCalendarClassStudents } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import { useDepartmentAcademicCalendarClassNavigation } from '@/composables/academicCalendars/useDepartmentAcademicCalendarClassNavigation';
import { hasAbility } from '@/lib/permissions';
import { AcademicCalendar, AcademicCalendarClassDetail, AcademicCalendarClassMoveTarget, ClassConfig } from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { computed, toRefs, watch } from 'vue';
import AcademicCalendarClassStudentFilters from './partials/AcademicCalendarClassStudentFilters.vue';
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
        canViewCourseWork?: boolean;
        canExportClassList?: boolean;
    }>(),
    {
        moveTargetClasses: () => [],
        siblingAcademicCalendarClasses: () => [],
        canUpdateAcademicCalendarClass: false,
        canViewCourseWork: false,
        canExportClassList: false,
    },
);

const { department, academicCalendar, academicCalendarClass, course, level, mode, classConfig, moveTargetClasses, siblingAcademicCalendarClasses } =
    toRefs(props);

const { departmentClassesUrl, moveStudentsUrl, updateClassUrl, breadcrumbs, studentCourseWorkUrl, classConfigQuery } =
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

const { editClassForm, openEditClassModal, submitEditClass, resetEditClassFormOnModalClose } = useAcademicCalendarClassEdit(
    updateClassUrl,
    academicCalendarClass,
);

const canMoveStudents = computed(() => hasAbility(['update:academic-calendar-student-enrolments']));

const singleClassExportOption = computed(() => [
    {
        academicCalendarClassId: academicCalendarClass.value.id,
        name: academicCalendarClass.value.name,
        studentCount: academicCalendarClass.value.studentCount,
    },
]);
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
                :can-export-class-list="canExportClassList"
                @edit="openEditClassModal"
                @export-class-list="openClassListExportModal"
            />
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-foreground">{{ $tChoice('trans.student', 2) }}</h2>
                <AcademicCalendarClassStudentFilters :filters="filters" @change="onFiltersChange" />
                <Empty
                    v-if="filteredStudents.length === 0 && sortedStudents.length > 0"
                    :message="$t('trans.no_data')"
                />
                <AcademicCalendarClassStudentsTable
                    v-else
                    v-model:selected-student-enrolment-ids="selectedStudentEnrolmentIds"
                    v-model:select-all-change-class-model="selectAllChangeClassModel"
                    :sorted-students="filteredStudents"
                    :can-move-students="canMoveStudents"
                    :can-view-course-work="canViewCourseWork"
                    :move-target-classes="moveTargetClasses"
                    :student-course-work-url="studentCourseWorkUrl"
                    @toggle-select-all="toggleSelectAllChangeClassFromRow"
                    @select-all-keydown="onSelectAllRowKeydown"
                    @open-move-students="openMoveStudentsModal"
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
            <ClassListExportModal
                v-if="canExportClassList"
                :institution-department-id="Number(department.id)"
                :calendar-year="String(academicCalendar.attributes.calendarYear)"
                :class-config-query="classConfigQuery"
                :classes="singleClassExportOption"
                :single-class-id="academicCalendarClass.id"
            />
        </div>
    </PageContainer>
</template>
