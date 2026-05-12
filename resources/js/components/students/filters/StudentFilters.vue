<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { debounce } from 'lodash';

import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { useLevels } from '@/composables/institution/useLevels';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { IconName } from '@/enums/icons';
import HttpService from '@/services/http.service';
import type { DepartmentLevel, DepartmentLevelCourse } from '@/types/department-meta-data';
import type { InstitutionDepartment } from '@/types/institution';
import type { StudentFiltersState } from '@/types/students';
import type { SelectOption } from '@/types/utils';

interface Props {
    filters: StudentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: StudentFiltersState): void;
}>();

function unwrapList<T>(res: unknown): T[] {
    if (Array.isArray(res)) {
        return res as T[];
    }
    if (res && typeof res === 'object' && 'data' in res && Array.isArray((res as { data: T[] }).data)) {
        return (res as { data: T[] }).data;
    }
    return [];
}

/** `GET v1/department-metadata/levels` returns `{ levels: { data: [...] } | [...], ... }`. */
function unwrapMetadataDepartmentLevels(res: unknown): DepartmentLevel[] {
    if (!res || typeof res !== 'object') {
        return [];
    }
    const levels = (res as { levels?: unknown }).levels;
    if (Array.isArray(levels)) {
        return levels as DepartmentLevel[];
    }
    if (levels && typeof levels === 'object' && 'data' in (levels as object)) {
        const rows = (levels as { data: unknown[] }).data;
        if (Array.isArray(rows)) {
            return rows as DepartmentLevel[];
        }
    }
    return [];
}

const search = ref(props.filters.search ?? '');
const name = ref(props.filters.name ?? '');

const departmentSelection = ref<SelectOption | null>(null);
const levelSelection = ref<SelectOption | null>(null);
const courseSelection = ref<SelectOption[]>([]);
const modeOfStudySelection = ref<SelectOption[]>([]);

const { isLoading: departmentsLoading, departments, listDepartments } = useInstitutionDepartments();
const { isLoading: levelsLoading, levels, listLevels } = useLevels();
const { isLoading: modesLoading, modesOfStudy, listModesOfStudy } = useModeOfStudy();

const courseOptions = ref<SelectOption[]>([]);
const coursesLoading = ref(false);

const departmentOptions = computed<SelectOption[]>(() => {
    const rows = departments.value?.data as InstitutionDepartment[] | undefined;
    if (!rows?.length) {
        return [];
    }
    return rows.map((item) => ({
        value: Number(item.id?.toString() ?? ''),
        label: item?.attributes?.department ?? '',
    }));
});

const levelOptions = computed<SelectOption[]>(() =>
    levels.value.map((level) => ({
        value: Number(level.id?.toString() ?? ''),
        label: level?.attributes?.name ?? '',
    })),
);

const modeOfStudyOptions = computed<SelectOption[]>(() => {
    const rows = modesOfStudy.value ?? [];
    return rows.map((mode) => ({
        value: Number(mode.id?.toString() ?? ''),
        label: mode?.attributes?.name ?? '',
    }));
});

const selectedDepartmentIds = computed(() => {
    const id = Number(departmentSelection.value?.value ?? 0);
    return id > 0 ? [id] : [];
});
const selectedLevelIds = computed(() => {
    const id = Number(levelSelection.value?.value ?? 0);
    return id > 0 ? [id] : [];
});

const loadAggregatedCourses = async () => {
    const deptIds = selectedDepartmentIds.value;
    const levelIdSet = new Set(selectedLevelIds.value.map((id) => Number(id)));
    if (!deptIds.length || !levelIdSet.size) {
        courseOptions.value = [];
        courseSelection.value = [];
        return;
    }

    coursesLoading.value = true;
    try {
        const merged = new Map<number, SelectOption>();
        for (const deptId of deptIds) {
            const metaRes = await HttpService.get(route('v1.department-metadata.levels', { institution_department: deptId }));
            const dlRows = unwrapMetadataDepartmentLevels(metaRes);

            const allowedDeptLevelIds = new Set<number>();
            for (const dl of dlRows) {
                const lid = Number(dl.attributes?.levelId ?? 0);
                if (!levelIdSet.has(lid)) {
                    continue;
                }
                const dlId = Number(dl.id ?? 0);
                if (dlId > 0) {
                    allowedDeptLevelIds.add(dlId);
                }
            }

            if (!allowedDeptLevelIds.size) {
                continue;
            }

            const coursesRes = await HttpService.get(
                route('v1.department-level-courses.by-institution-department', { institution_department: deptId }),
            );
            const courseRows = unwrapList<DepartmentLevelCourse>(coursesRes);
            for (const c of courseRows) {
                const dlId = Number(c.departmentLevelId ?? 0);
                if (!allowedDeptLevelIds.has(dlId)) {
                    continue;
                }
                const dcid = Number(c.departmentCourseId ?? 0);
                if (dcid && !merged.has(dcid)) {
                    merged.set(dcid, { value: dcid, label: c.course ?? '' });
                }
            }
        }
        courseOptions.value = [...merged.values()].sort((a, b) => String(a.label).localeCompare(String(b.label)));
        const allowed = new Set(courseOptions.value.map((o) => Number(o.value)));
        courseSelection.value = courseSelection.value.filter((o) => allowed.has(Number(o.value)));
    } finally {
        coursesLoading.value = false;
    }
};

