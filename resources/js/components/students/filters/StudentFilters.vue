<script setup lang="ts">
import { BookOpen, Briefcase, GraduationCap, User, UserRound, X } from '@lucide/vue';
import { computed, toRef } from 'vue';

import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import StudentFilterChip from '@/components/students/filters/StudentFilterChip.vue';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
import { IconName } from '@/enums/icons';
import type { StudentFiltersState, StudentStats } from '@/types/students';
import { trans } from 'laravel-vue-i18n';

interface Props {
    filters: StudentFiltersState;
    stats?: StudentStats | null;
}

type ActiveTag = {
    id: string;
    label: string;
    clear: () => void;
};

const props = withDefaults(defineProps<Props>(), {
    stats: null,
});

const emit = defineEmits<{
    (e: 'change', filters: StudentFiltersState): void;
    (e: 'filter', filters: Partial<StudentFiltersState>): void;
}>();

const {
    search,
    name,
    departmentSelection,
    levelSelection,
    courseSelection,
    departmentsLoading,
    courseOptions,
    coursesLoading,
    departmentOptions,
    selectedDepartmentIds,
    selectedLevelIds,
    whenDepartmentSearch,
    resetFilters,
} = useStudentFilters({
    filters: toRef(props, 'filters'),
    variant: 'index',
    onChange: (filters) => emit('change', filters),
});

const groupLabelClass = 'shrink-0 w-14 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground';

const globalStats = computed(
    () =>
        props.stats?.global ?? {
            total: 0,
            male: 0,
            female: 0,
            byLevel: [],
            byModeOfStudy: [],
            byStudentType: [],
        },
);

const filteredTotal = computed(() => props.stats?.filtered.total ?? globalStats.value.total);
const globalTotal = computed(() => globalStats.value.total);

const showShowingCount = computed(() => props.stats !== null);

const visibleLevels = computed(() => globalStats.value.byLevel.filter((row) => row.count > 0));
const visibleModes = computed(() => globalStats.value.byModeOfStudy.filter((row) => row.count > 0));

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

const clearField = (partial: Partial<StudentFiltersState>) => {
    emit('filter', partial);
};

const activeTags = computed<ActiveTag[]>(() => {
    const tags: ActiveTag[] = [];
    const { filters } = props;

    if (filters.gender === 'male') {
        tags.push({
            id: 'gender-male',
            label: trans('students.stat_male'),
            clear: () => clearField({ gender: undefined }),
        });
    } else if (filters.gender === 'female') {
        tags.push({
            id: 'gender-female',
            label: trans('students.stat_female'),
            clear: () => clearField({ gender: undefined }),
        });
    }

    for (const mode of visibleModes.value) {
        if (filters.mode_of_study?.includes(mode.id)) {
            tags.push({
                id: `mode-${mode.id}`,
                label: mode.name,
                clear: () => {
                    const next = (filters.mode_of_study ?? []).filter((id) => id !== mode.id);
                    clearField({ mode_of_study: next.length ? next : undefined });
                },
            });
        }
    }

    if (filters.student_type) {
        const typeRow = globalStats.value.byStudentType.find((row) => row.id === filters.student_type);
        tags.push({
            id: `type-${filters.student_type}`,
            label: typeRow?.name ?? filters.student_type,
            clear: () => clearField({ student_type: undefined }),
        });
    }

    if (filters.level?.length) {
        const levelLabel =
            (levelSelection.value ? String(levelSelection.value.label) : null) ??
            visibleLevels.value.find((row) => row.id === filters.level?.[0])?.name ??
            String(filters.level[0]);
        tags.push({
            id: `level-${filters.level[0]}`,
            label: levelLabel,
            clear: () => {
                levelSelection.value = null;
                courseSelection.value = [];
            },
        });
    }

    if (filters.department?.length && departmentSelection.value) {
        tags.push({
            id: `department-${filters.department[0]}`,
            label: String(departmentSelection.value.label),
            clear: () => {
                departmentSelection.value = null;
                courseSelection.value = [];
            },
        });
    }

    for (const course of courseSelection.value) {
        tags.push({
            id: `course-${course.value}`,
            label: String(course.label),
            clear: () => {
                courseSelection.value = courseSelection.value.filter(
                    (option) => Number(option.value) !== Number(course.value),
                );
            },
        });
    }

    if (filters.name?.trim()) {
        tags.push({
            id: 'name',
            label: filters.name.trim(),
            clear: () => {
                name.value = '';
            },
        });
    }

    if (filters.search?.trim()) {
        tags.push({
            id: 'search',
            label: filters.search.trim(),
            clear: () => {
                search.value = '';
            },
        });
    }

    return tags;
});

