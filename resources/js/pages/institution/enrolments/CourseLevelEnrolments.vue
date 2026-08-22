<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert, forbiddenAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';
import DeficitInClassSize from '@/pages/institution/enrolments/partials/DeficitInClassSize.vue';
import EnrolmentApplicationsBrowser from '@/pages/institution/enrolments/partials/EnrolmentApplicationsBrowser.vue';
import GenderEnrolmentAccordionItem from '@/pages/institution/enrolments/partials/GenderEnrolmentAccordionItem.vue';
import PurgeClassListDialog from '@/pages/institution/enrolments/partials/PurgeClassListDialog.vue';
import ScoringFormula from '@/pages/institution/enrolments/partials/ScoringFormula.vue';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { WorkflowStep } from '@/types/settings';
import { Link as BreadcrumbLink } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { trans } from 'laravel-vue-i18n';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    course: { name?: string; department_course_id?: number | string } | null;
    workflowSteps: WorkflowStep[];
    intakePeriod: IntakePeriod;
    modeOfStudy: ModeOfStudy;
    auth: AuthObject;
    errors: object;
    intakePeriods?: IntakePeriod[];
    modesOfStudy?: ModeOfStudy[];
    enrolments: EnrolmentGroupResponse;
    classSize: string | number;
}

const props = defineProps<Props>();

const { department, level, enrolments, intakePeriod, modeOfStudy, course } = props;
const { isItTrue } = useUtils();
const { allocateClassSlots, classListIsCreated } = useEnrolments();

const intakeLimit = ref(Number(props.classSize) || 0);
watch(
    () => props.classSize,
    (value) => {
        intakeLimit.value = Number(value) || 0;
    },
);

const selectedIds = ref<Set<number>>(new Set());
const selectionBarRef = ref<HTMLElement | null>(null);
const showPurgeDialog = ref(false);
const purgeProcessing = ref(false);

const genderGroups = ['disabled', 'females', 'males'] as const;

const GROUP_ICONS: Record<(typeof genderGroups)[number], IconName> = {
    disabled: IconName.accessibility,
    females: IconName.venus,
    males: IconName.mars,
};

const firstNonEmptyGroup = genderGroups.find((group) => (enrolments.groups?.[group]?.length ?? 0) > 0) ?? '';
const openGenderGroup = ref(firstNonEmptyGroup);

const backUrl = computed(() =>
    route('institution-departments.show', {
        department: String(department.id),
        intake_period_id: String(intakePeriod.id),
        mode_of_study_id: String(modeOfStudy.id),
    }),
);

const breadcrumbs: Array<BreadcrumbLink> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: backUrl.value },
    { title: level.attributes.level, href: backUrl.value },
    { title: course?.name ?? '', href: backUrl.value },
    { transChoiceKey: 'application' },
];

const levelRequirements = computed(() => level?.relationships?.requirement);

const allApplications = computed((): EnrolmentApplication[] =>
    genderGroups.flatMap((group) => enrolments.groups?.[group] ?? []),
);

const eligibleApplications = computed(() => allApplications.value.filter((app) => !app.inClassList));

const alreadyInClassCount = computed(() => allApplications.value.filter((app) => app.inClassList).length);

const selectedCount = computed(() => selectedIds.value.size);

const selectedForAdd = computed(() =>
    allApplications.value.filter((app) => selectedIds.value.has(app.applicationId) && !app.inClassList),
);

const selectedForRemove = computed(() =>
    allApplications.value.filter((app) => selectedIds.value.has(app.applicationId) && app.inClassList),
);

const remainingSeats = computed(() => Math.max(intakeLimit.value - alreadyInClassCount.value, 0));

const deficit = computed(() => remainingSeats.value);

const exceedsClassSize = computed(() => selectedForAdd.value.length > remainingSeats.value);

const noData = computed(
    () =>
        (enrolments.groups?.disabled?.length ?? 0) === 0 &&
        (enrolments.groups?.females?.length ?? 0) === 0 &&
        (enrolments.groups?.males?.length ?? 0) === 0,
);

const totalApplications = computed(() => {
    return (enrolments.groups?.disabled?.length ?? 0) + (enrolments.groups?.females?.length ?? 0) + (enrolments.groups?.males?.length ?? 0);
});

