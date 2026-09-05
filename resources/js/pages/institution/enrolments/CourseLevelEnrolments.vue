<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ButtonSize } from '@/enums/buttons';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { closeModal, errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import {
    getClassListTypeFromRank,
    occupiesIntakeSeat,
} from '@/lib/enrolmentClassListPresentation';
import { hasAbility } from '@/lib/permissions';
import { withRankingRequirement } from '@/lib/resolveEffectiveEnrolmentRequirements';
import ClassListActionDialog from '@/pages/institution/enrolments/partials/ClassListActionDialog.vue';
import type { ClassListActionPayload } from '@/pages/institution/enrolments/partials/ClassListActionDialog.vue';
import ClassSize from '@/pages/institution/enrolments/partials/ClassSize.vue';
import DeficitInClassSize from '@/pages/institution/enrolments/partials/DeficitInClassSize.vue';
import EnrolmentApplicationsBrowser from '@/pages/institution/enrolments/partials/EnrolmentApplicationsBrowser.vue';
import GenderEnrolmentAccordionItem from '@/pages/institution/enrolments/partials/GenderEnrolmentAccordionItem.vue';
import ScoringFormula from '@/pages/institution/enrolments/partials/ScoringFormula.vue';
import ReassignProgrammeDialog from '@/components/students/programme/ReassignProgrammeDialog.vue';
import { canReassignProgramme, useReassignProgramme } from '@/composables/students/useReassignProgramme';
import { useEnrolmentClassListUiStore } from '@/store/enrolments/useEnrolmentClassListUiStore';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentLevel, CourseRequirement } from '@/types/department-meta-data';
import { EnrolmentApplication, EnrolmentGroup, EnrolmentGroupResponse } from '@/types/enrolments';
import { InstitutionDepartment, IntakePeriod, ModeOfStudy } from '@/types/institution';
import { WorkflowStep } from '@/types/settings';
import { Link as BreadcrumbLink } from '@/types/ui';
import { Head, router, useForm } from '@inertiajs/vue3';
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
    courseRequirement?: CourseRequirement | null;
}

const props = defineProps<Props>();

const { department, level, intakePeriod, modeOfStudy, course } = props;
const enrolments = computed(() => props.enrolments);
const { isItTrue } = useUtils();
const { allocateClassSlots, classListIsCreatedForGroup, applyPolicyAlgorithmToApplications } = useEnrolments();
const canMoveProgramme = computed(() => canReassignProgramme());
const {
    form: reassignForm,
    records: reassignRecords,
    loadingRecords: reassignLoadingRecords,
    selectedApplicationIds: reassignSelectedIds,
    hydratingDefaults: reassignHydratingDefaults,
    openReassignProgrammeDialog,
    submitReassignProgramme,
} = useReassignProgramme();

const intakeLimit = ref(Number(props.classSize) || 0);
watch(
    () => props.classSize,
    (value) => {
        intakeLimit.value = Number(value) || 0;
    },
);

const selectedIds = ref<Set<number>>(new Set());
const actionProcessing = ref(false);
const pendingAction = ref<ClassListActionPayload | null>(null);

type SelectionMode = 'add' | 'remove' | 'any';

const genderGroups = ['disabled', 'females', 'males'] as const;

const GROUP_ICONS: Record<(typeof genderGroups)[number], IconName> = {
    disabled: IconName.accessibility,
    females: IconName.venus,
    males: IconName.mars,
};

const enrolmentUiStore = useEnrolmentClassListUiStore();
const enrolmentContextKey = computed(() =>
    enrolmentUiStore.contextKey({
        departmentId: department.id,
        levelId: level.id,
        courseId: course?.department_course_id,
        intakePeriodId: intakePeriod.id,
        modeOfStudyId: modeOfStudy.id,
    }),
);

const firstNonEmptyGroup =
    genderGroups.find((group) => (enrolments.value.groups?.[group]?.length ?? 0) > 0) ?? '';

const openGenderGroup = computed({
    get: () => enrolmentUiStore.getOpenGenderGroup(enrolmentContextKey.value, firstNonEmptyGroup),
    set: (group: string) => enrolmentUiStore.setOpenGenderGroup(enrolmentContextKey.value, group),
});