defineExpose({ resetFilters });
</script>

<template>
    <div class="rounded-lg border border-border bg-muted/40 p-2">
        <div class="mb-1.5 flex items-center justify-between gap-2">
            <p class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                {{ $tChoice('trans.filter', 2) }}
            </p>
            <p v-if="showShowingCount" class="text-[11px] font-medium text-primary tabular-nums">
                {{
                    $t('students.showing_filtered_of_total', {
                        filtered: filteredTotal.toLocaleString(),
                        total: globalTotal.toLocaleString(),
                    })
                }}
            </p>
        </div>

        <div class="flex flex-col gap-1.5">
            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                <span :class="groupLabelClass">{{ $tChoice('trans.gender', 1) }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <StudentFilterChip
                        :label="$t('students.stat_male')"
                        :count="globalStats.male.toLocaleString()"
                        :active="isGenderActive('male')"
                        :icon="User"
                        @click="toggleGender('male')"
                    />
                    <StudentFilterChip
                        :label="$t('students.stat_female')"
                        :count="globalStats.female.toLocaleString()"
                        :active="isGenderActive('female')"
                        :icon="UserRound"
                        @click="toggleGender('female')"
                    />
                </div>
            </div>

            <div v-if="visibleModes.length" class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                <span :class="groupLabelClass">{{ $t('trans.mode') }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <StudentFilterChip
                        v-for="mode in visibleModes"
                        :key="`mode-${mode.id}`"
                        :label="mode.name"
                        :count="mode.count.toLocaleString()"
                        :active="isModeActive(mode.id)"
                        :icon="BookOpen"
                        @click="toggleMode(mode.id)"
                    />
                </div>
            </div>

            <div
                v-if="globalStats.byStudentType.length"
                class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1"
            >
                <span :class="groupLabelClass">{{ $tChoice('trans.type', 1) }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <StudentFilterChip
                        v-for="type in globalStats.byStudentType"
                        :key="`type-${type.id}`"
                        :label="type.name"
                        :count="type.count.toLocaleString()"
                        :active="isStudentTypeActive(type.id)"
                        :icon="type.id === 'apprentice' ? Briefcase : User"
                        @click="toggleStudentType(type.id)"
                    />
                </div>
            </div>

            <div v-if="visibleLevels.length" class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                <span :class="groupLabelClass">{{ $tChoice('trans.level', 1) }}</span>
                <div class="flex flex-wrap items-center gap-1">
                    <StudentFilterChip
                        v-for="level in visibleLevels"
                        :key="`level-${level.id}`"
                        :label="level.name"
                        :count="level.count.toLocaleString()"
                        :active="isLevelActive(level.id)"
                        :icon="GraduationCap"
                        @click="toggleLevel(level.id)"
                    />
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-1.5 sm:grid-cols-2">
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="departmentSelection"
                        :options="departmentOptions"
                        :placeholder="$t('students.search_by_department_placeholder')"
                        :on-search="async (q: string) => await whenDepartmentSearch(q)"
                        :is-loading="departmentsLoading"
                        class="rounded-md"
                    />
                </div>
                <div class="min-w-0">
                    <BaseCombobox
                        v-model="courseSelection"
                        multiple
                        :options="courseOptions"
                        :placeholder="$t('students.search_by_course_placeholder')"
                        :is-loading="coursesLoading"
                        :disabled="!selectedDepartmentIds.length || !selectedLevelIds.length"
                        class="rounded-md"
                    />
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-1.5 sm:grid-cols-2">
                <div class="min-w-0">
                    <BaseInputWithIcon
                        :icon="IconName.user"
                        full-width
                        :placeholder="$t('students.search_by_name_placeholder')"
                        v-model="name"
                        class="w-full"
                    />
                </div>
                <div class="min-w-0">
                    <BaseInputWithIcon
                        :icon="IconName.search"
                        full-width
                        :placeholder="$t('students.search_by_student_details_placeholder')"
                        v-model="search"
                        class="w-full"
                    />
                </div>
            </div>

            <div v-if="activeTags.length" class="flex min-w-0 flex-wrap items-center gap-1 pt-0.5">
                <button
                    v-for="tag in activeTags"
                    :key="tag.id"
                    type="button"
                    class="inline-flex h-5 max-w-full items-center gap-0.5 rounded-md border border-border bg-muted px-1.5 text-[10px] font-medium text-foreground"
                    @click="tag.clear()"
                >
                    <span class="truncate">{{ tag.label }}</span>
                    <X class="h-2.5 w-2.5 shrink-0 opacity-70" />
                </button>
            </div>
        </div>
    </div>
</template>