const groupCount = (group: EnrolmentGroup): number => enrolments.groups?.[group]?.length ?? 0;

const getGroupSlot = (group: EnrolmentGroup): number => {
    const groups = enrolments?.groups ?? { disabled: [], females: [], males: [] };
    if (totalApplications.value > intakeLimit.value) {
        const { disabled, females, males } = allocateClassSlots(
            intakeLimit.value,
            groups.disabled.length,
            groups.females.length,
            groups.males.length,
        );
        const slots = { disabled, females, males };

        return slots[group] ?? 0;
    }

    return groups[group]?.length ?? 0;
};

const isSelected = (applicationId: number): boolean => selectedIds.value.has(applicationId);

const toggleSelection = (applicationId: number, checked: boolean) => {
    const next = new Set(selectedIds.value);
    if (checked) {
        next.add(applicationId);
    } else {
        next.delete(applicationId);
    }
    selectedIds.value = next;
};

const setGroupSelection = (applications: EnrolmentApplication[], checked: boolean) => {
    const next = new Set(selectedIds.value);
    applications
        .filter((app) => !app.inClassList)
        .forEach((app) => {
            if (checked) {
                next.add(app.applicationId);
            } else {
                next.delete(app.applicationId);
            }
        });
    selectedIds.value = next;
};

const clearSelection = () => {
    selectedIds.value = new Set();
};

const selectAllEligible = () => {
    selectedIds.value = new Set(eligibleApplications.value.map((app) => app.applicationId));
};

const onIntakeLimitSaved = (value: number) => {
    intakeLimit.value = value;
};

const bulkAddForm = useForm<{ application_ids: number[]; type: string }>({
    application_ids: [],
    type: 'provisional',
});

const purgeForm = useForm<{ application_ids: number[]; note: string }>({
    application_ids: [],
    note: '',
});

async function bulkAddSelected() {
    if (!hasAbility('create:class-lists')) {
        forbiddenAlert();
        return;
    }

    const ids = selectedForAdd.value.map((app) => app.applicationId);
    if (ids.length === 0) {
        errorAlert(trans('trans.ui_select_all_eligible'));
        return;
    }

    let message = `Add ${ids.length} application(s) to the provisional class list?`;
    if (exceedsClassSize.value) {
        message += ` Intake limit is ${intakeLimit.value} with ${alreadyInClassCount.value} already listed — this exceeds remaining seats (guidance only).`;
    }

    const confirmed = await useCustomConfirmDialog().open({
        title: trans('trans.ui_add_to_class_list'),
        message,
        confirmText: 'Please continue',
        note: exceedsClassSize.value ? trans('trans.ui_class_size_guidance_exceeded') : undefined,
    });

    if (!confirmed) {
        return;
    }

    bulkAddForm.application_ids = ids;
    bulkAddForm.post(route('enrolments.bulk-add-to-class-list'), {
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
        },
        onError: (errors) => {
            const messageText = Object.keys(errors).length
                ? Object.values(errors).join('\n')
                : 'Could not add applications to the class list';
            errorAlert(messageText);
        },
    });
}