if (!enrolmentUiStore.openGenderGroupByContext[enrolmentContextKey.value] && firstNonEmptyGroup) {
    enrolmentUiStore.setOpenGenderGroup(enrolmentContextKey.value, firstNonEmptyGroup);
}

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

const rankingLevel = computed(() => withRankingRequirement(props.level, props.courseRequirement));
const rankingRequirements = computed(() => rankingLevel.value.relationships?.requirement);
const isOLevel = computed(() => isItTrue(rankingRequirements.value?.attributes?.isOLevelRequired));

const allApplications = computed((): EnrolmentApplication[] =>
    genderGroups.flatMap((group) => enrolments.value.groups?.[group] ?? []),
);

const eligibleApplications = computed(() => allApplications.value.filter((app) => !app.inClassList));

const alreadyInClassCount = computed(() =>
    allApplications.value.filter((app) => app.inClassList && occupiesIntakeSeat(app.classListType)).length,
);

const selectedCount = computed(() => selectedIds.value.size);

const selectedForAdd = computed(() =>
    allApplications.value.filter((app) => selectedIds.value.has(Number(app.applicationId)) && !app.inClassList),
);

const selectedForRemove = computed(() =>
    allApplications.value.filter(
        (app) =>
            selectedIds.value.has(Number(app.applicationId)) && app.inClassList && app.classListType !== 'final',
    ),
);

/** Same transition choices as the row ⋯ menu, intersected across the current selection. */
const bulkTransitionActions = computed(() => {
    const apps = selectedForRemove.value;
    if (apps.length === 0) {
        return [] as Array<{ to: string; label: string }>;
    }

    const optionsFor = (type: string): Array<{ to: string; label: string }> => {
        if (type === 'final') {
            return [];
        }

        return [
            {
                to: 'provisional',
                label: 'To provisional',
                show:
                    ((type === 'waiting' || type === 'failed') && hasAbility('create:class-lists')) ||
                    (type === 'verified' && hasAbility('verify:class-lists')),
            },
            {
                to: 'waiting',
                label: 'To waiting',
                show: type === 'provisional' && hasAbility('create:class-lists'),
            },
            {
                to: 'verified',
                label: 'To verified',
                show: (type === 'provisional' || type === 'waiting') && hasAbility('verify:class-lists'),
            },
            {
                to: 'final',
                label: 'To final',
                show: type === 'verified' && hasAbility('manage-final:class-lists'),
            },
            {
                to: 'failed',
                label: 'Fail',
                show: ['provisional', 'waiting', 'verified'].includes(type) && hasAbility('create:class-lists'),
            },
        ]
            .filter((row) => row.show)
            .map(({ to, label }) => ({ to, label }));
    };

    const sets = apps.map((app) => new Set(optionsFor(app.classListType ?? '').map((row) => row.to)));
    const shared = [...sets[0]].filter((to) => sets.every((set) => set.has(to)));
    const labelByTo = new Map(
        apps.flatMap((app) => optionsFor(app.classListType ?? '').map((row) => [row.to, row.label] as const)),
    );

    return shared.map((to) => ({ to, label: labelByTo.get(to) ?? `To ${to}` }));
});

const remainingSeats = computed(() => Math.max(intakeLimit.value - alreadyInClassCount.value, 0));

const deficit = computed(() => remainingSeats.value);

const rankedGroupApplications = (group: EnrolmentGroup): EnrolmentApplication[] => {
    const apps = enrolments.value.groups?.[group] ?? [];
    if (isOLevel.value) {
        return applyPolicyAlgorithmToApplications(apps, rankingLevel.value);
    }

    return [...apps].sort(
        (a, b) => new Date(a.applicationDate).getTime() - new Date(b.applicationDate).getTime(),
    );
};

