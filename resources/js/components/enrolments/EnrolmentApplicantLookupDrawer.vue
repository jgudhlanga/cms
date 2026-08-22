<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { enrolmentStatusFromQuery } from '@/lib/enrolmentStatusOrigin';
import { toTitleCase } from '@/lib/enrolmentClassListPresentation';
import HttpService from '@/services/http.service';
import type { EnrolmentApplicantLookupResult } from '@/types/enrolments';
import type { ClassListType } from '@/types/enrolments';
import type { InstitutionDepartment } from '@/types/institution';
import type { DepartmentLevelCourse } from '@/types/department-meta-data';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type { SelectOption } from '@/types/utils';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    listType: ClassListType | string;
    intakePeriodId: string | number;
    intakePeriodName: string;
    from?: string | null;
    initialDepartmentId?: string | number | null;
    initialLevelId?: string | number | null;
    initialCourseId?: string | number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const MIN_QUERY_LENGTH = 2;

const searchQuery = ref('');
const results = ref<EnrolmentApplicantLookupResult[]>([]);
const isSearching = ref(false);
const searchError = ref<string | null>(null);
const hasSearched = ref(false);
const isInitializing = ref(false);

/** vue3-select binds the option `value`, not the full option object */
const selectedDepartmentId = ref<number | null>(null);
const selectedLevelId = ref<number | null>(null);
const selectedCourseId = ref<number | null>(null);

const { isLoading: departmentsLoading, departments, listDepartments } = useInstitutionDepartments();
const {
    isLoading: levelsLoading,
    departmentLevels,
    levelCourses,
    listAdminDepartmentLevels,
    listAdminLevelCourses,
} = useDepartmentLevels();

const normalizeDepartmentRows = (payload: ApiFilterResponse | InstitutionDepartment[] | null): InstitutionDepartment[] => {
    if (!payload) {
        return [];
    }

    if (Array.isArray(payload)) {
        return payload;
    }

    return (payload.data as InstitutionDepartment[] | undefined) ?? [];
};

const departmentOptions = computed((): SelectOption[] =>
    normalizeDepartmentRows(departments.value).map((item) => ({
        value: Number(item.id),
        label: item.attributes?.department ?? '',
    })),
);

const levelOptions = computed((): SelectOption[] =>
    departmentLevels.value.map((item) => ({
        value: Number(item.id),
        label: item.attributes?.level ?? '',
    })),
);

const courseOptions = computed((): SelectOption[] =>
    levelCourses.value.map((item: DepartmentLevelCourse) => ({
        value: Number(item.departmentCourseId),
        label: item.course ?? '',
    })),
);

const lockedContextLabel = computed(() =>
    trans('enrolments.lookup_locked_context', {
        type: toTitleCase(String(props.listType)),
        intake: props.intakePeriodName,
    }),
);

const originQuery = computed(() =>
    enrolmentStatusFromQuery({
        from: props.from ?? undefined,
    }),
);

const trimmedQuery = computed(() => searchQuery.value.trim());

const hasTypedSearch = computed(() => trimmedQuery.value.length >= MIN_QUERY_LENGTH);
const hasCourse = computed(() => selectedCourseId.value != null);
const canFetch = computed(() => hasTypedSearch.value || hasCourse.value);
const showingCourseSuggestions = computed(
    () => hasCourse.value && !hasTypedSearch.value && results.value.length > 0,
);

const emptyStateMessage = computed(() => {
    if (searchError.value) {
        return searchError.value;
    }

    if (hasSearched.value) {
        return hasCourse.value && !hasTypedSearch.value
            ? trans('enrolments.lookup_no_course_suggestions')
            : trans('enrolments.lookup_no_results');
    }

    return trans('enrolments.lookup_start_typing');
});

const resolveDestination = (applicationId: number): string => {
    const type = String(props.listType);
    const baseParams = {
        student_application: String(applicationId),
        type,
        ...originQuery.value,
    };

    if (type === 'verified' || type === 'final') {
        return route('enrolments.confirm', { ...baseParams, type: 'verified' });
    }

    return route('enrolments.verify', baseParams);
};

const resetLookupState = () => {
    searchQuery.value = '';
    results.value = [];
    searchError.value = null;
    hasSearched.value = false;
    selectedDepartmentId.value = null;
    selectedLevelId.value = null;
    selectedCourseId.value = null;
    departmentLevels.value = [];
    levelCourses.value = [];
};

