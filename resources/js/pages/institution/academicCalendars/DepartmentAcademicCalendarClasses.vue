<script setup lang="ts">
import AssignClassTutorModal from '@/components/academicCalendars/AssignClassTutorModal.vue';
import ClassListExportModal from '@/components/academicCalendars/ClassListExportModal.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DepartmentModeTotalsStrip from '@/components/institution/DepartmentModeTotalsStrip.vue';
import { openAssignClassTutorModal } from '@/composables/academicCalendars/useAcademicCalendarClassTutor';
import { openClassListExportModal } from '@/composables/academicCalendars/useClassListExport';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import {
    AcademicCalendar,
    AcademicCalendarClassGenerationContext,
    AcademicCalendarClassPreview,
    ClassConfig,
    ClassStaffingSummary,
} from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { Head, Link as InertiaLink, router, useForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, toRefs } from 'vue';
import AcademicCalendarClassPreviewCard from './partials/AcademicCalendarClassPreviewCard.vue';
import AcademicCalendarClassStaffingSummaryCard from './partials/AcademicCalendarClassStaffingSummaryCard.vue';
import AssessmentCalendarWindowsList from '@/components/assessments/AssessmentCalendarWindowsList.vue';
import type { AssessmentCalendarWindow } from '@/types/assessments';

const props = withDefaults(
    defineProps<{
        department: InstitutionDepartment;
        academicCalendar: AcademicCalendar;
        academicCalendars: AcademicCalendar[];
        course: DepartmentCourse;
        level: DepartmentLevel;
        mode: ModeOfStudy;
        auth: AuthObject;
        classConfig: ClassConfig | null;
        previewClasses: AcademicCalendarClassPreview[];
        generationContext: AcademicCalendarClassGenerationContext;
        staffingSummary: ClassStaffingSummary;
        selectedSemesterId: number | null;
        calendarType: 'term' | 'semester' | 'abma';
        semesterConfigHasSyllabi: boolean;
        canAssignStaffing?: boolean;
        errors: object;
        canViewCourseWork?: boolean;
        canExportClassList?: boolean;
        assessmentWindows?: AssessmentCalendarWindow[];
    }>(),
    {
        canAssignStaffing: false,
        canViewCourseWork: false,
        canExportClassList: false,
        assessmentWindows: () => [],
        staffingSummary: () => ({
            tutorsAssigned: 0,
            classCount: 0,
            modulesTotal: 0,
            moduleSlotsStaffed: 0,
            semesterModuleCount: 0,
        }),
        selectedSemesterId: null,
        calendarType: 'semester',
        semesterConfigHasSyllabi: false,
    },
);

const { department, academicCalendar, level, course, mode, classConfig, previewClasses, generationContext } = toRefs(props);

const canOpenCourseWorkMarksheet = computed(
    () =>
        props.canViewCourseWork
        && classConfig.value != null
        && (generationContext.value.populatedExistingClassCount > 0
            || previewClasses.value.some((preview) => preview.academicCalendarClassId != null)),
);

const exportablePreviewClasses = computed(() =>
    previewClasses.value.filter((preview) => preview.academicCalendarClassId != null),
);

const canExportClassLists = computed(
    () => props.canExportClassList && exportablePreviewClasses.value.length > 0,
);

const classConfigQuery = computed((): Record<string, string> => {
    const context = generationContext.value;
    const query: Record<string, string> = {
        class_config_id: String(context.classConfigId ?? classConfig.value?.id ?? ''),
        department_course_id: String(context.departmentCourseId ?? ''),
        department_level_id: String(context.departmentLevelId ?? ''),
        mode_of_study_id: String(context.modeOfStudyId ?? ''),
    };

    if (props.selectedSemesterId != null) {
        query.semester_id = String(props.selectedSemesterId);
    }

    return query;
});

const courseWorkMarksheetUrl = computed(() =>
    route('academic-calendars.department-classes.course-work-marksheet', {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        ...classConfigQuery.value,
    }),
);

