<script setup lang="ts">
import AcademicCalendarClassNavComboSelect from '@/components/academicCalendars/AcademicCalendarClassNavComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { EDIT_CLASS_MODAL, useAcademicCalendarClassEdit } from '@/composables/academicCalendars/useAcademicCalendarClassEdit';
import { MOVE_STUDENTS_MODAL, useAcademicCalendarClassMoveStudents } from '@/composables/academicCalendars/useAcademicCalendarClassMoveStudents';
import { useAcademicCalendarClassStudentFilters } from '@/composables/academicCalendars/useAcademicCalendarClassStudentFilters';
import { useAcademicCalendarClassStudentSelection } from '@/composables/academicCalendars/useAcademicCalendarClassStudentSelection';
import { useAcademicCalendarClassStudents } from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import { useDepartmentAcademicCalendarClassNavigation } from '@/composables/academicCalendars/useDepartmentAcademicCalendarClassNavigation';
import { hasAbility } from '@/lib/permissions';
import {
    AcademicCalendar,
    AcademicCalendarClassDetail,
    AcademicCalendarClassMoveTarget,
    ClassConfig,
} from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { computed, toRefs, watch } from 'vue';
import AcademicCalendarClassCourseWorkPlaceholder from './partials/AcademicCalendarClassCourseWorkPlaceholder.vue';
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
            <BaseAccordion :default-value="['course-work']">
                <BaseAccordionItem value="students" :title="$tChoice('trans.student', 2)">
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
                        :move-target-classes="moveTargetClasses"
                        @toggle-select-all="toggleSelectAllChangeClassFromRow"
                        @select-all-keydown="onSelectAllRowKeydown"
                        @open-move-students="openMoveStudentsModal"
                    />
                </BaseAccordionItem>
                <BaseAccordionItem value="course-work" :title="$tChoice('academic_calendar.course_work', 1)">
                    <AcademicCalendarClassCourseWorkPlaceholder />
                </BaseAccordionItem>
            </BaseAccordion>
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
