<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import OLevelResultCard from '@/components/students/oLevels/OLevelResultCard.vue';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { ColorVariant } from '@/enums/colors';
import { successAlert } from '@/lib/alerts';
import { EXAM_SITTINGS } from '@/lib/constants';
import { IconName } from '@/lib/icons';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { AcademicOLevelResult } from '@/types/enrolments';
import { router } from '@inertiajs/vue3';
import { computed, defineEmits, ref, watch } from 'vue';

interface Props {
    results: AcademicOLevelResult[];
    studentId: string;
    requirements?: CourseRequirement | DepartmentLevelRequirement | null;
}

const emit = defineEmits(['deleted']);
const props = defineProps<Props>();
const { requirements, results, studentId } = props;

const { onCreateOrEdit } = useOLevelResults();

const localResults = ref<AcademicOLevelResult[]>([...results]);
watch(
    () => props.results,
    (newVal) => {
        localResults.value = [...newVal];
    },
);

const examSittings = ref(EXAM_SITTINGS);

/**
 * ======================
 *   HELPER FUNCTIONS
 * ======================
 */
const findResultBySubjectId = (subjectId: string) => localResults.value.find((r) => String(r.attributes?.subjectId) === subjectId);

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

const getGradeId = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    return result ? String(result.attributes?.gradeId) : '--';
};

const getResultId = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    return result ? String(result.id) : '--';
};

/**
 * ======================
 *   COMPUTED SUBJECTS
 * ======================
 */
const mainSubjectIds = computed(() => requirements?.attributes?.mainSubjectIds ?? []);

const mainSubjects = computed(() => requirements?.relationships?.subjects ?? []);

const otherSubjects = computed(() => localResults.value.filter((r) => !mainSubjectIds.value.includes(Number(String(r.attributes?.subjectId)))));

const hasMainSubjectsResults = computed(() => Array.isArray(transformMainSubjectResults()) && transformMainSubjectResults().length > 0);
const hasOtherSubjectsResults = computed(() => Array.isArray(transformOtherSubjectResults()) && transformOtherSubjectResults().length > 0);
const additionalOtherSubjectsAllowed = computed(() => {
    const totalAllowed = Number(requirements?.attributes?.otherSubjectsCount ?? 0);
    const used = otherSubjects.value?.length ?? 0;
    return totalAllowed - used > 0;
});
const transformMainSubjectResults = () =>
    mainSubjects.value?.map((r) => ({
        type: '',
        id: String(r.id),
        attributes: {
            studentId: studentId,
            resultId: getResultId(String(r.id)),
            subject: r.attributes?.name,
            examYear: getExamYear(String(r.id)),
            examSitting: getExamSitting(String(r.id)),
            gradeId: getGradeId(String(r.id)),
            grade: getGrade(String(r.id)),
        },
    }));

const transformOtherSubjectResults = () =>
    otherSubjects.value?.map((r) => ({
        type: '',
        id: String(r.attributes?.subjectId),
        attributes: {
            studentId: studentId,
            resultId: String(r.id),
            subject: r.attributes?.subject,
            examYear: r.attributes?.examYear,
            examSitting: r.attributes?.examSitting,
            gradeId: r.attributes?.gradeId,
            grade: r.attributes?.grade,
        },
    }));

const handleResultDeleted = (recordId: string) => {
    localResults.value = localResults.value.filter((r) => String(r.id) !== String(recordId));
};

const emptyEdit = () => {
    return {
        type: '',
        id: '0',
        attributes: {
            studentId: studentId,
            resultId: '0',
            subject: '',
            examYear: '',
            examSitting: '',
            gradeId: '0',
            grade: '',
        },
    };
};

const deleteCallback = async (resultId: string) => {
    if (!Number(resultId) || Number(resultId) <= 0) {
        return;
    }
    router.delete(route('portal.delete-o-level-results', resultId), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('O level result deleted successfully');
            handleResultDeleted(resultId);
            emit('deleted', resultId);
        },
    });
};
</script>

<template>
    <!-- MAIN SUBJECTS -->
    <HeadingSmall
        :title="`${$t('trans.o_level_main_subjects')} (${requirements?.attributes?.mainSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <div class="my-4 flex flex-col space-y-4" v-if="hasMainSubjectsResults">
        <OLevelResultCard
            v-for="result in transformMainSubjectResults()"
            :result="result"
            :sitting-label="getExamSitting(String(result.id))"
            :key="result.id"
            :delete-callback="() => deleteCallback(result?.attributes?.resultId ?? '')"
            @deleted="handleResultDeleted"
        />
    </div>
    <Empty v-else />
    <!-- OTHER SUBJECTS -->
    <HeadingSmall
        :title="`${$t('trans.o_level_other_subjects')} (${requirements?.attributes?.otherSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />

    <div class="my-4 flex flex-col space-y-4" v-if="hasOtherSubjectsResults">
        <OLevelResultCard
            v-for="result in transformOtherSubjectResults()"
            :result="result"
            :sitting-label="getExamSitting(String(result.id))"
            :key="result.id"
            :delete-callback="() => deleteCallback(result?.attributes?.resultId ?? '')"
            @deleted="handleResultDeleted"
        />
    </div>
    <Empty v-else />
    <div v-if="additionalOtherSubjectsAllowed" class="mt-4 flex justify-center space-y-4">
        <BaseButton
            type="button"
            :title="$t('trans.ui_add_other_subjects')"
            classes="rounded-full"
            :variant="ColorVariant.primary_outline"
            @click="() => onCreateOrEdit(emptyEdit())"
        >
            <BaseIcon :name="IconName.add" />
        </BaseButton>
    </div>
</template>
