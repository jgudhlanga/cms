<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import DepartmentModeTotalsStrip from '@/components/institution/DepartmentModeTotalsStrip.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import EnrolmentModeAccordionItem from '@/pages/institution/departments/partials/view/EnrolmentModeAccordionItem.vue';
import AcademicCalendarClassCourseTable from '@/pages/institution/academicCalendars/partials/AcademicCalendarClassCourseTable.vue';
import HttpService from '@/services/http.service';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { DepartmentClassModeTotal, DepartmentCourseClassCount } from '@/types/academic-calendar';
import { ModeOfStudy } from '@/types/institution';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    departmentId: string;
    academicYear: string | null;
    semesterId?: string | null;
    initialModeOfStudyId?: string | null;
    totalsTarget?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    semesterId: null,
    initialModeOfStudyId: null,
    totalsTarget: null,
});

const emit = defineEmits<{
    'update:modeOfStudyId': [modeId: string];
}>();

const MODE_ORDER = ['full time', 'part time', 'ojet', 'block release', 'block'];

const MODE_ICONS: Array<{ match: string; icon: IconName }> = [
    { match: 'full time', icon: IconName.graduation_cape },
    { match: 'part time', icon: IconName.briefcase },
    { match: 'ojet', icon: IconName.award },
    { match: 'block', icon: IconName.calendar },
];

const LEGEND_COLORS = [
    'bg-primary',
    'bg-violet-400',
    'bg-indigo-400',
    'bg-sky-400',
    'bg-fuchsia-400',
    'bg-muted-foreground/40',
];

const openModeId = ref<string>('');
const modeTotals = ref<Record<number, number>>({});
const coursesByMode = ref<Record<string, DepartmentCourseClassCount[]>>({});
const loadedModes = ref<Record<string, boolean>>({});
const loadingModes = ref<Record<string, boolean>>({});
const failedModes = ref<Record<string, boolean>>({});
const retriedEmptyMismatch = ref<Record<string, boolean>>({});
const panelRequestSeq = ref<Record<string, number>>({});
const resolvedCalendarIdByMode = ref<Record<string, number | null>>({});
const loadingMeta = ref(false);
const isReady = ref(false);

const { isLoading: modesOfStudyLoading, listDepartmentModesOfStudy, modesOfStudy } = useModeOfStudy();
const departmentMetaStore = useDepartmentMetaStore();
const { academicClassConfigsRefreshNonce } = storeToRefs(departmentMetaStore);

const normalizeModeId = (modeId: string | number | null | undefined): string => {
    const value = String(modeId ?? '').trim();

    return value === '' || value === 'undefined' || value === 'null' ? '' : value;
};

const calendarPath = (): string =>
    route('v1.departments.academic-calendars', { institution_department: props.departmentId });

const calendarQuery = (modeId?: string): Record<string, string> => {
    const params: Record<string, string> = {};

    if (props.academicYear) {
        params.academic_year = String(props.academicYear);
    }

    if (modeId) {
        params.mode_of_study_id = modeId;
    }

    if (props.semesterId) {
        params.semester_id = String(props.semesterId);
    }

    return params;
};

const cacheKeyFor = (modeId: string): string =>
    `${props.departmentId}:${props.academicYear ?? ''}:${props.semesterId ?? ''}:${modeId}`;

const orderedModes = computed(() => {
    const modes = [...(modesOfStudy.value ?? [])];

    return modes.sort((a, b) => {
        const aIndex = MODE_ORDER.findIndex((name) => a.attributes.name.toLowerCase().includes(name));
        const bIndex = MODE_ORDER.findIndex((name) => b.attributes.name.toLowerCase().includes(name));
        const safeA = aIndex === -1 ? MODE_ORDER.length : aIndex;
        const safeB = bIndex === -1 ? MODE_ORDER.length : bIndex;

        if (safeA !== safeB) {
            return safeA - safeB;
        }

        return a.attributes.name.localeCompare(b.attributes.name);
    });
});

