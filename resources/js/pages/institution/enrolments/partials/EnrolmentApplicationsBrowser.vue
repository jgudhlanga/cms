<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { hasAbility } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication } from '@/types/enrolments';
import { ChevronDown, Lock, MoreHorizontal, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
    isOLevel: boolean;
    classListCreated: boolean;
    listedCount?: number;
    selectedIds?: Set<number>;
    isSelected?: (applicationId: number) => boolean;
    selectedCount?: number;
    bulkTransitionActions?: Array<{ to: string; label: string }>;
    canBulkAdd?: boolean;
    canBulkPurge?: boolean;
    actionProcessing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => new Set(),
    isSelected: () => false,
    listedCount: 0,
    selectedCount: 0,
    bulkTransitionActions: () => [],
    canBulkAdd: false,
    canBulkPurge: false,
    actionProcessing: false,
});

const emit = defineEmits<{
    toggle: [applicationId: number, checked: boolean];
    selectGroup: [applications: EnrolmentApplication[], checked: boolean];
    addOne: [application: EnrolmentApplication, bypassRanking: boolean];
    transitionOne: [application: EnrolmentApplication, toType: string];
    purgeOne: [application: EnrolmentApplication];
    clearSelection: [];
    bulkAdd: [];
    bulkTransition: [toType: string];
    bulkPurge: [];
}>();

const STATUS_ORDER = ['final', 'verified', 'waiting', 'provisional', 'failed', 'others'] as const;

const STATUS_PILL: Record<string, string> = {
    final: 'bg-emerald-100 text-emerald-900 border-emerald-200',
    verified: 'bg-primary/15 text-primary border-primary/25',
    waiting: 'bg-violet-100 text-violet-900 border-violet-200',
    provisional: 'bg-orange-100 text-orange-900 border-orange-200',
    failed: 'bg-red-100 text-red-900 border-red-200',
    others: 'bg-muted text-muted-foreground border-border',
};

const STATUS_ACCENT: Record<string, string> = {
    final: 'border-l-emerald-500',
    verified: 'border-l-primary',
    waiting: 'border-l-violet-400',
    provisional: 'border-l-orange-400',
    failed: 'border-l-red-400',
    others: 'border-l-muted-foreground/40',
};

const {
    applyPolicyAlgorithmToApplications,
    groupByClassListType,
    getMainSubjectGrade,
    getOtherSubjectGrades,
    getClassListTypeDescription,
    getRowClassList,
} = useEnrolments();
const { formatDate, isItTrue } = useUtils();

const search = ref('');
const activeStatus = ref<string>('all');
const openStatuses = ref<Record<string, boolean>>({});

const levelRequirements = computed(() => props.level?.relationships?.requirement);
const requirementSubjects = computed(() => levelRequirements.value?.relationships?.subjects ?? []);
const requiresPriorLevel = computed(() => Number(levelRequirements.value?.attributes?.requiredLevelId) > 0);
const requiresReadWrite = computed(() => isItTrue(levelRequirements.value?.attributes?.onlyReadWriteRequired));

const rankedApplications = computed(() => {
    if (props.isOLevel) {
        return applyPolicyAlgorithmToApplications(props.applications, props.level);
    }

    return [...props.applications].sort(
        (a, b) => new Date(a.applicationDate).getTime() - new Date(b.applicationDate).getTime(),
    );
});

const searchFiltered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return rankedApplications.value;
    }

    return rankedApplications.value.filter((app) => {
        const haystack = [app.studentName, app.phoneNumber, app.applicationTrackingNumber, app.email]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });
});

const statusCounts = computed(() => {
    const counts: Record<string, number> = { all: searchFiltered.value.length };
    STATUS_ORDER.forEach((key) => {
        counts[key] = 0;
    });

    searchFiltered.value.forEach((app) => {
        const key =
            app.classListType && STATUS_ORDER.includes(app.classListType as (typeof STATUS_ORDER)[number])
                ? app.classListType
                : 'others';
        counts[key] = (counts[key] ?? 0) + 1;
    });

    return counts;
});

const visibleStatusPills = computed(() =>
    STATUS_ORDER.filter((key) => (statusCounts.value[key] ?? 0) > 0 || activeStatus.value === key),
);

