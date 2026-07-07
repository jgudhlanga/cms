import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import HttpService from '@/services/http.service';
import type { DepartmentLevel, DepartmentLevelCourse } from '@/types/department-meta-data';
import type { InstitutionDepartment, Level } from '@/types/institution';
import type { StudentFiltersState } from '@/types/students';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { debounce } from 'lodash';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch, type Ref } from 'vue';

export type StudentFilterVariant = 'index' | 'export' | 'program';

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

type UseStudentFiltersOptions = {
    filters: Ref<StudentFiltersState>;
    variant: StudentFilterVariant;
    onChange: (filters: StudentFiltersState) => void;
};

export function useStudentFilters({ filters, variant, onChange }: UseStudentFiltersOptions) {
    const buildAllGendersOption = (): SelectOption => ({
        value: 'all',
        label: 'All',
    });

    const search = ref(filters.value.search ?? '');
    const name = ref(filters.value.name ?? '');

    const departmentSelection = ref<SelectOption | null>(null);
    const levelSelection = ref<SelectOption | null>(null);
    const courseSelection = ref<SelectOption[]>([]);
    const modeOfStudySelection = ref<SelectOption[]>([]);
    const genderSelection = ref<SelectOption | null>(buildAllGendersOption());

    const { isLoading: departmentsLoading, departments, listDepartments } = useInstitutionDepartments();
    const { isLoading: modesLoading, modesOfStudy, listModesOfStudy } = useModeOfStudy();

    const courseOptions = ref<SelectOption[]>([]);
    const coursesLoading = ref(false);

    const departmentLevelsForFilter = ref<DepartmentLevel[]>([]);
    const departmentLevelsLoading = ref(false);

    const globalLevelOptions = ref<SelectOption[]>([]);
    const allLevelsLoading = ref(false);

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

    const levelOptions = computed<SelectOption[]>(() => {
        const deptId = Number(departmentSelection.value?.value ?? 0);
        if (deptId > 0) {
            const rows = departmentLevelsForFilter.value;
            if (!rows.length) {
                return [];
            }
            const byLevelId = new Map<number, { label: string; position: number }>();
            for (const dl of rows) {
                const levelId = Number(dl.attributes?.levelId ?? 0);
                if (levelId <= 0) {
                    continue;
                }
                const position = Number(dl.attributes?.levelPosition ?? 0);
                const label = dl.attributes?.level ?? '';
                const existing = byLevelId.get(levelId);
                if (!existing || position < existing.position) {
                    byLevelId.set(levelId, { label: label || existing?.label || '', position });
                }
            }
            return [...byLevelId.entries()]
                .sort(([, a], [, b]) => a.position - b.position)
                .map(([value, { label }]) => ({ value, label }));
        }
        return globalLevelOptions.value;
    });

    const levelsLoading = computed(() => {
        const deptId = Number(departmentSelection.value?.value ?? 0);
        return deptId > 0 ? departmentLevelsLoading.value : allLevelsLoading.value;
    });

    async function loadAllLevelsForFilter(levelSearch = ''): Promise<void> {
        allLevelsLoading.value = true;
        try {
            const res = await HttpService.get(
                route('v1.levels.index', {
                    ...(levelSearch.trim() ? { search: levelSearch.trim() } : {}),
                    per_page: 100,
                }),
            );
            const rows = unwrapList<Level>(res);
            const sorted = [...rows].sort((a, b) => {
                const pa = Number(a.attributes?.position ?? 0);
                const pb = Number(b.attributes?.position ?? 0);
                if (pa !== pb) {
                    return pa - pb;
                }
                return String(a.attributes?.name ?? '').localeCompare(String(b.attributes?.name ?? ''));
            });
            globalLevelOptions.value = sorted
                .map((item) => ({
                    value: Number(item.id ?? 0),
                    label: item.attributes?.name ?? '',
                }))
                .filter((o) => o.value > 0 && o.label);
        } finally {
            allLevelsLoading.value = false;
        }
    }

    async function loadDepartmentLevelsForFilter(deptId: number): Promise<void> {
        if (!deptId || deptId <= 0) {
            departmentLevelsForFilter.value = [];
            return;
        }
        departmentLevelsLoading.value = true;
        try {
            const metaRes = await HttpService.get(route('v1.department-metadata.levels', { institution_department: deptId }));
            departmentLevelsForFilter.value = unwrapMetadataDepartmentLevels(metaRes);
        } finally {
            departmentLevelsLoading.value = false;
        }
    }

    const genderOptions = computed<SelectOption[]>(() => [
        { value: 'all', label: trans('students.filter_all_genders') },
        { value: 'male', label: trans_choice('general.male', 1) },
        { value: 'female', label: trans_choice('general.female', 1) },
    ]);

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

    watch(departmentSelection, async () => {
        levelSelection.value = null;
        courseSelection.value = [];
        courseOptions.value = [];
        const deptId = Number(departmentSelection.value?.value ?? 0);
        await loadDepartmentLevelsForFilter(deptId);
    });

    const whenDepartmentSearch = debounce(async (q: string) => {
        await listDepartments(route('v1.institution-departments.index', { is_academic: 1, page_size: 'all', search: q }));
    }, 600);

    const whenModeSearch = debounce(async (q: string) => {
        await listModesOfStudy(q);
    }, 600);

    const whenLevelSearch = debounce(async (q: string) => {
        const deptId = Number(departmentSelection.value?.value ?? 0);
        if (deptId > 0) {
            return;
        }
        await loadAllLevelsForFilter(q);
    }, 600);

    const toIdArray = (rows: SelectOption[]): number[] | undefined => {
        const ids = rows.map((o) => Number(o.value)).filter((id) => id > 0);
        return ids.length ? ids : undefined;
    };

    const toOptionalSingleIdArray = (opt: SelectOption | null): number[] | undefined => {
        const id = Number(opt?.value ?? 0);
        return id > 0 ? [id] : undefined;
    };

    const resolveGenderFilter = (): StudentFiltersState['gender'] => {
        const value = genderSelection.value?.value ? String(genderSelection.value.value) : '';

        return value === 'male' || value === 'female' ? value : undefined;
    };

    const normalizeFiltersState = (state: StudentFiltersState): Record<string, unknown> => {
        const normalizeNumberArray = (value?: number[] | null): number[] | undefined =>
            value?.length ? [...value].map((item) => Number(item)).sort((a, b) => a - b) : undefined;

        const normalizeStringArray = (value?: string[] | null): string[] | undefined =>
            value?.length ? [...value].map((item) => String(item)).sort((a, b) => a.localeCompare(b)) : undefined;

        return {
            search: state.search?.trim() || undefined,
            name: state.name?.trim() || undefined,
            department: normalizeNumberArray(state.department),
            level: normalizeNumberArray(state.level),
            course: normalizeNumberArray(state.course),
            mode_of_study: normalizeNumberArray(state.mode_of_study),
            gender: state.gender || undefined,
            academic_year: normalizeNumberArray(state.academic_year),
            calendar_type: normalizeStringArray(state.calendar_type),
            with_trashed: state.with_trashed || undefined,
        };
    };

    const areFiltersEquivalent = (left: StudentFiltersState, right: StudentFiltersState): boolean =>
        JSON.stringify(normalizeFiltersState(left)) === JSON.stringify(normalizeFiltersState(right));

    const areOptionIdsEqual = (left: SelectOption[], rightIds: number[]): boolean => {
        const leftIds = left.map((option) => Number(option.value)).filter((id) => id > 0);

        return JSON.stringify(leftIds) === JSON.stringify(rightIds);
    };

    const buildFiltersState = (): StudentFiltersState => {
        const comboboxFilters: StudentFiltersState = {
            department: toOptionalSingleIdArray(departmentSelection.value),
            level: toOptionalSingleIdArray(levelSelection.value),
            course: toIdArray(courseSelection.value),
            mode_of_study: toIdArray(modeOfStudySelection.value),
            gender: resolveGenderFilter(),
        };

        if (variant === 'export' || variant === 'program') {
            return {
                department: comboboxFilters.department,
                level: comboboxFilters.level,
                course: comboboxFilters.course,
                ...(variant === 'export'
                    ? {
                          mode_of_study: comboboxFilters.mode_of_study,
                          gender: comboboxFilters.gender,
                      }
                    : {}),
            };
        }

        return {
            search: search.value || undefined,
            name: name.value || undefined,
            ...comboboxFilters,
        };
    };

    const isExternalFilterSync = ref(false);

    const applyFilters = useDebounceFn(() => {
        if (isExternalFilterSync.value) {
            return;
        }

        const nextState = buildFiltersState();

        if (areFiltersEquivalent(nextState, filters.value)) {
            return;
        }

        onChange(nextState);
    }, 400);

    if (variant === 'index') {
        watch(
            () => filters.value,
            (next) => {
                isExternalFilterSync.value = true;

                if ((next.search ?? '') !== search.value) {
                    search.value = next.search ?? '';
                }

                if ((next.name ?? '') !== name.value) {
                    name.value = next.name ?? '';
                }

                if (next.gender === 'male' || next.gender === 'female') {
                    const nextGenderOption = genderOptions.value.find((option) => option.value === next.gender) ?? null;
                    if (nextGenderOption && genderSelection.value?.value !== nextGenderOption.value) {
                        genderSelection.value = nextGenderOption;
                    }
                } else if (!next.gender) {
                    if (genderSelection.value?.value !== 'all') {
                        genderSelection.value = buildAllGendersOption();
                    }
                }

                const levelId = next.level?.[0];
                if (levelId) {
                    const levelOption =
                        levelOptions.value.find((option) => Number(option.value) === levelId) ??
                        globalLevelOptions.value.find((option) => Number(option.value) === levelId);
                    if (levelOption && Number(levelSelection.value?.value ?? 0) !== levelId) {
                        levelSelection.value = levelOption;
                    }
                } else if (!next.level?.length) {
                    if (levelSelection.value !== null) {
                        levelSelection.value = null;
                    }
                }

                const modeIds = next.mode_of_study ?? [];
                if (modeIds.length) {
                    const nextModeOptions = modeIds
                        .map((id) => modeOfStudyOptions.value.find((option) => Number(option.value) === id))
                        .filter((option): option is SelectOption => option !== undefined);
                    if (!areOptionIdsEqual(modeOfStudySelection.value, modeIds)) {
                        modeOfStudySelection.value = nextModeOptions;
                    }
                } else if (!modeIds.length) {
                    if (modeOfStudySelection.value.length) {
                        modeOfStudySelection.value = [];
                    }
                }

                isExternalFilterSync.value = false;
            },
            { deep: true },
        );
    }

    const watchedRefs =
        variant === 'index'
            ? [search, name, departmentSelection, levelSelection, courseSelection, modeOfStudySelection, genderSelection]
            : variant === 'program'
              ? [departmentSelection, levelSelection, courseSelection]
              : [departmentSelection, levelSelection, courseSelection, modeOfStudySelection, genderSelection];

    watch(watchedRefs, applyFilters, { deep: true });

    const resetFilters = () => {
        name.value = '';
        search.value = '';
        departmentSelection.value = null;
        levelSelection.value = null;
        courseSelection.value = [];
        modeOfStudySelection.value = [];
        genderSelection.value = buildAllGendersOption();
        departmentLevelsForFilter.value = [];
    };

    onMounted(async () => {
        const tasks = [
            listDepartments(route('v1.institution-departments.index', { is_academic: 1, page_size: 'all' })),
            loadAllLevelsForFilter(),
        ];

        if (variant !== 'program') {
            tasks.push(listModesOfStudy());
        }

        await Promise.all(tasks);
    });

    return {
        search,
        name,
        departmentSelection,
        levelSelection,
        courseSelection,
        modeOfStudySelection,
        genderSelection,
        departmentsLoading,
        modesLoading,
        courseOptions,
        coursesLoading,
        departmentOptions,
        levelOptions,
        levelsLoading,
        genderOptions,
        modeOfStudyOptions,
        selectedDepartmentIds,
        selectedLevelIds,
        whenDepartmentSearch,
        whenModeSearch,
        whenLevelSearch,
        resetFilters,
    };
}