function openPurgeDialog() {
    if (!hasAbility('delete:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (selectedForRemove.value.length === 0) {
        errorAlert(trans('trans.ui_remove_from_class_list'));
        return;
    }
    showPurgeDialog.value = true;
}

function confirmPurge(note: string) {
    const ids = selectedForRemove.value.map((app) => app.applicationId);
    purgeProcessing.value = true;
    purgeForm.application_ids = ids;
    purgeForm.note = note;
    purgeForm.post(route('enrolments.purge-class-list'), {
        preserveScroll: true,
        onSuccess: () => {
            showPurgeDialog.value = false;
            clearSelection();
        },
        onError: (errors) => {
            const messageText = Object.keys(errors).length
                ? Object.values(errors).join('\n')
                : 'Could not purge class list entries';
            errorAlert(messageText);
        },
        onFinish: () => {
            purgeProcessing.value = false;
        },
    });
}

watch(selectedCount, (count, previous) => {
    if (count > 0 && previous === 0) {
        selectionBarRef.value?.focus();
    }
});
</script>

<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="backUrl">
        <template #backNavigationLeading>
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold uppercase leading-tight sm:text-lg">{{ course?.name }}</h2>
                <p class="truncate text-xs text-muted-foreground sm:text-sm">
                    {{ level.attributes.level }} · {{ modeOfStudy.attributes.name }}
                    <span class="text-muted-foreground/80"> · {{ intakePeriod.attributes.name }}</span>
                </p>
            </div>
        </template>

        <div class="my-3 flex flex-col gap-3">
            <BaseAlert
                v-if="noData"
                :title="$t('trans.no_data')"
                :description="
                    $t('trans.no_data_found_description', {
                        data: `${$tChoice('trans.application', 2)} for ${intakePeriod.attributes.name} - ${modeOfStudy.attributes.name}`,
                    })
                "
            />

            <template v-if="!noData">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <ClassSize
                        :class-size="intakeLimit"
                        :editable="true"
                        :department-id="String(department.id)"
                        :intake-period-id="intakePeriod.id"
                        :mode-of-study-id="modeOfStudy.id"
                        :department-course-id="course?.department_course_id"
                        :department-level-id="level.id"
                        @saved="onIntakeLimitSaved"
                    />
                    <DeficitInClassSize :deficit="deficit" />
                </div>

                <ScoringFormula v-if="isItTrue(levelRequirements?.attributes?.isOLevelRequired)" />
            </template>

            <div
                v-if="selectedCount > 0"
                ref="selectionBarRef"
                tabindex="-1"
                class="sticky top-2 z-20 flex flex-col gap-2 rounded-lg border border-primary/30 bg-card p-3 shadow-md sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-sm">
                    <span class="font-semibold">{{ selectedCount }}</span>
                    {{ $t('trans.ui_selected') }}
                    <span class="text-muted-foreground">
                        · {{ $tChoice('trans.class_list', 1) }} {{ alreadyInClassCount }}/{{ intakeLimit }}
                        <template v-if="exceedsClassSize">
                            · <span class="font-medium text-amber-700">{{ $t('trans.ui_class_size_guidance_exceeded') }}</span>
                        </template>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.shade"
                        :title="$t('trans.ui_select_all_eligible')"
                        classes="rounded-full"
                        @click="selectAllEligible"
                    />
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.shade"
                        :title="$t('trans.ui_clear_selection')"
                        classes="rounded-full"
                        @click="clearSelection"
                    />
                    <BaseButton
                        v-if="selectedForAdd.length > 0"
                        type="button"
                        :variant="ColorVariant.primary"
                        :title="$t('trans.ui_add_to_class_list')"
                        classes="rounded-full"
                        :disabled="bulkAddForm.processing"
                        @click="bulkAddSelected"
                    />
                    <BaseButton
                        v-if="selectedForRemove.length > 0"
                        type="button"
                        :variant="ColorVariant.warning"
                        :title="$t('trans.ui_remove_from_class_list')"
                        classes="rounded-full"
                        @click="openPurgeDialog"
                    />
                </div>
            </div>

            <PurgeClassListDialog
                :open="showPurgeDialog"
                :count="selectedForRemove.length"
                :processing="purgeProcessing"
                @closed="showPurgeDialog = false"
                @confirm="confirmPurge"
            />

            <BaseAccordion v-if="!noData" v-model="openGenderGroup" type="single" :collapsible="true" class="w-full gap-3">
                <GenderEnrolmentAccordionItem
                    v-for="group in genderGroups"
                    :key="group"
                    :value="group"
                    :title="group"
                    :count="groupCount(group)"
                    :icon="GROUP_ICONS[group]"
                    :is-open="openGenderGroup === group"
                >
                    <EnrolmentApplicationsBrowser
                        :level="level"
                        :department-id="String(department?.id)"
                        :applications="enrolments.groups[group]"
                        :class-size="intakeLimit"
                        :slot-size="getGroupSlot(group)"
                        :is-o-level="isItTrue(levelRequirements?.attributes?.isOLevelRequired)"
                        :class-list-created="classListIsCreated(enrolments)"
                        :selected-ids="selectedIds"
                        :is-selected="isSelected"
                        @toggle="toggleSelection"
                        @select-group="setGroupSelection"
                    />
                </GenderEnrolmentAccordionItem>
            </BaseAccordion>
        </div>
    </PageContainer>
</template>