const runSearch = async () => {
    if (!canFetch.value) {
        results.value = [];
        searchError.value = null;
        hasSearched.value = false;
        return;
    }

    if (!hasTypedSearch.value && trimmedQuery.value.length > 0) {
        return;
    }

    isSearching.value = true;
    searchError.value = null;

    try {
        const params: Record<string, string | number> = {
            type: String(props.listType),
            intake_period_id: Number(props.intakePeriodId),
        };

        if (hasTypedSearch.value) {
            params.q = trimmedQuery.value;
        }
        if (selectedDepartmentId.value != null) {
            params.institution_department_id = selectedDepartmentId.value;
        }
        if (selectedLevelId.value != null) {
            params.department_level_id = selectedLevelId.value;
        }
        if (selectedCourseId.value != null) {
            params.department_course_id = selectedCourseId.value;
        }

        const response = await HttpService.get(route('enrolments.applicant-lookup'), { params });
        const rows = Array.isArray(response) ? response : (response?.data ?? []);
        results.value = rows as EnrolmentApplicantLookupResult[];
        hasSearched.value = true;
    } catch {
        searchError.value = hasCourse.value && !hasTypedSearch.value
            ? trans('enrolments.lookup_no_course_suggestions')
            : trans('enrolments.lookup_no_results');
        results.value = [];
        hasSearched.value = true;
    } finally {
        isSearching.value = false;
    }
};

const debouncedSearch = useDebounceFn(runSearch, 350);

const scheduleSearchIfReady = (immediate = false) => {
    if (!props.open || isInitializing.value) {
        return;
    }

    if (hasTypedSearch.value) {
        if (immediate) {
            void runSearch();
        } else {
            void debouncedSearch();
        }
        return;
    }

    if (trimmedQuery.value.length > 0) {
        return;
    }

    if (hasCourse.value) {
        void runSearch();
        return;
    }

    results.value = [];
    searchError.value = null;
    hasSearched.value = false;
};

const close = () => emit('update:open', false);

const resetFilters = () => {
    isInitializing.value = true;

    selectedDepartmentId.value = null;
    selectedLevelId.value = null;
    selectedCourseId.value = null;
    departmentLevels.value = [];
    levelCourses.value = [];
    searchQuery.value = '';
    results.value = [];
    searchError.value = null;
    hasSearched.value = false;

    isInitializing.value = false;
};

/** Prevent Radix dismiss + click-through to links under the overlay when using vue-select. */
const onInteractOutside = (event: { preventDefault: () => void }) => {
    event.preventDefault();
};

const navigateToApplicant = (applicationId: number) => {
    router.visit(resolveDestination(applicationId));
};

const loadFilterOptions = async () => {
    await listDepartments(route('v1.institution-departments.index', { is_academic: 1, page_size: 'all' }));
};

const applyInitialFilters = async () => {
    selectedDepartmentId.value = null;
    selectedLevelId.value = null;
    selectedCourseId.value = null;

    if (!props.initialDepartmentId) {
        return;
    }

    const departmentId = Number(props.initialDepartmentId);
    if (!departmentOptions.value.some((option) => Number(option.value) === departmentId)) {
        return;
    }

    selectedDepartmentId.value = departmentId;
    await listAdminDepartmentLevels(route('v1.dropdowns.institution-departments.levels', String(departmentId)));

    if (!props.initialLevelId) {
        return;
    }

    const levelId = Number(props.initialLevelId);
    if (!levelOptions.value.some((option) => Number(option.value) === levelId)) {
        return;
    }

    selectedLevelId.value = levelId;
    await listAdminLevelCourses(route('v1.dropdowns.department-level.courses', String(levelId)));

    if (!props.initialCourseId) {
        return;
    }

    const courseId = Number(props.initialCourseId);
    if (courseOptions.value.some((option) => Number(option.value) === courseId)) {
        selectedCourseId.value = courseId;
    }
};

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) {
            resetLookupState();
            return;
        }

        isInitializing.value = true;

        try {
            resetLookupState();
            await loadFilterOptions();
            await applyInitialFilters();
        } finally {
            isInitializing.value = false;
        }

        scheduleSearchIfReady(true);
    },
);

watch(searchQuery, () => scheduleSearchIfReady());

watch(selectedDepartmentId, async (departmentId, previous) => {
    if (isInitializing.value || departmentId === previous) {
        return;
    }

    selectedLevelId.value = null;
    selectedCourseId.value = null;
    departmentLevels.value = [];
    levelCourses.value = [];

    if (departmentId != null) {
        await listAdminDepartmentLevels(route('v1.dropdowns.institution-departments.levels', String(departmentId)));
    }

    scheduleSearchIfReady(true);
});

