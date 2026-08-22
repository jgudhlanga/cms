<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import { jsonApiRequestConfig, parseDepartmentEnrolmentSummaries } from '@/lib/json-api';
import EnrolmentModeAccordionItem from '@/pages/institution/departments/partials/view/EnrolmentModeAccordionItem.vue';
import HttpService from '@/services/http.service';
import { ModeOfStudy } from '@/types/institution';
import { Link } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

export type CourseEnrolmentSummary = {
    institutionDepartmentId: number;
    departmentCourseId: number;
    courseName: string;
    levels: Array<{
        departmentLevelId: number;
        levelName: string;
        enrolmentsCount: number;
    }>;
};

export type DepartmentEnrolmentLevelHrefContext = {
    departmentLevelId: number;
    departmentCourseId: number;
    modeOfStudyId: string;
    courseName: string;
    levelName: string;
};

interface Props {
    departmentId: string;
    intakePeriodId: string | null;
    type?: string | null;
    initialModeOfStudyId?: string | null;
    summariesRouteName: string;
    resolveLevelHref: (context: DepartmentEnrolmentLevelHrefContext) => string;
}

const props = withDefaults(defineProps<Props>(), {
    type: null,
    initialModeOfStudyId: null,
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
const coursesByMode = ref<Record<string, CourseEnrolmentSummary[]>>({});
const loadedModes = ref<Record<string, boolean>>({});
const loadingPanel = ref(false);
const loadingMeta = ref(false);
const lastUpdatedLabel = ref('Updated just now');
const isReady = ref(false);

const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();

const summariesParams = (modeId?: string): Record<string, string> => {
    const params: Record<string, string> = {
        institution_department: props.departmentId,
    };

    if (props.intakePeriodId) {
        params.intake_period_id = props.intakePeriodId;
    }

    if (modeId) {
        params.mode_of_study_id = modeId;
    }

    if (props.type) {
        params.type = props.type;
    }

    return params;
};

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

const programmeCount = (modeId: string): number => (coursesByMode.value[modeId] ?? []).length;

const isModeLoaded = (modeId: string): boolean => {
    if (!props.intakePeriodId) {
        return false;
    }

    return Boolean(loadedModes.value[`${props.intakePeriodId}:${modeId}`]);
};

const totalEnrolments = computed(() => orderedModes.value.reduce((sum, mode) => sum + modeCount(mode), 0));

const legendItems = computed(() =>
    orderedModes.value.map((mode, index) => ({
        id: String(mode.id),
        label: mode.attributes.name,
        count: modeCount(mode),
        colorClass: modeCount(mode) > 0 ? LEGEND_COLORS[index % LEGEND_COLORS.length] : 'bg-muted-foreground/30',
        share: totalEnrolments.value > 0 ? (modeCount(mode) / totalEnrolments.value) * 100 : 0,
    })),
);

const modeIcon = (mode: ModeOfStudy): IconName => {
    const name = mode.attributes.name.toLowerCase();
    const match = MODE_ICONS.find((row) => name.includes(row.match));

    return match?.icon ?? IconName.users;
};

const levelBadge = (levelName: string): string => {
    const words = levelName.trim().split(/\s+/);
    if (words.length === 1) {
        return levelName.slice(0, 3).toUpperCase();
    }

    return words
        .map((word) => word[0] ?? '')
        .join('')
        .slice(0, 3)
        .toUpperCase();
};

const fetchModeTotals = async () => {
    if (!props.intakePeriodId) {
        modeTotals.value = {};
        return;
    }

    loadingMeta.value = true;
    try {
        const document = await HttpService.get(
            route(props.summariesRouteName, summariesParams()),
            jsonApiRequestConfig(),
        );
        const parsed = parseDepartmentEnrolmentSummaries(document);
        const totals: Record<number, number> = {};
        parsed.modeTotals.forEach((row) => {
            totals[row.modeOfStudyId] = row.count;
        });
        modeTotals.value = totals;
        lastUpdatedLabel.value = 'Updated just now';
    } catch {
        errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.application', 2) }));
    } finally {
        loadingMeta.value = false;
    }
};

const loadModePanel = async (modeId: string, force = false) => {
    if (!props.intakePeriodId || !modeId) {
        return;
    }

    const cacheKey = `${props.intakePeriodId}:${modeId}`;
    if (!force && loadedModes.value[cacheKey]) {
        return;
    }

    loadingPanel.value = true;
    try {
        const document = await HttpService.get(
            route(props.summariesRouteName, summariesParams(modeId)),
            jsonApiRequestConfig(),
        );
        const parsed = parseDepartmentEnrolmentSummaries(document);
        const totals: Record<number, number> = { ...modeTotals.value };
        parsed.modeTotals.forEach((row) => {
            totals[row.modeOfStudyId] = row.count;
        });
        modeTotals.value = totals;
        coursesByMode.value = {
            ...coursesByMode.value,
            [modeId]: parsed.courses,
        };
        loadedModes.value = {
            ...loadedModes.value,
            [cacheKey]: true,
        };
    } catch {
        errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.application', 2) }));
    } finally {
        loadingPanel.value = false;
    }
};

const resetAndReload = async () => {
    loadedModes.value = {};
    coursesByMode.value = {};
    await fetchModeTotals();
    if (openModeId.value) {
        await loadModePanel(openModeId.value, true);
    }
};

onMounted(async () => {
    await listModesOfStudy();

    const preferredMode =
        orderedModes.value.find((row) => String(row.id) === String(props.initialModeOfStudyId)) ??
        orderedModes.value[0] ??
        null;
    openModeId.value = preferredMode ? String(preferredMode.id) : '';

    await fetchModeTotals();
    if (openModeId.value) {
        await loadModePanel(openModeId.value);
        emit('update:modeOfStudyId', openModeId.value);
    }
    isReady.value = true;
});

watch(
    () => props.intakePeriodId,
    async (next, previous) => {
        if (!isReady.value || next === previous) {
            return;
        }
        await resetAndReload();
    },
);

watch(openModeId, async (modeId, previous) => {
    if (!isReady.value || !modeId || modeId === previous) {
        return;
    }
    await loadModePanel(modeId);
    emit('update:modeOfStudyId', modeId);
});
</script>

<template>
    <DataLoadingSpinner v-if="modesOfStudyLoading || loadingMeta" />

    <template v-else>
        <BaseAlert
            v-if="!intakePeriodId"
            :title="$t('trans.no_data')"
            :description="$t('trans.no_data_found_description', { data: $tChoice('trans.intake_period', 1) })"
        />

        <BaseAlert
            v-else-if="orderedModes.length === 0"
            :title="$t('trans.no_data')"
            :description="$t('trans.no_data_found_description', { data: $tChoice('trans.mode_of_study', 2) })"
        />

        <div v-else class="flex flex-col gap-3">
            <section class="rounded-xl border border-border/70 bg-card px-4 py-3 shadow-sm">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <span class="text-2xl font-bold tabular-nums tracking-tight text-foreground sm:text-3xl">
                            {{ totalEnrolments }}
                        </span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                            {{ $tChoice('trans.application', 2) }}
                        </span>
                    </div>
                    <span class="shrink-0 text-[11px] text-muted-foreground">{{ lastUpdatedLabel }}</span>
                </div>

                <div
                    class="mb-2 flex h-1.5 w-full overflow-hidden rounded-full bg-muted"
                    role="img"
                    :aria-label="`${totalEnrolments} ${$tChoice('trans.application', 2)}`"
                >
                    <template v-if="totalEnrolments > 0">
                        <div
                            v-for="item in legendItems"
                            :key="`bar-${item.id}`"
                            :class="item.colorClass"
                            :style="{ width: `${Math.max(item.share, item.count > 0 ? 2 : 0)}%` }"
                            class="h-full transition-[width]"
                        />
                    </template>
                </div>

                <ul class="flex flex-wrap gap-x-4 gap-y-1">
                    <li
                        v-for="item in legendItems"
                        :key="`legend-${item.id}`"
                        class="inline-flex items-center gap-1.5 text-[11px] text-muted-foreground"
                    >
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="item.colorClass" aria-hidden="true" />
                        <span>
                            <span class="font-medium text-foreground">{{ item.label }}</span>
                            <span class="text-muted-foreground"> · {{ item.count }}</span>
                        </span>
                    </li>
                </ul>
            </section>

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
                >
                    <div
                        class="flex flex-col gap-4 border-t border-border/60 pt-4"
                        :aria-busy="loadingPanel && openModeId === String(mode.id)"
                    >
                        <DataLoadingSpinner v-if="loadingPanel && openModeId === String(mode.id)" />

                        <template v-else-if="(coursesByMode[String(mode.id)] ?? []).length > 0">
                            <div
                                v-for="enrolment in coursesByMode[String(mode.id)]"
                                :key="enrolment.departmentCourseId"
                                class="flex flex-col gap-2"
                            >
                                <h4 class="text-xs font-bold uppercase tracking-[0.12em] text-foreground/80">
                                    {{ enrolment.courseName }}
                                </h4>

                                <div class="flex flex-col gap-2">
                                    <Link
                                        v-for="level in enrolment.levels"
                                        :key="level.departmentLevelId"
                                        :href="
                                            resolveLevelHref({
                                                departmentLevelId: level.departmentLevelId,
                                                departmentCourseId: enrolment.departmentCourseId,
                                                modeOfStudyId: String(mode.id),
                                                courseName: enrolment.courseName,
                                                levelName: level.levelName,
                                            })
                                        "
                                        class="group flex items-center gap-3 rounded-xl bg-primary/5 px-3 py-3 transition-colors hover:bg-primary/10"
                                    >
                                        <span
                                            class="inline-flex min-w-10 items-center justify-center rounded-md border border-primary/20 bg-card px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-primary"
                                        >
                                            {{ levelBadge(level.levelName) }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground group-hover:text-primary">
                                            {{ level.levelName }}
                                        </span>
                                        <span class="shrink-0 text-sm font-bold tabular-nums text-foreground">
                                            {{ level.enrolmentsCount }}
                                        </span>
                                    </Link>
                                </div>
                            </div>
                        </template>

                        <div
                            v-else
                            class="rounded-xl border border-dashed border-border bg-muted/30 px-4 py-6 text-center"
                        >
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ $t('trans.no_data') }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t('trans.no_data_found_description', {
                                        data: `${$tChoice('trans.application', 2)} — ${mode.attributes.name}`,
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </EnrolmentModeAccordionItem>
            </BaseAccordion>
        </div>
    </template>
</template>