const modeCount = (mode: ModeOfStudy): number => modeTotals.value[Number(mode.id)] ?? 0;

const programmeCount = (modeId: string): number =>
    (coursesByMode.value[modeId] ?? []).filter((course) =>
        (course.levels ?? []).some((level) => Number(level.totalFinalList ?? 0) > 0),
    ).length;

const isModeLoaded = (modeId: string): boolean => {
    if (!props.academicYear) {
        return false;
    }

    return Boolean(loadedModes.value[cacheKeyFor(modeId)]);
};

const isModeLoading = (modeId: string): boolean => Boolean(loadingModes.value[cacheKeyFor(modeId)]);

const isModeFailed = (modeId: string): boolean => Boolean(failedModes.value[cacheKeyFor(modeId)]);

const coursesForMode = (modeId: string): DepartmentCourseClassCount[] => coursesByMode.value[modeId] ?? [];

const hasEmptyCountMismatch = (modeId: string): boolean =>
    coursesForMode(modeId).length === 0 && (modeTotals.value[Number(modeId)] ?? 0) > 0;

const shouldShowPanelSpinner = (modeId: string): boolean =>
    normalizeModeId(openModeId.value) === modeId &&
    (isModeLoading(modeId) || (!isModeLoaded(modeId) && !isModeFailed(modeId)));

const shouldShowEmptyState = (modeId: string): boolean =>
    !isModeLoading(modeId) && (isModeLoaded(modeId) || isModeFailed(modeId)) && coursesForMode(modeId).length === 0;

const shouldSkipCachedPanel = (modeId: string, cacheKey: string, force: boolean): boolean => {
    if (force || !loadedModes.value[cacheKey]) {
        return false;
    }

    return !hasEmptyCountMismatch(modeId) || Boolean(retriedEmptyMismatch.value[cacheKey]);
};

const totalConfirmed = computed(() => orderedModes.value.reduce((sum, mode) => sum + modeCount(mode), 0));

const legendItems = computed(() =>
    orderedModes.value.map((mode, index) => ({
        id: String(mode.id),
        label: mode.attributes.name,
        count: modeCount(mode),
        colorClass: modeCount(mode) > 0 ? LEGEND_COLORS[index % LEGEND_COLORS.length] : 'bg-muted-foreground/30',
        share: totalConfirmed.value > 0 ? (modeCount(mode) / totalConfirmed.value) * 100 : 0,
    })),
);

const modeIcon = (mode: ModeOfStudy): IconName => {
    const name = mode.attributes.name.toLowerCase();
    const match = MODE_ICONS.find((row) => name.includes(row.match));

    return match?.icon ?? IconName.users;
};

const applyModeTotals = (totals: DepartmentClassModeTotal[]): void => {
    const next: Record<number, number> = {};
    totals.forEach((row) => {
        next[row.modeOfStudyId] = row.count;
    });
    modeTotals.value = next;
};

const fetchModeTotals = async () => {
    if (!props.academicYear) {
        modeTotals.value = {};
        return;
    }

    loadingMeta.value = true;
    try {
        const document = await HttpService.get(calendarPath(), { params: calendarQuery() });
        applyModeTotals((document?.meta?.modeTotals ?? []) as DepartmentClassModeTotal[]);
    } catch {
        errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.class', 2) }));
    } finally {
        loadingMeta.value = false;
    }
};

