<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useAcademicCalendarClassStudentCourseWork } from '@/composables/academicCalendars/useAcademicCalendarClassStudentCourseWork';
import { courseWorkAuditEventTranslationKey } from '@/lib/course-work';
import CourseWorkStudentSyllabusSection from '@/pages/institution/academicCalendars/partials/courseWork/CourseWorkStudentSyllabusSection.vue';
import type { CourseWorkAuditLogEntry } from '@/types/course-work';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';

const props = withDefaults(
    defineProps<{
        academicCalendarClassId: number;
        studentEnrolmentId: number;
        canCreate?: boolean;
        canUpdate?: boolean;
        canViewAuditTrail?: boolean;
        moduleLocks?: Record<number, {
            hasEditableCourseWork: boolean;
            allAssessmentTypesLocked: boolean;
            lockedAssessmentTypeIds: number[];
            lockedAssessmentTypeNames: string[];
            readOnlyMessage: string | null;
        }>;
    }>(),
    {
        canCreate: false,
        canUpdate: false,
        canViewAuditTrail: false,
    },
);

const { tree, auditLogs, loading, refreshing, savingKey, auditLoading, error, loadTree, loadAuditLogs, saveMark } =
    useAcademicCalendarClassStudentCourseWork(props.academicCalendarClassId, props.studentEnrolmentId);

const { formatDate } = useUtils();

const auditEventLabel = (event: string): string => trans(courseWorkAuditEventTranslationKey(event));

const formatAuditMark = (mark: number | null | undefined): string =>
    mark !== null && mark !== undefined ? String(mark) : trans('students.not_available');

const auditTrailDescription = computed((): string | undefined => {
    if (auditLoading.value) {
        return trans('academic_calendar.course_work_loading');
    }

    if (auditLogs.value.length === 0) {
        return trans('academic_calendar.course_work_audit_empty');
    }

    return trans('academic_calendar.course_work_audit_count', { count: String(auditLogs.value.length) });
});

const formatAuditDate = (createdAt: string | null): string | null => {
    if (!createdAt) {
        return null;
    }

    return `${trans('academic_calendar.course_work_audit_date')}: ${formatDate(createdAt, 'L LT')}`;
};

const formatAuditValues = (newValues: { mark: number | null; remark: string | null }): string => {
    const parts = [`${trans('academic_calendar.course_work_mark')}: ${formatAuditMark(newValues.mark)}`];

    if (newValues.remark) {
        parts.push(`${trans('academic_calendar.course_work_remark')}: ${newValues.remark}`);
    }

    return parts.join(' · ');
};

const formatAuditLine = (log: CourseWorkAuditLogEntry): string => {
    const segments = [
        formatAuditDate(log.createdAt),
        log.userName ?? trans('students.not_available'),
        log.moduleCode,
        log.assessmentTypeName,
        auditEventLabel(log.event),
    ].filter((segment): segment is string => Boolean(segment));

    if (log.newValues) {
        segments.push(formatAuditValues(log.newValues));
    }

    return segments.join(' · ');
};

const onSaveRow = (
    moduleId: number,
    assessmentTypeId: number,
    mark: number | null,
    remark: string | null,
    markId: number | null,
): Promise<boolean> =>
    saveMark({
        markId,
        courseSyllabusModuleId: moduleId,
        assessmentTypeId,
        mark,
        remark,
    });

onMounted(async () => {
    await loadTree();

    if (props.canViewAuditTrail) {
        await loadAuditLogs();
    }
});
</script>

<template>
    <div class="flex flex-col space-y-6">
        <p v-if="loading" class="text-sm text-muted-foreground">{{ $t('academic_calendar.course_work_loading') }}</p>
        <p v-else-if="error" class="text-sm text-destructive">{{ $t(error) }}</p>

        <Empty
            v-else-if="tree && tree.syllabi.length === 0"
            :message="$t('academic_calendar.course_work_no_syllabi')"
        />

        <Empty
            v-else-if="tree && tree.assessmentTypes.length === 0"
            :message="$t('academic_calendar.course_work_no_assessment_types')"
        />

        <section v-else-if="tree" class="space-y-4">
            <div
                v-for="syllabus in tree.syllabi"
                :key="syllabus.id"
                class="space-y-3"
            >
                <div class="flex items-center gap-3 uppercase">
                    <span class="text-base font-bold text-foreground">{{ syllabus.title }}</span>
                    <BaseTag :title="syllabus.code" />
                </div>

                <CourseWorkStudentSyllabusSection
                    :syllabus="syllabus"
                    :can-create="canCreate"
                    :can-update="canUpdate"
                    :refreshing="refreshing"
                    :saving-key="savingKey"
                    :module-locks="moduleLocks"
                    :on-save-row="onSaveRow"
                />
            </div>
        </section>

        <p v-if="tree && tree.syllabi.length > 0" class="text-xs text-muted-foreground">
            {{ $t('academic_calendar.course_work_phase2_note') }}
        </p>

        <BaseAccordion v-if="canViewAuditTrail" class="w-full border-t border-border pt-4">
            <BaseAccordionItem
                value="course-work-audit-trail"
                :title="$t('academic_calendar.course_work_audit_trail')"
                :description="auditTrailDescription"
            >
                <ul
                    v-if="!auditLoading && auditLogs.length > 0"
                    class="max-h-40 space-y-0.5 overflow-y-auto"
                >
                    <li
                        v-for="log in auditLogs"
                        :key="log.id"
                        :title="formatAuditLine(log)"
                        class="truncate rounded px-1 py-0.5 text-xs leading-snug text-muted-foreground hover:bg-muted/40"
                    >
                        <template v-if="log.createdAt">
                            <span>{{ formatAuditDate(log.createdAt) }}</span>
                            <span> · </span>
                        </template>
                        <span class="font-medium text-foreground/90">{{ log.userName ?? $t('students.not_available') }}</span>
                        <span> · {{ log.moduleCode }} · {{ log.assessmentTypeName }} · </span>
                        <span class="uppercase">{{ auditEventLabel(log.event) }}</span>
                        <template v-if="log.newValues">
                            <span> · {{ $t('academic_calendar.course_work_mark') }}: {{ formatAuditMark(log.newValues.mark) }}</span>
                            <template v-if="log.newValues.remark">
                                <span> · {{ $t('academic_calendar.course_work_remark') }}: {{ log.newValues.remark }}</span>
                            </template>
                        </template>
                    </li>
                </ul>
            </BaseAccordionItem>
        </BaseAccordion>
    </div>
</template>