const splitSelectedForAdd = (): { provisionalIds: number[]; waitingIds: number[] } => {
    const provisionalIds: number[] = [];
    const waitingIds: number[] = [];

    genderGroups.forEach((group) => {
        const ranked = rankedGroupApplications(group);
        const slot = getGroupSlot(group);
        const groupListCreated = classListIsCreatedForGroup(enrolments.value, group);

        ranked.forEach((app, index) => {
            if (!selectedIds.value.has(Number(app.applicationId)) || app.inClassList) {
                return;
            }

            if (groupListCreated) {
                provisionalIds.push(Number(app.applicationId));
                return;
            }

            if (group === 'disabled') {
                provisionalIds.push(Number(app.applicationId));
                return;
            }

            const type = getClassListTypeFromRank(index, slot);
            if (type === 'provisional') {
                provisionalIds.push(Number(app.applicationId));
            } else if (type === 'waiting') {
                waitingIds.push(Number(app.applicationId));
            }
        });
    });

    return { provisionalIds, waitingIds };
};

const reloadEnrolmentsView = () => {
    router.reload({
        only: ['enrolments', 'classSize'],
        preserveScroll: true,
        preserveState: true,
    });
};

const noData = computed(
    () =>
        (enrolments.value.groups?.disabled?.length ?? 0) === 0 &&
        (enrolments.value.groups?.females?.length ?? 0) === 0 &&
        (enrolments.value.groups?.males?.length ?? 0) === 0,
);

const totalApplications = computed(() => {
    return (
        (enrolments.value.groups?.disabled?.length ?? 0) +
        (enrolments.value.groups?.females?.length ?? 0) +
        (enrolments.value.groups?.males?.length ?? 0)
    );
});

const groupCount = (group: EnrolmentGroup): number => enrolments.value.groups?.[group]?.length ?? 0;