const loadModePanel = async (modeId: string, force = false) => {
    const id = normalizeModeId(modeId);
    if (!props.academicYear || !id) {
        return;
    }

    const cacheKey = cacheKeyFor(id);
    if (shouldSkipCachedPanel(id, cacheKey, force)) {
        return;
    }

    const requestSeq = (panelRequestSeq.value[cacheKey] ?? 0) + 1;
    panelRequestSeq.value = { ...panelRequestSeq.value, [cacheKey]: requestSeq };
    loadingModes.value = { ...loadingModes.value, [cacheKey]: true };
    failedModes.value = { ...failedModes.value, [cacheKey]: false };
    try {
        const document = await HttpService.get(calendarPath(), { params: calendarQuery(id) });
        if (panelRequestSeq.value[cacheKey] !== requestSeq) {
            return;
        }

        const rows = (Array.isArray(document) ? document : (document?.data ?? [])) as DepartmentCourseClassCount[];
        if (Array.isArray(document?.meta?.modeTotals)) {
            applyModeTotals(document.meta.modeTotals as DepartmentClassModeTotal[]);
        }
        coursesByMode.value = {
            ...coursesByMode.value,
            [id]: rows,
        };
        resolvedCalendarIdByMode.value = {
            ...resolvedCalendarIdByMode.value,
            [id]: document?.meta?.resolvedAcademicCalendarId ?? null,
        };
        loadedModes.value = {
            ...loadedModes.value,
            [cacheKey]: true,
        };
        const emptyMismatch = rows.length === 0 && (modeTotals.value[Number(id)] ?? 0) > 0;
        if (emptyMismatch && !force && !retriedEmptyMismatch.value[cacheKey]) {
            retriedEmptyMismatch.value = { ...retriedEmptyMismatch.value, [cacheKey]: true };
            await loadModePanel(id, true);

            return;
        }

        retriedEmptyMismatch.value = {
            ...retriedEmptyMismatch.value,
            [cacheKey]: emptyMismatch,
        };
    } catch {
        if (panelRequestSeq.value[cacheKey] !== requestSeq) {
            return;
        }

        failedModes.value = { ...failedModes.value, [cacheKey]: true };
        errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.class', 2) }));
    } finally {
        if (panelRequestSeq.value[cacheKey] === requestSeq) {
            loadingModes.value = { ...loadingModes.value, [cacheKey]: false };
        }
    }
};

const refreshModePanel = async (modeId: string): Promise<void> => {
    await loadModePanel(modeId, true);
};

const loadOpenModePanel = async (force = false): Promise<void> => {
    const id = normalizeModeId(openModeId.value);
    if (!id) {
        return;
    }

    await loadModePanel(id, force);
    if (normalizeModeId(openModeId.value) === id) {
        emit('update:modeOfStudyId', id);
    }
};

const resetAndReload = async () => {
    loadedModes.value = {};
    loadingModes.value = {};
    failedModes.value = {};
    retriedEmptyMismatch.value = {};
    panelRequestSeq.value = {};
    coursesByMode.value = {};
    resolvedCalendarIdByMode.value = {};
    await fetchModeTotals();
    await loadOpenModePanel(true);
};

onMounted(async () => {
    await listDepartmentModesOfStudy(props.departmentId);

    const preferredMode =
        orderedModes.value.find((row) => String(row.id) === String(props.initialModeOfStudyId)) ??
        orderedModes.value[0] ??
        null;
    openModeId.value = preferredMode ? String(preferredMode.id) : '';

    await fetchModeTotals();
    isReady.value = true;
    await loadOpenModePanel();
});

watch(
    () => [props.academicYear, props.departmentId, props.semesterId],
    async ([nextYear, nextDepartmentId, nextSemesterId], [previousYear, previousDepartmentId, previousSemesterId]) => {
        if (
            !isReady.value ||
            (nextYear === previousYear && nextDepartmentId === previousDepartmentId && nextSemesterId === previousSemesterId)
        ) {
            return;
        }
        await resetAndReload();
    },
);

watch(openModeId, async (modeId, previous) => {
    const id = normalizeModeId(modeId);
    if (!isReady.value || !id || id === normalizeModeId(previous)) {
        return;
    }
    await loadOpenModePanel();
});

watch(academicClassConfigsRefreshNonce, (next, prev) => {
    if (!isReady.value || prev === undefined || next <= prev || !openModeId.value) {
        return;
    }

    void loadOpenModePanel(true);
});

