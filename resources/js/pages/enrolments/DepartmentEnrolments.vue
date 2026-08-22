<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import DepartmentEnrolmentModeBrowser from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import type { DepartmentEnrolmentLevelHrefContext } from '@/components/enrolments/DepartmentEnrolmentModeBrowser.vue';
import EnrolmentApplicantLookupDrawer from '@/components/enrolments/EnrolmentApplicantLookupDrawer.vue';
import { useUtils } from '@/composables/core/useUtils';
import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusOriginBackUrl,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';
import DepartmentContextBar from '@/pages/institution/departments/partials/DepartmentContextBar.vue';
import { InstitutionDepartment, IntakePeriod } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
    intakePeriod?: IntakePeriod | null;
}

const props = defineProps<Props>();
const { getQueryParams } = useUtils();
const queryParams = getQueryParams();

const institutionDepartmentId = computed(() => String(props.department?.id ?? ''));
const intakePeriodId = computed(() => queryParams['intake_period_id'] ?? null);
const modeOfStudyId = computed(() => queryParams['mode_of_study_id'] ?? null);
const listType = computed(() => queryParams['type'] ?? '');
const from = computed(() => parseEnrolmentStatusFrom(queryParams['from']));

const backUrl = computed(() => enrolmentStatusOriginBackUrl(from.value, intakePeriodId.value));

const listTypeLabel = computed(() => (listType.value ? `${listType.value} applications` : ''));
const lookupOpen = ref(false);

const switchDepartmentForm = useForm({
    department: null,
});
const selectedDepartment = ref<SelectOption>({
    value: Number(props.department.id ?? 0),
    label: props.department.attributes?.department ?? '',
});

watch(selectedDepartment, (nextDepartment) => {
    const selectedDepartmentId = Number(nextDepartment?.value ?? 0);
    const currentDepartmentId = Number(props.department.id ?? 0);

    if (selectedDepartmentId <= 0 || selectedDepartmentId === currentDepartmentId) {
        return;
    }

    const currentQuery = getQueryParams();

    router.get(
        buildDepartmentApplicationsUrl({
            institutionDepartmentId: selectedDepartmentId,
            type: currentQuery['type'] ?? listType.value,
            intakePeriodId: currentQuery['intake_period_id'] ?? intakePeriodId.value,
            modeOfStudyId: currentQuery['mode_of_study_id'] ?? modeOfStudyId.value,
            from: currentQuery['from'] ?? queryParams['from'],
        }),
    );
});

watch(
    () => props.department.id,
    (departmentId) => {
        selectedDepartment.value = {
            value: Number(departmentId ?? 0),
            label: props.department.attributes?.department ?? '',
        };
    },
);

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
        { title: props.department.attributes.department, href: backUrl.value },
        { title: listTypeLabel.value },
    ];
});

const resolveLevelHref = (context: DepartmentEnrolmentLevelHrefContext): string =>
    route('enrolments.class-lists', {
        institution_department: institutionDepartmentId.value,
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
            institutionDepartmentId: institutionDepartmentId.value,
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
        <template #backNavigationLeading>
            <DepartmentContextBar
                :department="department"
                :form="switchDepartmentForm"
                v-model="selectedDepartment"
            />
        </template>

        <template #backNavigationTrailing>
            <button
                type="button"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                @click="lookupOpen = true"
            >
                <Search class="h-3.5 w-3.5 shrink-0" />
                {{ $t('enrolments.find_applicant') }}
            </button>
        </template>

        <DepartmentEnrolmentModeBrowser
            :key="institutionDepartmentId"
            :department-id="institutionDepartmentId"
            :intake-period-id="intakePeriodId"
            :type="listType || null"
            :initial-mode-of-study-id="modeOfStudyId"
            summaries-route-name="v1.department-metadata.class-lists"
            :resolve-level-href="resolveLevelHref"
            @update:mode-of-study-id="syncModeToUrl"
        />

        <EnrolmentApplicantLookupDrawer
            v-model:open="lookupOpen"
            :list-type="listType"
            :intake-period-id="intakePeriodId ?? ''"
            :intake-period-name="intakePeriod?.attributes?.name ?? ''"
            :from="queryParams['from']"
            :initial-department-id="department.id"
        />
    </PageContainer>
</template>