const hasNewStudentsToAssign = computed(() => generationContext.value.newFinalStudentCount > 0);

type PreviewEmptyAlert = {
    titleKey: string;
    descriptionKey: string;
};

const previewEmptyAlert = computed((): PreviewEmptyAlert | null => {
    if (previewClasses.value.length > 0) {
        return null;
    }

    const context = generationContext.value;
    if (context.finalStudentCount === 0) {
        return { titleKey: 'trans.no_data', descriptionKey: 'enrolment.preview_empty_no_final_enrolments' };
    }

    if (context.classConfigId == null) {
        return { titleKey: 'trans.no_data', descriptionKey: 'enrolment.preview_empty_no_class_config' };
    }

    const studentsPerClass = Number(context.studentsPerClass ?? 0);
    if (studentsPerClass < 1) {
        return { titleKey: 'trans.no_data', descriptionKey: 'enrolment.preview_empty_no_class_size' };
    }

    if (context.newFinalStudentCount === 0) {
        return { titleKey: 'trans.no_data', descriptionKey: 'enrolment.preview_empty_all_assigned' };
    }

    return { titleKey: 'trans.no_data', descriptionKey: 'enrolment.no_preview_classes_generated' };
});

const classActionTitle = computed(() =>
    hasNewStudentsToAssign.value && generationContext.value.hasExistingClasses
        ? 'enrolment.add_student_to_class'
        : 'enrolment.generate_classes',
);

const breadcrumbs = computed<Array<Link>>(() => [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.value.attributes?.isAcademic }) },
    { title: department.value.attributes.departmentCode, href: route('institution-departments.show', String(department.value.id)) },
    { title: level.value.attributes.level, href: route('institution-departments.show', String(department.value.id)) },
    { title: course.value.attributes.course, href: route('institution-departments.show', String(department.value.id)) },
    { title: mode.value.attributes.name, href: route('institution-departments.show', String(department.value.id)) },
    { transChoiceKey: 'class' },
]);

const form = useForm({
    class_config_id: generationContext.value.classConfigId,
    department_level_id: generationContext.value.departmentLevelId,
    department_course_id: generationContext.value.departmentCourseId,
    mode_of_study_id: generationContext.value.modeOfStudyId,
    students_per_class: generationContext.value.studentsPerClass,
});

const syncFormDefaultsFromGenerationContext = (): void => {
    const context = generationContext.value;
    form.defaults({
        class_config_id: context.classConfigId,
        department_level_id: context.departmentLevelId,
        department_course_id: context.departmentCourseId,
        mode_of_study_id: context.modeOfStudyId,
        students_per_class: context.studentsPerClass,
    });
    form.reset();
};

const saveClasses = () => {
    form.post(
        route('academic-calendars.department-classes.store', {
            institution_department: String(department.value.id),
            calendar_year: String(academicCalendar.value.attributes.calendarYear),
        }),
        {
            onSuccess: () => {
                syncFormDefaultsFromGenerationContext();
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('enrolment.classes_generation_failed')));
            },
        },
    );
};

const classShowUrl = (classPreview: AcademicCalendarClassPreview): string | null => {
    if (classPreview.academicCalendarClassId == null) {
        return null;
    }

    const params: Record<string, string> = {
        institution_department: String(department.value.id),
        calendar_year: String(academicCalendar.value.attributes.calendarYear),
        academic_calendar_class: String(classPreview.academicCalendarClassId),
        ...classConfigQuery.value,
    };

    return route('academic-calendars.department-classes.show', params);
};

const computedTitle = computed(() => classConfig.value?.attributes?.departmentCourse ?? '');

