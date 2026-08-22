<script setup lang="ts">
import DepartmentEnrolmentModeBrowser from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import type { DepartmentEnrolmentLevelHrefContext } from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import { useUtils } from '@/composables/core/useUtils';
import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusOriginBackUrl,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';
import { InstitutionDepartment } from '@/types/institution';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id ?? '');
const { getQueryParams } = useUtils();
const queryParams = getQueryParams();

const intakePeriodId = computed(() => queryParams['intake_period_id'] ?? null);
const listType = computed(() => queryParams['type'] ?? '');
const from = computed(() => parseEnrolmentStatusFrom(queryParams['from']));

const backUrl = computed(() => enrolmentStatusOriginBackUrl(from.value, intakePeriodId.value));

const listTypeLabel = computed(() => (listType.value ? `${listType.value} applications` : ''));

const breadcrumbs = computed(() => {
    const originCrumb =
        from.value === 'dashboard'
            ? { transKey: 'dashboard', href: backUrl.value }
            : { transChoiceKey: 'trans.application', href: backUrl.value };

    return [
        from.value === 'dashboard'
            ? originCrumb
            : { transKey: 'dashboard', href: route('dashboard') },
        ...(from.value === 'dashboard' ? [] : [originCrumb]),
        { title: department.attributes.department, href: backUrl.value },
        { title: listTypeLabel.value },
    ];
});

const resolveLevelHref = (context: DepartmentEnrolmentLevelHrefContext): string =>
    route('enrolments.class-lists', {
        institution_department: institutionDepartmentId,
        department_level: context.departmentLevelId,
        intake_period_id: intakePeriodId.value ?? undefined,
        mode_of_study_id: context.modeOfStudyId,
        department_course_id: context.departmentCourseId,
        type: listType.value || undefined,
        from: queryParams['from'] || undefined,
    });

const syncModeToUrl = (modeId: string) => {
    router.get(
        buildDepartmentApplicationsUrl({
            institutionDepartmentId,
            type: listType.value,
            intakePeriodId: intakePeriodId.value,
            modeOfStudyId: modeId,
            from: queryParams['from'],
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
</script>

<template>
    <Head :title="listTypeLabel || $tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <DepartmentEnrolmentModeBrowser
            :department-id="institutionDepartmentId"
            :intake-period-id="intakePeriodId"
            :type="listType || null"
            :initial-mode-of-study-id="queryParams['mode_of_study_id'] ?? null"
            summaries-route-name="v1.department-metadata.class-lists"
            :resolve-level-href="resolveLevelHref"
            @update:mode-of-study-id="syncModeToUrl"
        />
    </PageContainer>
</template>