watch(selectedLevelId, async (levelId, previous) => {
    if (isInitializing.value || levelId === previous) {
        return;
    }

    selectedCourseId.value = null;
    levelCourses.value = [];

    if (levelId != null) {
        await listAdminLevelCourses(route('v1.dropdowns.department-level.courses', String(levelId)));
    }

    scheduleSearchIfReady(true);
});

watch(selectedCourseId, () => scheduleSearchIfReady(true));
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent
            side="right"
            class="flex w-full flex-col p-0 sm:max-w-md"
            @pointer-down-outside="onInteractOutside"
            @focus-outside="onInteractOutside"
            @interact-outside="onInteractOutside"
        >
            <SheetHeader class="border-b bg-muted/30 px-5 py-4">
                <SheetTitle>{{ $t('enrolments.find_applicant') }}</SheetTitle>
                <SheetDescription class="text-sm">
                    {{ $t('enrolments.find_applicant_description') }}
                </SheetDescription>
                <p class="mt-1 text-xs font-medium text-muted-foreground">{{ lockedContextLabel }}</p>
            </SheetHeader>

            <div class="flex flex-1 flex-col gap-3 overflow-y-auto px-5 py-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs text-muted-foreground">{{ $t('enrolments.lookup_search_hint') }}</p>
                    <button
                        type="button"
                        class="shrink-0 cursor-pointer text-xs font-semibold text-primary hover:underline"
                        @click="resetFilters"
                    >
                        {{ $t('enrolments.lookup_reset_filters') }}
                    </button>
                </div>

                <BaseSelect
                    v-model="selectedDepartmentId"
                    :label="$tChoice('trans.department', 1)"
                    :options="departmentOptions"
                    :loading="departmentsLoading"
                    :is-clearable="true"
                    :teleport="false"
                    :placeholder="$t('enrolments.lookup_all_departments')"
                />
                <BaseSelect
                    v-model="selectedLevelId"
                    :label="$tChoice('trans.level', 1)"
                    :options="levelOptions"
                    :loading="levelsLoading"
                    :is-clearable="true"
                    :is-disabled="selectedDepartmentId == null"
                    :teleport="false"
                    :placeholder="$t('enrolments.lookup_all_levels')"
                />
                <BaseSelect
                    v-model="selectedCourseId"
                    :label="$tChoice('trans.course', 1)"
                    :options="courseOptions"
                    :is-clearable="true"
                    :is-disabled="selectedLevelId == null"
                    :teleport="false"
                    :placeholder="$t('enrolments.lookup_all_courses')"
                />

                <label class="relative block">
                    <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="h-10 w-full rounded-lg border border-border bg-card pr-3 pl-9 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        :placeholder="$t('enrolments.lookup_search_placeholder')"
                        :aria-label="$t('enrolments.lookup_search_placeholder')"
                        @keydown.enter.prevent
                    />
                </label>

                <div v-if="isSearching" class="py-6 text-center text-sm text-muted-foreground">Searching…</div>

                <div
                    v-else-if="results.length === 0"
                    class="rounded-lg border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    {{ emptyStateMessage }}
                </div>

                <div v-else class="flex flex-col gap-2">
                    <p v-if="showingCourseSuggestions" class="text-xs text-muted-foreground">
                        {{ $t('enrolments.lookup_course_suggestions_hint') }}
                    </p>
                    <button
                        v-for="row in results"
                        :key="row.applicationId"
                        type="button"
                        class="flex cursor-pointer flex-col rounded-lg border border-border bg-card px-3 py-2.5 text-left transition-colors hover:bg-muted"
                        @click.stop="navigateToApplicant(row.applicationId)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm font-semibold text-foreground">{{ row.studentName }}</span>
                            <span class="shrink-0 rounded-full bg-primary/15 px-2 py-0.5 text-[10px] font-semibold uppercase text-primary">
                                {{ row.level }}
                            </span>
                        </div>
                        <span class="mt-0.5 text-xs text-muted-foreground">{{ row.course }}</span>
                        <span class="text-xs text-muted-foreground">{{ row.department }}</span>
                        <span class="mt-1 font-mono text-[10px] text-muted-foreground">{{ row.applicationTrackingNumber }}</span>
                    </button>
                </div>
            </div>

            <div class="border-t px-5 py-3">
                <button
                    type="button"
                    class="w-full cursor-pointer rounded-lg border border-border px-3 py-2 text-sm font-medium hover:bg-muted"
                    @click="close"
                >
                    {{ $t('trans.close') }}
                </button>
            </div>
        </SheetContent>
    </Sheet>
</template>
