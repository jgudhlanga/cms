<script setup lang="ts">
import { BookOpen, Briefcase, GraduationCap, User, Users, UserRound } from '@lucide/vue';
import { useStudents } from '@/composables/students/useStudents';
import type { StudentFiltersState, StudentStats } from '@/types/students';
import { useDebounceFn } from '@vueuse/core';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { Component } from 'vue';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    filters: StudentFiltersState;
    loading?: boolean;
    refreshKey?: number;
}

type StatChip = {
    id: string;
    label: string;
    value: string;
    icon: Component;
    active: boolean;
    iconClass: string;
    valueClass: string;
    activeClass: string;
    ariaLabel: string;
    onClick: () => void;
};

type ChipGroup = {
    id: string;
    label: string;
    chips: StatChip[];
};

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    refreshKey: 0,
});

const emit = defineEmits<{
    (e: 'filter', filters: Partial<StudentFiltersState>): void;
}>();

const { fetchStudentStats } = useStudents();

const stats = ref<StudentStats>({
    global: {
        total: 0,
        male: 0,
        female: 0,
        byLevel: [],
        byModeOfStudy: [],
        byStudentType: [],
    },
    filtered: {
        total: 0,
    },
});
const isLocalLoading = ref(false);

const normalizeFilters = (filters: StudentFiltersState): string =>
    JSON.stringify({
        search: filters.search?.trim() || undefined,
        name: filters.name?.trim() || undefined,
        department: filters.department?.length ? [...filters.department].sort((a, b) => a - b) : undefined,
        level: filters.level?.length ? [...filters.level].sort((a, b) => a - b) : undefined,
        course: filters.course?.length ? [...filters.course].sort((a, b) => a - b) : undefined,
        mode_of_study: filters.mode_of_study?.length ? [...filters.mode_of_study].sort((a, b) => a - b) : undefined,
        gender: filters.gender || undefined,
        student_type: filters.student_type || undefined,
        with_trashed: filters.with_trashed || undefined,
    });

const filterSignature = computed(() => normalizeFilters(props.filters));
const effectiveLoading = computed(() => props.loading || isLocalLoading.value);

const loadStats = async () => {
    try {
        isLocalLoading.value = true;
        const res = await fetchStudentStats(props.filters);
        if (res) {
            stats.value = res;
        }
    } finally {
        isLocalLoading.value = false;
    }
};

const debouncedLoadStats = useDebounceFn(loadStats, 250);

onMounted(() => loadStats());
watch(filterSignature, (next, previous) => {
    if (next !== previous) {
        debouncedLoadStats();
    }
});
watch(() => props.refreshKey, () => loadStats());

const hasActiveFilters = computed(() => {
    const { filters } = props;

    return Boolean(
        filters.search ||
            filters.name ||
            filters.department?.length ||
            filters.level?.length ||
            filters.course?.length ||
            filters.mode_of_study?.length ||
            filters.gender ||
            filters.student_type ||
            filters.with_trashed,
    );
});

const showFilteredSubtitle = computed(
    () => hasActiveFilters.value && stats.value.filtered.total !== stats.value.global.total,
);

const visibleLevels = computed(() => stats.value.global.byLevel.filter((row) => row.count > 0));
const visibleModes = computed(() => stats.value.global.byModeOfStudy.filter((row) => row.count > 0));

const isGenderActive = (gender: 'male' | 'female') => props.filters.gender === gender;
const isLevelActive = (id: number) => props.filters.level?.includes(id) ?? false;
const isModeActive = (id: number) => props.filters.mode_of_study?.includes(id) ?? false;
const isStudentTypeActive = (type: 'direct' | 'apprentice') => props.filters.student_type === type;

const toggleGender = (gender: 'male' | 'female') => {
    emit('filter', { gender: isGenderActive(gender) ? undefined : gender });
};

const toggleLevel = (id: number) => {
    emit('filter', { level: isLevelActive(id) ? undefined : [id] });
};

const toggleMode = (id: number) => {
    const current = props.filters.mode_of_study ?? [];
    const next = current.includes(id) ? current.filter((value) => value !== id) : [...current, id];
    emit('filter', { mode_of_study: next.length ? next : undefined });
};

const toggleStudentType = (type: 'direct' | 'apprentice') => {
    emit('filter', { student_type: isStudentTypeActive(type) ? undefined : type });
};

const clearDimensionalFilters = () => {
    emit('filter', {
        gender: undefined,
        level: undefined,
        mode_of_study: undefined,
        student_type: undefined,
    });
};

const filterAriaLabel = (label: string, count: number) => `${label}, ${count}`;

const overviewChips = computed<StatChip[]>(() => [
    {
        id: 'total',
        label: trans('students.stat_total_students'),
        value: String(stats.value.global.total),
        icon: Users,
        active: false,
        iconClass: 'text-indigo-500',
        valueClass: 'text-indigo-700',
        activeClass: 'border-indigo-300 bg-indigo-50',
        ariaLabel: filterAriaLabel(trans('students.stat_total_students'), stats.value.global.total),
        onClick: clearDimensionalFilters,
    },
]);

