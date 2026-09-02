<script setup lang="ts">
import EnrolmentApplicantLookupDrawer from '@/components/enrolments/EnrolmentApplicantLookupDrawer.vue';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { IconName } from '@/enums/icons';
import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusOriginBackUrl,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';
import { filterEnrolmentApplications, toTitleCase } from '@/lib/enrolmentClassListPresentation';
import { withRankingRequirement } from '@/lib/resolveEffectiveEnrolmentRequirements';
import ClassListTable from '@/pages/enrolments/partials/ClassListTable.vue';
import GenderEnrolmentAccordionItem from '@/pages/institution/enrolments/partials/GenderEnrolmentAccordionItem.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel, CourseRequirement } from '@/types/department-meta-data';
import { ClassListType, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { trans_choice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: { name?: string; department_course_id?: number | string } | null;
    intakePeriod: IntakePeriod;
    modeOfStudy: ModeOfStudy;
    auth: AuthObject;
    errors: object;
    intakePeriods: IntakePeriod[];
    modesOfStudy: ModeOfStudy[];
    enrolments: EnrolmentGroupResponse;
    classSize: string | number;
    courseRequirement?: CourseRequirement | null;
}

const props = defineProps<Props>();

const { department, level, enrolments, intakePeriod, modeOfStudy, course } = props;
const { applyPolicyAlgorithmToApplications } = useEnrolments();
const { getQueryParams, isItTrue } = useUtils();
const queryParams = getQueryParams();
const from = parseEnrolmentStatusFrom(queryParams['from']);
const originBackUrl = enrolmentStatusOriginBackUrl(from, String(intakePeriod.id));

const search = ref('');
const lookupOpen = ref(false);
const genderGroups = ['disabled', 'females', 'males'] as const;

const GROUP_ICONS: Record<(typeof genderGroups)[number], IconName> = {
    disabled: IconName.accessibility,
    females: IconName.venus,
    males: IconName.mars,
};

const listTypeLabel = computed(() => {
    const type = String(queryParams['type'] ?? '').trim();

    if (type) {
        return toTitleCase(`${type} ${trans_choice('trans.application', 2)}`);
    }

    return trans_choice('trans.class_list', 1);
});

const departmentApplicationsUrl = computed(() =>
    buildDepartmentApplicationsUrl({
        institutionDepartmentId: department.id,
        type: String(queryParams['type'] ?? ''),
        intakePeriodId: String(intakePeriod.id),
        modeOfStudyId: String(modeOfStudy.id),
        from: queryParams['from'],
    }),
);

const breadcrumbs = computed(() => [
    from === 'dashboard'
        ? { transKey: 'dashboard', href: originBackUrl }
        : { transKey: 'dashboard', href: route('dashboard') },
    ...(from === 'dashboard' ? [] : [{ transChoiceKey: 'trans.application', href: originBackUrl }]),
    { title: department.attributes.department, href: departmentApplicationsUrl.value },
    { title: level.attributes.level, href: departmentApplicationsUrl.value },
    { title: course?.name ?? '', href: departmentApplicationsUrl.value },
    { title: listTypeLabel.value },
]);

const rankingLevel = computed(() => withRankingRequirement(props.level, props.courseRequirement));
const isOLevel = computed(() => isItTrue(rankingLevel.value?.relationships?.requirement?.attributes?.isOLevelRequired));
const classListType = computed(() => (queryParams['type'] as ClassListType) || undefined);

const groupApplications = (group: EnrolmentGroup) => {
    const applications = enrolments.groups?.[group] ?? [];

    if (isOLevel.value) {
        return applyPolicyAlgorithmToApplications(applications, rankingLevel.value);
    }

    return applications;
};

const filteredGroupApplications = (group: EnrolmentGroup) =>
    filterEnrolmentApplications(groupApplications(group), search.value);

const groupCount = (group: EnrolmentGroup) => filteredGroupApplications(group).length;

const noData = computed(() => genderGroups.every((group) => (enrolments.groups?.[group]?.length ?? 0) === 0));

const hasSearchResults = computed(() => genderGroups.some((group) => groupCount(group) > 0));

const visibleGenderGroups = computed(() => genderGroups.filter((group) => groupCount(group) > 0));

const firstNonEmptyGroup = genderGroups.find((group) => (enrolments.groups?.[group]?.length ?? 0) > 0) ?? '';
const openGenderGroup = ref(firstNonEmptyGroup);

watch(
    [search, visibleGenderGroups],
    () => {
        if (visibleGenderGroups.value.length === 0) {
            return;
        }

        if (!visibleGenderGroups.value.includes(openGenderGroup.value as (typeof genderGroups)[number])) {
            openGenderGroup.value = visibleGenderGroups.value[0];
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="listTypeLabel" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentApplicationsUrl">
        <template #backNavigationLeading>
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold uppercase leading-tight sm:text-lg">{{ course?.name }}</h2>
                <p class="truncate text-xs text-muted-foreground sm:text-sm">
                    {{ level.attributes.level }} · {{ modeOfStudy.attributes.name }} · {{ listTypeLabel }}
                </p>
            </div>
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

        <div class="my-3 flex flex-col gap-3">
            <BaseAlert
                v-if="noData"
                :title="$t('trans.no_data')"
                :description="
                    $t('trans.no_data_found_description', {
                        data: `${$tChoice('trans.class_list', 2)} for ${intakePeriod.attributes.name} - ${modeOfStudy.attributes.name}`,
                    })
                "
            />

            <template v-if="!noData">
                <div class="flex flex-col gap-1.5">
                    <label class="relative block">
                        <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="search"
                            class="h-10 w-full rounded-full border border-border bg-card pr-3 pl-9 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                            :placeholder="$t('enrolments.class_list_search_placeholder')"
                            :aria-label="$t('enrolments.class_list_search_placeholder')"
                        />
                    </label>
                    <p class="text-xs text-muted-foreground">
                        {{ $t('enrolments.class_list_search_hint', { department: department.attributes.department }) }}
                    </p>
                </div>

                <div
                    v-if="!hasSearchResults"
                    class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    {{ $t('enrolments.class_list_search_no_results') }}
                </div>

                <BaseAccordion v-else v-model="openGenderGroup" type="single" :collapsible="true" class="w-full gap-3">
                    <GenderEnrolmentAccordionItem
                        v-for="group in visibleGenderGroups"
                        :key="group"
                        :value="group"
                        :title="group"
                        :count="groupCount(group)"
                        :icon="GROUP_ICONS[group]"
                        :is-open="openGenderGroup === group"
                    >
                        <ClassListTable
                            :level="rankingLevel"
                            :is-o-level="isOLevel"
                            :class-list-type="classListType"
                            :department-id="String(department?.id)"
                            :applications="filteredGroupApplications(group)"
                        />
                    </GenderEnrolmentAccordionItem>
                </BaseAccordion>
            </template>
        </div>

        <EnrolmentApplicantLookupDrawer
            v-model:open="lookupOpen"
            :list-type="classListType ?? 'provisional'"
            :intake-period-id="intakePeriod.id"
            :intake-period-name="intakePeriod.attributes.name"
            :from="queryParams['from']"
            :initial-department-id="department.id"
            :initial-level-id="level.id"
            :initial-course-id="course?.department_course_id"
        />
    </PageContainer>
</template>