const getGroupSlot = (group: EnrolmentGroup): number => {
    const groups = enrolments.value?.groups ?? { disabled: [], females: [], males: [] };
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

const isSelected = (applicationId: number): boolean => selectedIds.value.has(Number(applicationId));

const toggleSelection = (applicationId: number, checked: boolean) => {
    const id = Number(applicationId);
    const next = new Set(selectedIds.value);
    if (checked) {
        next.add(id);
    } else {
        next.delete(id);
    }
    selectedIds.value = next;
};

const setGroupSelection = (applications: EnrolmentApplication[], checked: boolean, mode: SelectionMode = 'any') => {
    const next = new Set(selectedIds.value);
    const filtered = applications.filter((app) => {
        if (app.classListType === 'final') {
            return false;
        }
        if (mode === 'add') {
            return !app.inClassList;
        }
        if (mode === 'remove') {
            return app.inClassList;
        }
        return true;
    });

    filtered.forEach((app) => {
        const id = Number(app.applicationId);
        if (checked) {
            next.add(id);
        } else {
            next.delete(id);
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

const openProgrammeReassign = () => {
    const ids =
        selectedCount.value > 0
            ? Array.from(selectedIds.value)
            : allApplications.value.map((app) => Number(app.applicationId));
    void openReassignProgrammeDialog({ applicationIds: ids });
};

const mutationContext = () => ({
    institution_department_id: Number(department.id),
    department_level_id: Number(level.id),
    department_course_id: Number(course?.department_course_id ?? 0) || undefined,
    intake_period_id: Number(intakePeriod.id),
    mode_of_study_id: Number(modeOfStudy.id),
});

const bulkAddForm = useForm({
    application_ids: [] as number[],
    waiting_application_ids: [] as number[],
    type: 'provisional',
    note: '' as string | undefined,
    bypass_ranking: false,
    ...mutationContext(),
});

const transitionForm = useForm({
    application_ids: [] as number[],
    to_type: 'provisional',
    note: '' as string | undefined,
    bypass_ranking: false,
    ...mutationContext(),
});

const purgeForm = useForm({
    application_ids: [] as number[],
    note: '',
    ...mutationContext(),
});

const openActionDialog = (action: ClassListActionPayload) => {
    pendingAction.value = action;
    openModal({ name: APP_MODULE_KEYS.class_list_action, edit: action });
};

const queueAdd = (
    applicationIds: number[],
    bypassRanking: boolean,
    waitingApplicationIds: number[] = [],
) => {
    if (!hasAbility('create:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (applicationIds.length === 0 && waitingApplicationIds.length === 0) {
        errorAlert(trans('trans.ui_select_all_eligible'));
        return;
    }

    const overLimit =
        alreadyInClassCount.value + applicationIds.length > intakeLimit.value && intakeLimit.value > 0;
    const needsBypass = bypassRanking || overLimit;
    const count = applicationIds.length + waitingApplicationIds.length;
    const waitingNote =
        waitingApplicationIds.length > 0
            ? ` (${waitingApplicationIds.length} waiting)`
            : '';

    openActionDialog({
        kind: 'add',
        applicationIds,
        waitingApplicationIds,
        bypassRanking: needsBypass,
        title: trans('trans.ui_add_to_class_list'),
        description: `Add ${count} application${count === 1 ? '' : 's'} to the class list${waitingNote}.`,
        confirmLabel: trans('trans.ui_add_to_class_list'),
        requireNote: needsBypass,
        bypassWarning: needsBypass
            ? 'This add bypasses ranking and/or exceeds the intake limit. A reason is required for the audit trail.'
            : null,
        confirmVariant: ColorVariant.primary,
    });
};

const queueTransition = (applicationIds: number[], toType: string) => {
    const permission =
        toType === 'final' ? 'manage-final:class-lists' : toType === 'verified' ? 'verify:class-lists' : 'create:class-lists';
    if (!hasAbility(permission)) {
        forbiddenAlert();
        return;
    }

    const mutableIds = allApplications.value
        .filter((app) => applicationIds.includes(app.applicationId) && app.classListType !== 'final')
        .map((app) => app.applicationId);

    if (mutableIds.length === 0) {
        errorAlert('Final class list entries are locked and cannot be edited.');
        return;
    }

    const count = mutableIds.length;

    openActionDialog({
        kind: 'transition',
        applicationIds: mutableIds,
        toType,
        bypassRanking: false,
        title: `Move to ${toType}`,
        description: `Update ${count} class list entr${count === 1 ? 'y' : 'ies'} to ${toType}.`,
        confirmLabel: `Confirm ${toType}`,
        requireNote: true,
        bypassWarning: null,
        confirmVariant: toType === 'failed' ? ColorVariant.danger : ColorVariant.primary,
    });
};

const queuePurge = (applicationIds: number[]) => {
    if (!hasAbility('delete:class-lists')) {
        forbiddenAlert();
        return;
    }

    const mutableIds = allApplications.value
        .filter((app) => applicationIds.includes(app.applicationId) && app.classListType !== 'final')
        .map((app) => app.applicationId);

    if (mutableIds.length === 0) {
        errorAlert('Final class list entries are locked and cannot be removed from this page.');
        return;
    }

    const count = mutableIds.length;

    openActionDialog({
        kind: 'purge',
        applicationIds: mutableIds,
        bypassRanking: false,
        title: trans('trans.ui_remove_from_class_list'),
        description: trans('trans.ui_purge_class_list_confirm'),
        confirmLabel: trans('trans.ui_remove_from_class_list'),
        requireNote: true,
        bypassWarning: null,
        confirmVariant: ColorVariant.danger,
    });
};

async function bulkAddSelected() {
    const { provisionalIds, waitingIds } = splitSelectedForAdd();
    const overLimit = provisionalIds.length > remainingSeats.value;

    queueAdd(provisionalIds, overLimit, waitingIds);
}

function openPurgeDialog() {
    queuePurge(selectedForRemove.value.map((app) => app.applicationId));
}

function confirmPendingAction(note: string) {
    const action = pendingAction.value;
    if (!action) {
        return;
    }

    actionProcessing.value = true;
    const onDone = () => {
        actionProcessing.value = false;
        pendingAction.value = null;
        closeModal(APP_MODULE_KEYS.class_list_action);
        clearSelection();
        reloadEnrolmentsView();
    };
    const onError = (errors: Record<string, string>) => {
        const messageText = Object.keys(errors).length ? Object.values(errors).join('\n') : 'Action failed';
        errorAlert(messageText);
    };

    if (action.kind === 'add') {
        bulkAddForm.application_ids = action.applicationIds;
        bulkAddForm.waiting_application_ids = action.waitingApplicationIds ?? [];
        bulkAddForm.type = 'provisional';
        bulkAddForm.note = note || undefined;
        bulkAddForm.bypass_ranking = action.bypassRanking;
        Object.assign(bulkAddForm, mutationContext());
        bulkAddForm.post(route('enrolments.bulk-add-to-class-list'), {
            preserveScroll: true,
            onSuccess: onDone,
            onError,
            onFinish: () => {
                actionProcessing.value = false;
            },
        });
        return;
    }

    if (action.kind === 'transition') {
        transitionForm.application_ids = action.applicationIds;
        transitionForm.to_type = action.toType ?? 'provisional';
        transitionForm.note = note;
        transitionForm.bypass_ranking = action.bypassRanking;
        Object.assign(transitionForm, mutationContext());
        transitionForm.post(route('enrolments.transition-class-list'), {
            preserveScroll: true,
            onSuccess: onDone,
            onError,
            onFinish: () => {
                actionProcessing.value = false;
            },
        });
        return;
    }

    purgeForm.application_ids = action.applicationIds;
    purgeForm.note = note;
    Object.assign(purgeForm, mutationContext());
    purgeForm.post(route('enrolments.purge-class-list'), {
        preserveScroll: true,
        onSuccess: onDone,
        onError,
        onFinish: () => {
            actionProcessing.value = false;
        },
    });
}

const onAddOne = (application: EnrolmentApplication, bypassRanking: boolean, listType?: string) => {
    if (listType === 'waiting') {
        queueAdd([], bypassRanking, [application.applicationId]);
        return;
    }

    queueAdd([application.applicationId], bypassRanking);
};

const onRowTransition = (application: EnrolmentApplication, toType: string) => {
    queueTransition([application.applicationId], toType);
};

const onRowPurge = (application: EnrolmentApplication) => {
    queuePurge([application.applicationId]);
};
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
                    <BaseButton
                        v-if="canMoveProgramme"
                        type="button"
                        :size="ButtonSize.xs"
                        :variant="ColorVariant.primary_outline"
                        classes="rounded-full"
                        @click="openProgrammeReassign"
                    >
                        {{ $t('students.reassign_programme') }}
                    </BaseButton>
                </div>

                <ScoringFormula v-if="isOLevel" />
            </template>

            <ClassListActionDialog
                :processing="actionProcessing"
                :form="pendingAction?.kind === 'add' ? bulkAddForm : pendingAction?.kind === 'transition' ? transitionForm : purgeForm"
                @closed="pendingAction = null"
                @confirm="confirmPendingAction"
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
                        :key="group"
                        :level="rankingLevel"
                        :department-id="String(department?.id)"
                        :applications="enrolments.groups?.[group] ?? []"
                        :class-size="intakeLimit"
                        :slot-size="getGroupSlot(group)"
                        :enrolment-group="group"
                        :is-o-level="isOLevel"
                        :class-list-created="classListIsCreatedForGroup(enrolments, group)"
                        :ui-context-key="enrolmentContextKey"
                        :listed-count="alreadyInClassCount"
                        :selected-ids="selectedIds"
                        :is-selected="isSelected"
                        :selected-count="selectedCount"
                        :bulk-transition-actions="bulkTransitionActions"
                        :can-bulk-add="selectedForAdd.length > 0"
                        :can-bulk-purge="selectedForRemove.length > 0 && hasAbility('delete:class-lists')"
                        :action-processing="actionProcessing || bulkAddForm.processing"
                        @toggle="toggleSelection"
                        @select-group="(apps, checked) => setGroupSelection(apps, checked, 'any')"
                        @add-one="onAddOne"
                        @transition-one="onRowTransition"
                        @purge-one="onRowPurge"
                        @clear-selection="clearSelection"
                        @bulk-add="bulkAddSelected"
                        @bulk-transition="(toType) => queueTransition(selectedForRemove.map((a) => a.applicationId), toType)"
                        @bulk-purge="openPurgeDialog"
                    />
                </GenderEnrolmentAccordionItem>
            </BaseAccordion>
        </div>
        <ReassignProgrammeDialog
            v-if="canMoveProgramme"
            :form="reassignForm"
            :records="reassignRecords"
            :loading-records="reassignLoadingRecords"
            :hydrating-defaults="reassignHydratingDefaults"
            v-model:selected-application-ids="reassignSelectedIds"
            :on-form-action="submitReassignProgramme"
        />
    </PageContainer>
</template>
