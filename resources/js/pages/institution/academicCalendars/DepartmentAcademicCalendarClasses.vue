<script setup lang="ts">
import { Head, Link as InertiaLink, useForm } from '@inertiajs/vue3';
import { UserIcon, UserRoundIcon, UsersIcon } from 'lucide-vue-next';

import PageContainer from '@/components/core/page/PageContainer.vue';
import { AcademicCalendar, AcademicCalendarClassGenerationContext, AcademicCalendarClassPreview, ClassConfig } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { trans } from 'laravel-vue-i18n';
import { computed, toRefs } from 'vue';

const props = defineProps<{
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
    errors: object;
}>();

const { department, academicCalendar, level, course, mode, classConfig, previewClasses, generationContext } = toRefs(props);

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
                successAlert(trans('enrolment.classes_generated_successfully'));
                syncFormDefaultsFromGenerationContext();
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('enrolment.classes_generation_failed')));
            },
        },
    );
};
</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution-departments.show', String(department.id))">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="String(classConfig?.attributes?.calendarYear ?? '---')">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                    <LabelValue :label="$tChoice('trans.course', 1)" :value="classConfig?.attributes?.departmentCourse ?? '---'" />
                    <LabelValue :label="$tChoice('trans.level', 1)" :value="classConfig?.attributes?.departmentLevel ?? '---'" />
                    <LabelValue :label="$tChoice('general.mode', 1)" :value="classConfig?.attributes?.modeOfStudy ?? '---'" />
                    <LabelValue :label="$tChoice('academic_calendar.class_unit_size', 1)" :value="String(classConfig?.attributes?.studentsPerClass ?? '---')" />
                    <LabelValue
                        :label="$tChoice('syllabus.course_syllabus', 2)"
                        :value="
                            (classConfig?.attributes?.courseSyllabusCodes ?? []).length > 0
                                ? (classConfig?.attributes?.courseSyllabusCodes ?? []).join(', ')
                                : '---'
                        "
                    />
                    <LabelValue
                        :label="$tChoice('trans.class', 2)"
                        :value="String(generationContext.populatedExistingClassCount ?? 0)"
                    />
                </div>
            </BaseCard>

            <div class="flex items-center justify-between">
                <HeadingSmall :title="`${$t('enrolment.final_enrolments')} (${generationContext.finalStudentCount})`" />
                <BaseButton
                    :title="$t(classActionTitle)"
                    :disabled="!hasNewStudentsToAssign || form.processing"
                    :processing="form.processing"
                    @click="saveClasses"
                    classes="rounded-full"
                />
            </div>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-700">
                <div class="flex items-center gap-1">
                    {{ $t('students.not_in_class') }}:
                    <span class="font-semibold">{{ generationContext.newFinalStudentCount }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <UserIcon class="h-4 w-4 text-blue-600" />
                    <span class="font-semibold">{{ $tChoice('general.male', 2) }}: {{ generationContext.newStudentGenderCounts.male }}</span>
                </div>
                <div class="flex items-center gap-1">
                    <UserRoundIcon class="h-4 w-4 text-pink-600" />
                    <span class="font-semibold">{{ $tChoice('general.female', 2) }}: {{ generationContext.newStudentGenderCounts.female }}</span>
                </div>
                <div class="flex items-center gap-1 font-medium">
                    {{ $t('trans.other') }}:
                    <span class="font-semibold">{{ generationContext.newStudentGenderCounts.unknown }}</span>
                </div>
            </div>

            <BaseAlert
                v-if="previewEmptyAlert"
                :title="$t(previewEmptyAlert.titleKey)"
                :description="$t(previewEmptyAlert.descriptionKey)"
            />

            <template v-else>
                <BaseCard v-for="classPreview in previewClasses" :key="classPreview.name" :title="classPreview.name">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex flex-1 flex-wrap items-center gap-x-8 gap-y-2">
                            <p class="flex items-center gap-1 text-sm text-gray-700">
                                <UsersIcon class="h-4 w-4 text-gray-600" />
                                <span class="font-semibold">{{ $t('students.class_total') }}: {{ classPreview.studentCount }}</span>
                            </p>
                            <p v-if="classPreview.academicCalendarClassId" class="flex items-center gap-1 text-sm text-gray-700">
                                <UserIcon class="h-4 w-4 text-blue-600" />
                                <span class="font-semibold">{{ $tChoice('general.male', 2) }}: {{ classPreview.genderCounts?.male ?? 0 }}</span>
                            </p>
                            <p v-if="classPreview.academicCalendarClassId" class="flex items-center gap-1 text-sm text-gray-700">
                                <UserRoundIcon class="h-4 w-4 text-pink-600" />
                                <span class="font-semibold">{{ $tChoice('general.female', 2) }}: {{ classPreview.genderCounts?.female ?? 0 }}</span>
                            </p>
                        </div>
                        <InertiaLink
                            v-if="classPreview.academicCalendarClassId"
                            :href="
                                route('academic-calendars.department-classes.show', {
                                    institution_department: String(department.id),
                                    calendar_year: String(academicCalendar.attributes.calendarYear),
                                    academic_calendar_class: String(classPreview.academicCalendarClassId),
                                })
                            "
                        >
                        <BaseButton :title="$t('enrolment.view_class')" classes="rounded-full" :size="ButtonSize.sm" :variant="ColorVariant.success" />
                        </InertiaLink>
                        <BaseButton v-else :title="$t('enrolment.view_class')" :disabled="true" classes="rounded-full" :size="ButtonSize.sm" :variant="ColorVariant.success"/>
                    </div>
                </BaseCard>
            </template>
        </div>
    </PageContainer>
</template>