const totalsTeleportTo = computed(() => (props.totalsTarget ? `#${props.totalsTarget}` : 'body'));
</script>

<template>
    <Teleport :to="totalsTeleportTo" :disabled="!totalsTarget">
        <DepartmentModeTotalsStrip
            v-if="orderedModes.length > 0"
            :total="totalConfirmed"
            :total-label="$tChoice('academic_calendar.confirmed_student', totalConfirmed)"
            :items="legendItems"
        />
    </Teleport>

    <DataLoadingSpinner v-if="modesOfStudyLoading || loadingMeta" />

    <template v-else>
        <BaseAlert
            v-if="!academicYear"
            :title="$t('trans.no_data')"
            :description="$t('trans.no_data_found_description', { data: $tChoice('academic_calendar.calendar_year', 1) })"
        />

        <BaseAlert
            v-else-if="orderedModes.length === 0"
            :title="$t('trans.no_data')"
            :description="$t('trans.no_data_found_description', { data: $tChoice('trans.mode_of_study', 2) })"
        />

        <div v-else class="flex flex-col gap-3">
            <BaseAccordion v-model="openModeId" type="single" :collapsible="true" class="w-full gap-3">
                <EnrolmentModeAccordionItem
                    v-for="mode in orderedModes"
                    :key="String(mode.id)"
                    :value="String(mode.id)"
                    :title="mode.attributes.name"
                    :count="modeCount(mode)"
                    :programme-count="programmeCount(String(mode.id))"
                    :loaded="isModeLoaded(String(mode.id))"
                    :icon="modeIcon(mode)"
                    :is-open="openModeId === String(mode.id)"
                    :empty-subtitle="$t('academic_calendar.no_confirmed_students_recorded')"
                    :count-singular="$tChoice('academic_calendar.confirmed_student', 1).toLowerCase()"
                    :count-plural="$tChoice('academic_calendar.confirmed_student', 2).toLowerCase()"
                >
                    <div
                        class="flex flex-col gap-4 border-t border-border/60 pt-4"
                        :aria-busy="shouldShowPanelSpinner(String(mode.id))"
                    >
                        <DataLoadingSpinner v-if="shouldShowPanelSpinner(String(mode.id))" />

                        <AcademicCalendarClassCourseTable
                            v-else-if="coursesForMode(String(mode.id)).length > 0 && academicYear"
                            :class-states="coursesForMode(String(mode.id))"
                            :department-id="departmentId"
                            :academic-year="academicYear"
                            :mode-of-study-id="String(mode.id)"
                            :resolved-academic-calendar-id="resolvedCalendarIdByMode[String(mode.id)] ?? null"
                        />

                        <div
                            v-else-if="shouldShowEmptyState(String(mode.id))"
                            class="rounded-xl border border-dashed border-border bg-muted/30 px-4 py-6 text-center"
                        >
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ $t('trans.no_data') }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t('trans.no_data_found_description', {
                                        data: `${$tChoice('trans.class', 2)} — ${mode.attributes.name}`,
                                    })
                                }}
                            </p>
                            <div class="mt-3 flex justify-center">
                                <BaseButton
                                    type="button"
                                    :variant="ColorVariant.primary_outline"
                                    :size="ButtonSize.sm"
                                    :processing="isModeLoading(String(mode.id))"
                                    @click.stop="refreshModePanel(String(mode.id))"
                                >
                                    <BaseIcon
                                        v-if="!isModeLoading(String(mode.id))"
                                        :name="IconName.refresh"
                                        class="h-4 w-4"
                                    />
                                    <span>{{ $t('trans.refresh') }}</span>
                                </BaseButton>
                            </div>
                        </div>
                    </div>
                </EnrolmentModeAccordionItem>
            </BaseAccordion>
        </div>
    </template>
</template>
