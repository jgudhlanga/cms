<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import SubjectTable from '@/components/students/update/partials/SubjectTable.vue';
import { EXAM_SITTINGS } from '@/lib/constants';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { AcademicOLevelResult } from '@/types/enrolments';
import { computed, ref } from 'vue';

interface Props {
    results: AcademicOLevelResult[];
    requirements?: CourseRequirement | DepartmentLevelRequirement | null;
}

const props = defineProps<Props>();
const { requirements, results } = props;

const examSittings = ref(EXAM_SITTINGS);

/**
 * ======================
 *   HELPER FUNCTIONS
 * ======================
 */
const findResultBySubjectId = (subjectId: string) => results.find((r) => String(r.attributes?.subjectId) === subjectId);

const getExamSitting = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    const sitting = examSittings.value.find((s) => String(s.value) === String(result?.attributes?.examSitting));
    return sitting ? String(sitting.label) : '---';
};

const getExamYear = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    return result ? String(result.attributes?.examYear) : '--';
};

const getGrade = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    return result ? String(result.attributes?.grade) : '--';
};

/**
 * ======================
 *   COMPUTED SUBJECTS
 * ======================
 */
const mainSubjectIds = computed(() => requirements?.attributes?.mainSubjectIds ?? []);

const mainSubjects = computed(() => requirements?.relationships?.subjects ?? []);

const otherSubjects = computed(() => results.filter((r) => !mainSubjectIds.value.includes(Number(String(r.attributes?.subjectId)))));
</script>

<template>
    <!-- MAIN SUBJECTS -->
    <HeadingSmall
        :title="`${$t('trans.o_level_main_subjects')} (${requirements?.attributes?.mainSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />

    <SubjectTable :subjects="mainSubjects" type="main" :getExamYear="getExamYear" :getExamSitting="getExamSitting" :getGrade="getGrade" />

    <!-- OTHER SUBJECTS -->
    <HeadingSmall
        :title="`${$t('trans.o_level_other_subjects')} (${requirements?.attributes?.otherSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />

    <SubjectTable :subjects="otherSubjects" type="other" :getExamYear="getExamYear" :getExamSitting="getExamSitting" :getGrade="getGrade" />
</template>