const groupedForDisplay = computed(() => {
    const source =
        activeStatus.value === 'all'
            ? searchFiltered.value
            : searchFiltered.value.filter((app) => {
                  const key =
                      app.classListType && STATUS_ORDER.includes(app.classListType as (typeof STATUS_ORDER)[number])
                          ? app.classListType
                          : 'others';

                  return key === activeStatus.value;
              });

    if (props.classListCreated) {
        const grouped = groupByClassListType(source);
        if (!props.isOLevel) {
            return grouped;
        }

        const ranked: Record<string, EnrolmentApplication[]> = {};
        Object.entries(grouped).forEach(([key, apps]) => {
            ranked[key] = applyPolicyAlgorithmToApplications(apps, props.level);
        });

        return ranked;
    }

    return { applications: source } as Record<string, EnrolmentApplication[]>;
});

const selectableInView = computed(() =>
    Object.values(groupedForDisplay.value)
        .flat()
        .filter((app) => app.classListType !== 'final'),
);

const selectedInThisGroup = computed(
    () => props.applications.filter((app) => props.isSelected(Number(app.applicationId))).length,
);

const allSelectableSelected = computed(
    () => selectableInView.value.length > 0 && selectableInView.value.every((app) => props.isSelected(app.applicationId)),
);

const selectionCriteriaLines = computed(() => {
    if (props.isOLevel) {
        return [];
    }

    const lines: string[] = [];
    if (requiresPriorLevel.value) {
        lines.push(
            `Prior qualification: ${levelRequirements.value?.attributes?.requiredLevel ?? 'required level'} must be completed.`,
        );
    }
    if (requiresReadWrite.value) {
        lines.push('Literacy: applicant must acknowledge basic read and write ability.');
    }
    lines.push('Order: earliest complete application first (application date).');
    lines.push('Intake limit is guidance only when adding to the class list.');

    return lines;
});

const ensureOpenDefaults = () => {
    const keys = Object.keys(groupedForDisplay.value);
    if (keys.length === 0) {
        return;
    }
    const hasOpen = keys.some((key) => openStatuses.value[key]);
    if (!hasOpen) {
        openStatuses.value = { ...openStatuses.value, [keys[0]]: true };
    }
};

watch(groupedForDisplay, ensureOpenDefaults, { immediate: true });

const isSectionOpen = (key: string) => openStatuses.value[key] ?? false;

const toggleSection = (key: string) => {
    openStatuses.value = { ...openStatuses.value, [key]: !isSectionOpen(key) };
};

const onSelectAll = (checked: boolean | 'indeterminate') => {
    emit('selectGroup', selectableInView.value, checked === true);
};

const shortSubject = (name: string): string => {
    const map: Record<string, string> = {
        english: 'Eng',
        mathematics: 'Maths',
        'any science subject': 'Sci',
        science: 'Sci',
    };
    const key = name.trim().toLowerCase();
    if (map[key]) {
        return map[key];
    }

    return name.length > 8 ? name.slice(0, 6) : name;
};

const gradeBadgeClass = (grade: string | null | undefined): string => {
    const g = String(grade ?? '').toUpperCase();
    if (g === 'A') return 'bg-emerald-100 text-emerald-800';
    if (g === 'B') return 'bg-sky-100 text-sky-800';
    if (g === 'C') return 'bg-orange-100 text-orange-800';

    return 'bg-muted text-muted-foreground';
};

const yearSuffix = (year: string | number | null | undefined): string => {
    const value = String(year ?? '');
    return value.length >= 2 ? value.slice(-2) : value;
};

const sittingBadgeClass = (count: number): string =>
    count > 1 ? 'bg-amber-100 text-amber-900' : 'bg-muted text-muted-foreground';

const sectionTitle = (key: string): string => (key === 'applications' ? 'Applications' : key);

const canCreate = computed(() => hasAbility('create:class-lists'));
const canVerify = computed(() => hasAbility('verify:class-lists'));
const canManageFinal = computed(() => hasAbility('manage-final:class-lists'));
const canDelete = computed(() => hasAbility('delete:class-lists'));

const transitionOptions = (app: EnrolmentApplication): Array<{ to: string; label: string; show: boolean }> => {
    const type = app.classListType ?? '';
    if (type === 'final') {
        return [];
    }

    return [
        {
            to: 'provisional',
            label: 'To provisional',
            show: ((type === 'waiting' || type === 'failed') && canCreate.value) || (type === 'verified' && canVerify.value),
        },
        { to: 'waiting', label: 'To waiting', show: type === 'provisional' && canCreate.value },
        {
            to: 'verified',
            label: 'To verified',
            show: (type === 'provisional' || type === 'waiting') && canVerify.value,
        },
        { to: 'final', label: 'To final', show: type === 'verified' && canManageFinal.value },
        {
            to: 'failed',
            label: 'Fail',
            show: ['provisional', 'waiting', 'verified'].includes(type) && canCreate.value,
        },
    ].filter((row) => row.show);
};