const genderChips = computed<StatChip[]>(() => [
    {
        id: 'male',
        label: trans('students.stat_male'),
        value: String(stats.value.global.male),
        icon: User,
        active: isGenderActive('male'),
        iconClass: 'text-sky-500',
        valueClass: 'text-sky-700',
        activeClass: 'border-sky-300 bg-sky-50',
        ariaLabel: filterAriaLabel(trans('students.stat_male'), stats.value.global.male),
        onClick: () => toggleGender('male'),
    },
    {
        id: 'female',
        label: trans('students.stat_female'),
        value: String(stats.value.global.female),
        icon: UserRound,
        active: isGenderActive('female'),
        iconClass: 'text-rose-500',
        valueClass: 'text-rose-600',
        activeClass: 'border-rose-300 bg-rose-50',
        ariaLabel: filterAriaLabel(trans('students.stat_female'), stats.value.global.female),
        onClick: () => toggleGender('female'),
    },
]);

const levelChips = computed<StatChip[]>(() =>
    visibleLevels.value.map((level) => ({
        id: `level-${level.id}`,
        label: level.name,
        value: String(level.count),
        icon: GraduationCap,
        active: isLevelActive(level.id),
        iconClass: 'text-amber-600',
        valueClass: 'text-amber-700',
        activeClass: 'border-amber-300 bg-amber-50',
        ariaLabel: filterAriaLabel(level.name, level.count),
        onClick: () => toggleLevel(level.id),
    })),
);

const modeChips = computed<StatChip[]>(() =>
    visibleModes.value.map((mode) => ({
        id: `mode-${mode.id}`,
        label: mode.name,
        value: String(mode.count),
        icon: BookOpen,
        active: isModeActive(mode.id),
        iconClass: 'text-emerald-600',
        valueClass: 'text-emerald-700',
        activeClass: 'border-emerald-300 bg-emerald-50',
        ariaLabel: filterAriaLabel(mode.name, mode.count),
        onClick: () => toggleMode(mode.id),
    })),
);

const typeChips = computed<StatChip[]>(() =>
    stats.value.global.byStudentType.map((type) => ({
        id: `type-${type.id}`,
        label: type.name,
        value: String(type.count),
        icon: type.id === 'apprentice' ? Briefcase : User,
        active: isStudentTypeActive(type.id),
        iconClass: type.id === 'apprentice' ? 'text-violet-600' : 'text-slate-600',
        valueClass: type.id === 'apprentice' ? 'text-violet-700' : 'text-slate-700',
        activeClass: type.id === 'apprentice' ? 'border-violet-300 bg-violet-50' : 'border-slate-300 bg-slate-50',
        ariaLabel: filterAriaLabel(type.name, type.count),
        onClick: () => toggleStudentType(type.id),
    })),
);

const primaryGroups = computed<ChipGroup[]>(() => {
    const groups: ChipGroup[] = [
        { id: 'overview', label: `${trans('trans.overview')}:`, chips: overviewChips.value },
        { id: 'gender', label: `${trans_choice('trans.gender', 1)}:`, chips: genderChips.value },
    ];

    if (modeChips.value.length) {
        groups.push({ id: 'mode', label: `${trans('trans.mode')}:`, chips: modeChips.value });
    }

    if (typeChips.value.length) {
        groups.push({ id: 'type', label: `${trans_choice('trans.type', 1)}:`, chips: typeChips.value });
    }

    return groups;
});

const chipBaseClass =
    'inline-flex h-7 shrink-0 items-center gap-1.5 cursor-pointer rounded-full border px-2.5 text-xs font-medium transition focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

const groupLabelClass = 'shrink-0 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground';
</script>

<template>
    <div class="mb-3 space-y-2.5 border-b pb-3">
        <div class="space-y-2 transition-opacity" :class="{ 'pointer-events-none opacity-60': effectiveLoading }">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <div
                    v-for="group in primaryGroups"
                    :key="group.id"
                    class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1.5"
                >
                    <span :class="groupLabelClass">{{ group.label }}</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="chip in group.chips"
                            :key="chip.id"
                            type="button"
                            :aria-label="chip.ariaLabel"
                            :aria-pressed="chip.active || undefined"
                            :class="[
                                chipBaseClass,
                                chip.id === 'total'
                                    ? 'border-border bg-card hover:border-indigo-300 hover:bg-indigo-50'
                                    : chip.active
                                      ? chip.activeClass
                                      : 'border-border bg-card hover:border-primary/40',
                            ]"
                            @click="chip.onClick"
                        >
                            <component :is="chip.icon" class="h-3.5 w-3.5 shrink-0" :class="chip.iconClass" />
                            <span class="text-foreground whitespace-nowrap">{{ chip.label }}</span>
                            <span class="font-semibold tabular-nums" :class="chip.valueClass">{{ chip.value }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="levelChips.length" class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1.5">
                <span :class="groupLabelClass">{{ `${$tChoice('trans.level', 1)}:` }}</span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="chip in levelChips"
                        :key="chip.id"
                        type="button"
                        :aria-label="chip.ariaLabel"
                        :aria-pressed="chip.active"
                        :class="[
                            chipBaseClass,
                            chip.active ? chip.activeClass : 'border-border bg-card hover:border-primary/40',
                        ]"
                        @click="chip.onClick"
                    >
                        <component :is="chip.icon" class="h-3.5 w-3.5 shrink-0" :class="chip.iconClass" />
                        <span class="text-foreground whitespace-nowrap">{{ chip.label }}</span>
                        <span class="font-semibold tabular-nums" :class="chip.valueClass">{{ chip.value }}</span>
                    </button>
                </div>
            </div>
        </div>

        <p v-if="showFilteredSubtitle" class="text-muted-foreground text-sm">
            {{
                $t('students.showing_filtered_of_total', {
                    filtered: stats.filtered.total,
                    total: stats.global.total,
                })
            }}
        </p>
    </div>
</template>
