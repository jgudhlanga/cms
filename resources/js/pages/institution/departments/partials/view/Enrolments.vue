<script setup lang="ts">
import DepartmentEnrolmentModeBrowser from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import type { DepartmentEnrolmentLevelHrefContext } from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import EnrolmentFilters from '@/pages/institution/enrolments/partials/EnrolmentFilters.vue';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id ?? '');
const { getQueryParams } = useUtils();

const intakePeriod = ref<SelectOption | null>(null);
const openModeId = ref('');
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();

const syncUrlParams = () => {
    const params: Record<string, string> = {};
    if (intakePeriod.value?.value) {
        params.intake_period_id = String(intakePeriod.value.value);
    }
    if (openModeId.value) {
        params.mode_of_study_id = openModeId.value;
    }

    router.get(route('institution-departments.show', { department: institutionDepartmentId }), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleIntakeChange = () => {
    syncUrlParams();
};

const onModeChange = (modeId: string) => {
    openModeId.value = modeId;
    syncUrlParams();
};

const resolveLevelHref = (context: DepartmentEnrolmentLevelHrefContext): string =>
    route('department-levels.enrolments', {
        institution_department: institutionDepartmentId,
        department_level: context.departmentLevelId,
        intake_period_id: intakePeriod.value?.value.toString(),
        mode_of_study_id: context.modeOfStudyId,
        department_course_id: context.departmentCourseId,
    });

onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);

    const query = getQueryParams();
    const intakeOption =
        intakePeriods.value?.data?.find((row) => String(row.id) === String(query.intake_period_id)) ??
        intakePeriods.value?.data?.[0] ??
        null;
    intakePeriod.value = intakeOption
        ? { value: Number(intakeOption.id), label: intakeOption.attributes.name }
        : null;
    openModeId.value = query.mode_of_study_id ? String(query.mode_of_study_id) : '';
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <EnrolmentFilters
            v-model:intakePeriodModel="intakePeriod"
            :intake-periods="intakePeriods?.data ?? []"
            :show-mode-of-study="false"
            :handle-filter-change="handleIntakeChange"
        />

        <DataLoadingSpinner v-if="intakePeriodsLoading" />
        <DepartmentEnrolmentModeBrowser
            v-else-if="intakePeriod"
            :department-id="institutionDepartmentId"
            :intake-period-id="String(intakePeriod.value)"
            :initial-mode-of-study-id="openModeId || null"
            summaries-route-name="v1.department-metadata.enrolments"
            :resolve-level-href="resolveLevelHref"
            @update:mode-of-study-id="onModeChange"
        />
    </div>
</template>