const isFinalLocked = (app: EnrolmentApplication): boolean => app.classListType === 'final';

const addBypassNeeded = (app: EnrolmentApplication): boolean => {
    if (props.classListCreated) {
        return true;
    }
    if (props.classSize > 0 && props.listedCount + 1 > props.classSize) {
        return true;
    }
    return !app.classListType || app.classListType === 'others';
};
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Search -->
        <label class="relative block">
            <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
                v-model="search"
                type="search"
                class="h-10 w-full rounded-full border border-border bg-card pr-3 pl-9 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="Search name or phone..."
                aria-label="Search applications"
            />
        </label>

        <!-- Status filters -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button
                type="button"
                class="rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase"
                :class="
                    activeStatus === 'all'
                        ? 'border-foreground/20 bg-foreground text-background'
                        : 'border-border bg-card text-muted-foreground hover:bg-muted'
                "
                @click="activeStatus = 'all'"
            >
                All {{ statusCounts.all }}
            </button>
            <button
                v-for="status in visibleStatusPills"
                :key="status"
                type="button"
                class="rounded-full border px-2.5 py-1 text-[11px] font-semibold capitalize"
                :class="
                    cn(
                        STATUS_PILL[status],
                        activeStatus === status ? 'ring-1 ring-foreground/20 opacity-100' : 'opacity-90',
                    )
                "
                @click="activeStatus = status"
            >
                {{ status }} {{ statusCounts[status] ?? 0 }}
            </button>
            <div class="ml-auto flex items-center gap-2">
                <Checkbox
                    :checked="allSelectableSelected"
                    :disabled="selectableInView.length === 0"
                    :aria-label="$t('trans.ui_select_all_eligible')"
                    @update:checked="onSelectAll"
                />
                <span class="text-[11px] text-muted-foreground">Select visible</span>
            </div>
        </div>

        <div
            v-if="selectedInThisGroup > 0"
            class="flex flex-col gap-2 rounded-lg border border-primary/30 bg-primary/5 p-2.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-xs">
                <span class="font-semibold">{{ selectedCount }}</span>
                selected
            </p>
            <div class="flex flex-wrap items-center gap-1.5">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    :size="ButtonSize.xs"
                    title="Clear"
                    classes="rounded-full"
                    @click="emit('clearSelection')"
                />
                <BaseButton
                    v-if="canBulkAdd"
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.xs"
                    :title="$t('trans.ui_add_to_class_list')"
                    classes="rounded-full"
                    :disabled="actionProcessing"
                    @click="emit('bulkAdd')"
                />
                <BaseButton
                    v-for="action in bulkTransitionActions"
                    :key="action.to"
                    type="button"
                    :variant="action.to === 'failed' ? ColorVariant.danger : ColorVariant.primary"
                    :size="ButtonSize.xs"
                    :title="action.label"
                    classes="rounded-full"
                    :disabled="actionProcessing"
                    @click="emit('bulkTransition', action.to)"
                />
                <BaseButton
                    v-if="canBulkPurge"
                    type="button"
                    :variant="ColorVariant.danger"
                    :size="ButtonSize.xs"
                    :title="$t('trans.ui_remove_from_class_list')"
                    classes="rounded-full"
                    :disabled="actionProcessing"
                    @click="emit('bulkPurge')"
                />
            </div>
        </div>

        <!-- Criteria -->
        <div
            v-if="isOLevel"
            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/70 bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground"
        >
            <p>
                Grade key:
                <span class="ml-1 inline-flex items-center gap-1">
                    <span class="rounded px-1.5 py-0.5 font-bold" :class="gradeBadgeClass('A')">A</span> distinction
                    <span class="rounded px-1.5 py-0.5 font-bold" :class="gradeBadgeClass('B')">B</span> merit
                    <span class="rounded px-1.5 py-0.5 font-bold" :class="gradeBadgeClass('C')">C</span> pass
                </span>
            </p>
            <p class="font-medium text-foreground/80">Lower points total = stronger application</p>
        </div>
        <div
            v-else
            class="rounded-lg border border-border/70 bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground"
        >
            <p class="mb-1 font-semibold text-foreground/80">Selection criteria</p>
            <ul class="list-inside list-disc space-y-0.5">
                <li v-for="(line, index) in selectionCriteriaLines" :key="index">{{ line }}</li>
            </ul>
        </div>

        <!-- Empty -->
        <div
            v-if="Object.keys(groupedForDisplay).length === 0 || searchFiltered.length === 0"
            class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No applications match your filters.
        </div>

        <!-- Status sections -->
        <div v-for="(apps, statusKey) in groupedForDisplay" :key="String(statusKey)" class="flex flex-col gap-2">
            <section
                class="overflow-hidden rounded-xl border border-border/70 border-l-4 bg-card shadow-sm"
                :class="STATUS_ACCENT[String(statusKey)] ?? STATUS_ACCENT.others"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-2 px-3 py-2.5 text-left hover:bg-muted/40"
                    @click="toggleSection(String(statusKey))"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold capitalize">{{ sectionTitle(String(statusKey)) }}</span>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-bold tabular-nums">{{ apps.length }}</span>
                        </div>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{
                                statusKey === 'applications'
                                    ? 'Ranked for class-list selection'
                                    : getClassListTypeDescription(String(statusKey))
                            }}
                        </p>
                    </div>
                    <ChevronDown
                        class="h-4 w-4 shrink-0 text-muted-foreground transition-transform"
                        :class="isSectionOpen(String(statusKey)) ? 'rotate-180' : ''"
                    />
                </button>

                <div v-show="isSectionOpen(String(statusKey))" class="border-t border-border/60">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-xs">
                            <thead>
                                <tr class="border-b border-border/60 bg-muted/40 text-[10px] tracking-wide text-muted-foreground uppercase">
                                    <th class="w-8 px-2 py-2 text-center"></th>
                                    <th class="w-8 px-1 py-2">#</th>
                                    <th class="px-2 py-2">Applicant</th>
                                    <th class="px-2 py-2">Applied</th>
                                    <template v-if="isOLevel">
                                        <th class="px-1 py-2 text-center">Sit.</th>
                                        <th class="px-1 py-2 text-center">First</th>
                                        <th
                                            v-for="subject in requirementSubjects"
                                            :key="`h_${subject.id}`"
                                            class="px-1 py-2 text-center"
                                        >
                                            {{ shortSubject(subject.attributes?.name ?? '') }}
                                        </th>
                                        <th
                                            v-for="n in levelRequirements?.attributes?.otherSubjectsCount"
                                            :key="`ho_${n}`"
                                            class="px-1 py-2 text-center"
                                        >
                                            Opt {{ n }}
                                        </th>
                                        <th class="px-2 py-2 text-right">Score</th>
                                    </template>
                                    <template v-else>
                                        <th v-if="requiresPriorLevel" class="px-2 py-2 text-center">
                                            {{ levelRequirements?.attributes?.requiredLevel }} done
                                        </th>
                                        <th v-if="requiresReadWrite" class="px-2 py-2 text-center">Read/Write</th>
                                    </template>
                                    <th class="w-10 px-2 py-2 text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(application, index) in apps"
                                    :key="application.applicationId"
                                    class="border-b border-border/40 last:border-0 hover:bg-muted/30"
                                    :class="!classListCreated ? getRowClassList(index, classSize) : ''"
                                >
                                    <td class="px-2 py-1.5 text-center">
                                        <Checkbox
                                            v-if="!isFinalLocked(application)"
                                            :checked="isSelected(application.applicationId)"
                                            :aria-label="application.studentName"
                                            @update:checked="(checked) => emit('toggle', application.applicationId, checked === true)"
                                        />
                                        <span
                                            v-else
                                            class="inline-flex items-center justify-center text-emerald-700"
                                            title="Final class list entries are locked"
                                        >
                                            <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span class="sr-only">Locked</span>
                                        </span>
                                    </td>
                                    <td class="px-1 py-1.5 tabular-nums text-muted-foreground">{{ index + 1 }}</td>
                                    <td class="px-2 py-1.5">
                                        <div class="font-semibold text-foreground">{{ application.studentName }}</div>
                                        <div class="text-[10px] text-muted-foreground">{{ application.phoneNumber || '—' }}</div>
                                    </td>
                                    <td class="px-2 py-1.5 whitespace-nowrap text-muted-foreground">
                                        {{ formatDate(application.applicationDate, 'DD MMM YY') }}
                                    </td>
                                    <template v-if="isOLevel">
                                        <td class="px-1 py-1.5 text-center">
                                            <span
                                                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold"
                                                :class="sittingBadgeClass(Number(application.examSittingsCount) || 1)"
                                            >
                                                {{ application.examSittingsCount || 1 }}
                                            </span>
                                        </td>
                                        <td class="px-1 py-1.5 text-center tabular-nums">{{ application.firstExamYear || '—' }}</td>
                                        <td
                                            v-for="subject in requirementSubjects"
                                            :key="`g_${application.applicationId}_${subject.id}`"
                                            class="px-1 py-1.5 text-center"
                                        >
                                            <span
                                                v-if="getMainSubjectGrade(application.academicResults ?? [], String(subject.id))"
                                                class="inline-flex items-baseline rounded px-1.5 py-0.5 text-[10px] font-bold"
                                                :class="
                                                    gradeBadgeClass(
                                                        getMainSubjectGrade(application.academicResults ?? [], String(subject.id))?.grade,
                                                    )
                                                "
                                            >
                                                {{ getMainSubjectGrade(application.academicResults ?? [], String(subject.id))?.grade }}
                                                <sup class="ml-0.5 text-[8px] font-semibold opacity-80">
                                                    {{
                                                        yearSuffix(
                                                            getMainSubjectGrade(application.academicResults ?? [], String(subject.id))
                                                                ?.examYear,
                                                        )
                                                    }}
                                                </sup>
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td
                                            v-for="(result, oi) in getOtherSubjectGrades(application.academicResults ?? [], level)"
                                            :key="`o_${application.applicationId}_${oi}`"
                                            class="px-1 py-1.5 text-center"
                                        >
                                            <span
                                                class="inline-flex items-baseline rounded px-1.5 py-0.5 text-[10px] font-bold"
                                                :class="gradeBadgeClass(result.grade)"
                                            >
                                                {{ result.grade }}
                                                <sup class="ml-0.5 text-[8px] font-semibold opacity-80">{{ yearSuffix(result.examYear) }}</sup>
                                            </span>
                                        </td>
                                        <td class="px-2 py-1.5 text-right">
                                            <span class="inline-flex rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-bold tabular-nums">
                                                {{ application.totalScore }} pts
                                            </span>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td v-if="requiresPriorLevel" class="px-2 py-1.5 text-center">
                                            <span
                                                class="text-[10px] font-semibold"
                                                :class="isItTrue(application.requiredLevelCompleted) ? 'text-emerald-600' : 'text-red-600'"
                                            >
                                                {{ isItTrue(application.requiredLevelCompleted) ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td v-if="requiresReadWrite" class="px-2 py-1.5 text-center">
                                            <span
                                                class="text-[10px] font-semibold"
                                                :class="isItTrue(application.readWriteAcknowledged) ? 'text-emerald-600' : 'text-red-600'"
                                            >
                                                {{ isItTrue(application.readWriteAcknowledged) ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </template>
                                    <td class="px-2 py-1.5 text-center">
                                        <button
                                            v-if="!application.inClassList && canCreate"
                                            type="button"
                                            class="text-[10px] font-semibold text-primary hover:underline"
                                            @click="emit('addOne', application, addBypassNeeded(application))"
                                        >
                                            Add
                                        </button>
                                        <span
                                            v-else-if="isFinalLocked(application)"
                                            class="inline-flex items-center justify-center text-emerald-700"
                                            title="Final class list entries are locked"
                                        >
                                            <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span class="sr-only">Final</span>
                                        </span>
                                        <DropdownMenu v-else-if="application.inClassList">
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                                                    aria-label="Class list actions"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="min-w-40">
                                                <DropdownMenuGroup>
                                                    <DropdownMenuItem
                                                        v-for="option in transitionOptions(application)"
                                                        :key="option.to"
                                                        class="cursor-pointer text-xs"
                                                        @click="emit('transitionOne', application, option.to)"
                                                    >
                                                        {{ option.label }}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canDelete"
                                                        class="cursor-pointer text-xs text-destructive"
                                                        @click="emit('purgeOne', application)"
                                                    >
                                                        Remove permanently
                                                    </DropdownMenuItem>
                                                </DropdownMenuGroup>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
