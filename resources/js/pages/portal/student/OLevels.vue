<script setup lang="ts">
// External imports
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

// Internal components
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import CreateEdit from '@/components/students/oLevels/modals/CreateEdit.vue';
import OLevelResultCard from '@/components/students/oLevels/OLevelResultCard.vue';

// Composable
import { useUtils } from '@/composables/core/useUtils';
import { useOLevelResults } from '@/composables/students/useOLevelResults';

// Types & constants
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { EXAM_SITTINGS } from '@/lib/constants';
import type { AuthObject } from '@/types/data-pagination';
import type { OLevelSubjectResult } from '@/types/enrolments';
import type { Student } from '@/types/students';
import type { BreadcrumbItemInterface } from '@/types/ui';

// Props definition
interface Props {
    auth: AuthObject;
    errors: object;
    student: Student;
}
const props = defineProps<Props>();

// State
const oLevelResults = ref<OLevelSubjectResult[] | null>(null);
const examSittings = ref(EXAM_SITTINGS);

// Composable
const { navigateTo, isItTrue } = useUtils();
const { loadStudentOLevelResults, isLoading } = useOLevelResults();

// Breadcrumbs
const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: route('portal.dashboard') }, { title: 'O-Levels' }];

// Helper: Find result by subject ID
const findResultBySubjectId = (subjectId: string) => {
    return oLevelResults.value?.find((r) => String(r.id) === subjectId);
};

// Helper: Get exam sitting label for a subject
const getExamSittingLabel = (subjectId: string) => {
    const result = findResultBySubjectId(subjectId);
    const sitting = examSittings.value.find((s) => String(s.value) === String(result?.attributes?.examSitting));
    return sitting ? String(sitting.label) : '---';
};

// Load results on mount
onMounted(async () => {
    oLevelResults.value = await loadStudentOLevelResults(String(props.student.id));
});

// Computed: Check if there are results
const hasResults = computed(() => Array.isArray(oLevelResults.value) && oLevelResults.value.length > 0);

// Methods
const reloadResults = async () => {
    oLevelResults.value = await loadStudentOLevelResults(String(props.student.id));
};
const verificationMode = isItTrue(import.meta.env.VITE_VERIFICATION_MODE);
</script>
<template>
    <Head :title="$t('trans.ui_o_level')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-6 flex items-center justify-between">
            <HeadingSmall
                :title="$t('trans.ui_o_level_results')"
                :description="$t('trans.ui_list_of_o_level_subjects_and_grades_attained_by_a_student')"
            />
            <BaseButton
                v-if="!verificationMode"
                classes="rounded-full"
                :variant="ColorVariant.primary_outline"
                @click="navigateTo(route('portal.manage-o-level-results'))"
            >
                <BaseIcon :name="IconName.cogs" />
                <span>{{ $t('trans.ui_add_new') }}</span>
            </BaseButton>
        </div>
        <template v-if="isLoading">
            <DataLoadingSpinner />
        </template>
        <template v-else>
            <template v-if="hasResults">
                <div class="flex flex-col space-y-4">
                    <OLevelResultCard
                        v-for="result in oLevelResults"
                        :result="result"
                        :sitting-label="getExamSittingLabel(String(result.id))"
                        :key="result.id"
                        @deleted="reloadResults"
                    />
                </div>
            </template>
            <Empty v-else />
        </template>
        <CreateEdit @saved="reloadResults" />
    </PageContainer>
</template>