const debouncedLoadCourses = debounce(() => {
    void loadAggregatedCourses();
}, 400);

watch([selectedDepartmentIds, selectedLevelIds], debouncedLoadCourses);

watch(departmentSelection, () => {
    levelSelection.value = null;
    courseSelection.value = [];
    courseOptions.value = [];
});

const whenDepartmentSearch = debounce(async (q: string) => {
    await listDepartments(route('v1.institution-departments.index', { is_academic: 1, page_size: 'all', search: q }));
}, 600);

const whenLevelSearch = debounce(async (q: string) => {
    await listLevels(q);
}, 600);

const whenModeSearch = debounce(async (q: string) => {
    await listModesOfStudy(q);
}, 600);

const toIdArray = (rows: SelectOption[]): number[] | undefined => {
    const ids = rows.map((o) => Number(o.value)).filter((id) => id > 0);
    return ids.length ? ids : undefined;
};

const toOptionalSingleIdArray = (opt: SelectOption | null): number[] | undefined => {
    const id = Number(opt?.value ?? 0);
    return id > 0 ? [id] : undefined;
};

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        name: name.value || undefined,
        department: toOptionalSingleIdArray(departmentSelection.value),
        level: toOptionalSingleIdArray(levelSelection.value),
        course: toIdArray(courseSelection.value),
        mode_of_study: toIdArray(modeOfStudySelection.value),
    });
}, 400);

watch(
    [
        search,
        name,
        departmentSelection,
        levelSelection,
        courseSelection,
        modeOfStudySelection,
    ],
    applyFilters,
    { deep: true },
);

const resetFilters = () => {
    name.value = '';
    search.value = '';
    departmentSelection.value = null;
    levelSelection.value = null;
    courseSelection.value = [];
    modeOfStudySelection.value = [];
};

onMounted(async () => {
    await Promise.all([
        listDepartments(route('v1.institution-departments.index', { is_academic: 1, page_size: 'all' })),
        listLevels(),
        listModesOfStudy(),
    ]);
});
</script>

<template>
    <div class="flex w-full max-w-full min-w-0 flex-col gap-4">
        <!-- Row 1: three equal columns on md+ -->
        <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-3 md:gap-4">
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
            <div class="min-w-0">
                <BaseCombobox
                    v-model="modeOfStudySelection"
                    multiple
                    :options="modeOfStudyOptions"
                    :placeholder="$t('students.search_by_mode_of_study_placeholder')"
                    :on-search="async (q: string) => await whenModeSearch(q)"
                    :is-loading="modesLoading"
                    class="rounded-full"
                />
            </div>
        </div>

        <!-- Row 2: three equal columns on md+ -->
        <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-3 md:gap-4">
            <div class="min-w-0">
                <BaseCombobox
                    v-model="departmentSelection"
                    :options="departmentOptions"
                    :placeholder="$t('students.search_by_department_placeholder')"
                    :on-search="async (q: string) => await whenDepartmentSearch(q)"
                    :is-loading="departmentsLoading"
                    class="rounded-full"
                />
            </div>
            <div class="min-w-0">
                <BaseCombobox
                    v-model="levelSelection"
                    :options="levelOptions"
                    :placeholder="$t('students.search_by_level_placeholder')"
                    :on-search="async (q: string) => await whenLevelSearch(q)"
                    :is-loading="levelsLoading"
                    class="rounded-full"
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
                    class="rounded-full"
                />
            </div>
        </div>
        <div class="flex justify-end">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>
