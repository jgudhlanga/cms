<script setup lang="ts">
import LabelValue from '@/components/core/util/LabelValue.vue';
import type { TeachingClassesSummary } from '@/types/lecturer';

defineProps<{
    summary: TeachingClassesSummary;
}>();
</script>

<template>
    <BaseCard :title="$t('dashboard.lecturer_classes_summary_title')">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <LabelValue
                :label="$tChoice('trans.class', 2)"
                :value="String(summary.classCount)"
            />
            <LabelValue
                :label="$tChoice('trans.student', 2)"
                :value="String(summary.studentCount)"
            />
            <LabelValue
                :label="$tChoice('trans.module', 2)"
                :value="String(summary.assignedModuleCount)"
            />
            <LabelValue
                :label="$t('dashboard.lecturer_open_assessment_windows')"
                :value="String(summary.openAssessmentWindowCount)"
            />
            <LabelValue
                :label="$t('dashboard.lecturer_missing_coursework')"
                :value="String(summary.missingCourseWorkCount)"
            />
        </div>
    </BaseCard>
</template>