const enrolmentLegend = computed(() => {
    const placed = previewClasses.value.reduce(
        (totals, classPreview) => ({
            male: totals.male + Number(classPreview.genderCounts?.male ?? 0),
            female: totals.female + Number(classPreview.genderCounts?.female ?? 0),
        }),
        { male: 0, female: 0 },
    );
    const unplaced = generationContext.value.newStudentGenderCounts;

    return [
        {
            id: 'male',
            label: trans_choice('general.male', 2),
            count: placed.male + Number(unplaced.male ?? 0),
            colorClass: 'bg-blue-600',
        },
        {
            id: 'female',
            label: trans_choice('general.female', 2),
            count: placed.female + Number(unplaced.female ?? 0),
            colorClass: 'bg-pink-500',
        },
        {
            id: 'unplaced',
            label: trans('students.not_in_class'),
            count: generationContext.value.newFinalStudentCount,
            colorClass: 'bg-muted-foreground/40',
        },
    ];
});

const onAssignTutor = (classId: number, staffId?: number | null): void => {
    openAssignClassTutorModal({ academicCalendarClassId: classId, staffId });
};

const { open: openConfirmDialog } = useCustomConfirmDialog();

const onRemoveTutor = async (classId: number): Promise<void> => {
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
            academic_calendar_class: String(classId),
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
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution-departments.show', String(department.id))">
        <div class="flex flex-col gap-3">
            <AcademicCalendarClassStaffingSummaryCard
                :title="computedTitle"
                :class-config="classConfig"
                :staffing-summary="staffingSummary"
                :selected-semester-id="selectedSemesterId"
                :calendar-type="calendarType"
                :semester-config-has-syllabi="semesterConfigHasSyllabi"
            />

            <div
                v-if="assessmentWindows.length > 0"
                class="rounded-xl border border-border bg-card p-3"
            >
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $t('assessments.dashboard_assessment_calendars') }}
                </p>
                <AssessmentCalendarWindowsList :windows="assessmentWindows" compact />
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <DepartmentModeTotalsStrip
                    :total="generationContext.finalStudentCount"
                    :total-label="$t('enrolment.final_enrolments')"
                    :items="enrolmentLegend"
                    :align="'start'"
                />
                <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
                    <BaseButton
                        v-if="canExportClassLists"
                        type="button"
                        :title="$t('academic_calendar.export_class_lists')"
                        classes="rounded-full"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.xs"
                        @click="openClassListExportModal"
                    />
                    <InertiaLink v-if="canOpenCourseWorkMarksheet" :href="courseWorkMarksheetUrl">
                        <BaseButton
                            type="button"
                            :title="$t('academic_calendar.course_work_open_marksheet')"
                            classes="rounded-full"
                            :variant="ColorVariant.primary_outline"
                            :size="ButtonSize.xs"
                        />
                    </InertiaLink>
                    <BaseButton
                        :title="$t(classActionTitle)"
                        :disabled="!hasNewStudentsToAssign || form.processing"
                        :processing="form.processing"
                        :size="ButtonSize.xs"
                        @click="saveClasses"
                        classes="rounded-full"
                    />
                </div>
            </div>

            <BaseAlert
                v-if="previewEmptyAlert"
                :title="$t(previewEmptyAlert.titleKey)"
                :description="$t(previewEmptyAlert.descriptionKey)"
            />
            <div v-else class="flex flex-col gap-1.5">
                <AcademicCalendarClassPreviewCard
                    v-for="classPreview in previewClasses"
                    :key="classPreview.name"
                    :class-preview="classPreview"
                    :show-url="classShowUrl(classPreview)"
                    :can-assign-staffing="canAssignStaffing"
                    :show-module-staffing="selectedSemesterId != null"
                    @assign-tutor="onAssignTutor"
                    @remove-tutor="onRemoveTutor"
                />
            </div>
            <AssignClassTutorModal
                v-if="canAssignStaffing"
                :institution-department-id="Number(department.id)"
                :calendar-year="String(academicCalendar.attributes.calendarYear)"
            />
            <ClassListExportModal
                v-if="canExportClassLists"
                :institution-department-id="Number(department.id)"
                :calendar-year="String(academicCalendar.attributes.calendarYear)"
                :class-config-query="classConfigQuery"
                :classes="exportablePreviewClasses"
            />
        </div>
    </PageContainer>
</template>
