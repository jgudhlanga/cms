<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { displayValue } from '@/composables/students/studentProgrammeDisplay';
import CourseWorkAssessmentFields from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkAssessmentFields.vue';
import CourseWorkModuleAggregation from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkModuleAggregation.vue';
import type { CourseWorkStudentSyllabus } from '@/types/course-work';

interface Props {
    syllabus: CourseWorkStudentSyllabus;
    canCreate: boolean;
    canUpdate: boolean;
    refreshing?: boolean;
    savingKey?: string | null;
    onSaveRow: (
        moduleId: number,
        assessmentTypeId: number,
        mark: number | null,
        remark: string | null,
        markId: number | null,
    ) => Promise<boolean>;
}

withDefaults(defineProps<Props>(), {
    refreshing: false,
    savingKey: null,
});
</script>

<template>
    <Empty
        v-if="syllabus.modules.length === 0"
        :message="$t('academic_calendar.course_work_no_modules')"
    />

    <BaseAccordion v-else class="w-full">
        <BaseAccordionItem
            v-for="courseModule in syllabus.modules"
            :key="courseModule.id"
            :value="String(courseModule.id)"
            :title="displayValue(courseModule.title)"
            :description="courseModule.code ? displayValue(courseModule.code) : undefined"
        >
            <div class="flex flex-col gap-3">
                <template v-if="courseModule.captureMarkOnly">
                    <CourseWorkAssessmentFields
                        :assessment="{
                            assessmentTypeId: 0,
                            assessmentTypeName: $t('academic_calendar.course_work_mark'),
                            markId: courseModule.moduleMark?.markId ?? null,
                            mark: courseModule.moduleMark?.mark ?? null,
                            remark: courseModule.moduleMark?.remark ?? null,
                        }"
                        :can-create="canCreate"
                        :can-update="canUpdate"
                        :saving="savingKey === `${courseModule.id}:mark-only`"
                        :on-save-row="(mark, remark) =>
                            onSaveRow(courseModule.id, 0, mark, remark, courseModule.moduleMark?.markId ?? null)"
                    />
                </template>
                <template v-else>
                    <CourseWorkAssessmentFields
                        v-for="assessment in courseModule.assessments"
                        :key="assessment.assessmentTypeId"
                        :assessment="assessment"
                        :can-create="canCreate"
                        :can-update="canUpdate"
                        :saving="savingKey === `${courseModule.id}:${assessment.assessmentTypeId}`"
                        :on-save-row="(mark, remark) =>
                            onSaveRow(
                                courseModule.id,
                                assessment.assessmentTypeId,
                                mark,
                                remark,
                                assessment.markId,
                            )"
                    />
                    <CourseWorkModuleAggregation
                        :aggregation="courseModule.aggregation"
                        :updating="refreshing"
                    />
                </template>
            </div>
        </BaseAccordionItem>
    </BaseAccordion>
</template>
