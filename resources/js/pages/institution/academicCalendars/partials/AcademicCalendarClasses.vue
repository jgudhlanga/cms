<script setup lang="ts">
import DepartmentClassModeBrowser from '@/components/academicCalendars/DepartmentClassModeBrowser.vue';
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import HttpService from '@/services/http.service';
import { InstitutionDepartment } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id) ?? '';
const ALL_PHASES_VALUE = 'all';
const allPhasesOption = (): SelectOption => ({
    value: ALL_PHASES_VALUE,
    label: trans('academic_calendar.all_programme_semesters'),
});
const isAllPhases = (option: SelectOption | null): boolean =>
    option == null || String(option.value).trim() === '' || String(option.value) === ALL_PHASES_VALUE;

const academicYear = ref<SelectOption | null>(null);
const programmeSemester = ref<SelectOption | null>(allPhasesOption());
const openModeId = ref('');
const { listSemesters } = useAcademicCalendars();

const isYearOptionsLoading = ref(false);
const semesters = ref<SelectOption[]>([]);
const programmeSemesters = ref<SelectOption[]>([allPhasesOption()]);

const getSelectedAcademicYearFromUrl = (): SelectOption | null => {
    const raw = new URL(window.location.href).searchParams.get('academic_year');
    if (!raw) {
        return null;
    }
    const match = semesters.value.find((o) => String(o.value) === raw) ?? null;
    if (!match) {
        return { value: raw, label: raw };
    }
    return match;
};

const getSelectedProgrammeSemesterFromUrl = (): SelectOption | null => {
    const raw = new URL(window.location.href).searchParams.get('semester_id');
    if (!raw) {
        return null;
    }

    return programmeSemesters.value.find((o) => String(o.value) === raw) ?? null;
};

const loadProgrammeSemesters = async (): Promise<void> => {
    try {
        const levelsDocument = await HttpService.get(
            route('v1.department-metadata.levels', { institution_department: institutionDepartmentId }),
        );
        const levelRows = Array.isArray(levelsDocument?.levels)
            ? levelsDocument.levels
            : (levelsDocument?.levels?.data ?? []);
        const calendarTypes = [
            ...new Set(
                (levelRows as Array<{ attributes?: { calendarType?: string } }>)
                    .map((row) => String(row.attributes?.calendarType ?? '').trim())
                    .filter((type) => type === 'semester' || type === 'term' || type === 'abma'),
            ),
        ];

        const query = new URLSearchParams({ page_size: 'all' });
        if (calendarTypes.length > 0) {
            query.set('calendar_type', calendarTypes.join(','));
        }

        const body = await HttpService.get(`${route('v1.semesters.index')}?${query.toString()}`);
        const rows = (body?.data ?? []) as Array<{ id: number | string; attributes?: { name?: string } }>;
        programmeSemesters.value = [
            allPhasesOption(),
            ...rows.map((row) => ({
                value: String(row.id),
                label: row.attributes?.name ?? String(row.id),
            })),
        ];
    } catch {
        programmeSemesters.value = [allPhasesOption()];
    }
};

const syncFiltersToUrl = (): void => {
    const currentUrl = new URL(window.location.href);

    currentUrl.searchParams.set('academic_year', String(academicYear.value?.value ?? ''));
    currentUrl.searchParams.delete('academic_calendar_type');
    if (!isAllPhases(programmeSemester.value)) {
        currentUrl.searchParams.set('semester_id', String(programmeSemester.value?.value ?? '').trim());
    } else {
        currentUrl.searchParams.delete('semester_id');
    }

    if (openModeId.value) {
        currentUrl.searchParams.set('mode_of_study_id', openModeId.value);
    } else {
        currentUrl.searchParams.delete('mode_of_study_id');
    }

    window.history.replaceState({}, '', currentUrl.toString());
};

onMounted(async () => {
    isYearOptionsLoading.value = true;
    try {
        semesters.value = await listSemesters();
        await loadProgrammeSemesters();

        const currentCalendarYear = String(new Date().getFullYear());
        const defaultYearOption =
            semesters.value.find((o) => String(o.value) === currentCalendarYear) ?? semesters.value[0] ?? null;

        academicYear.value = getSelectedAcademicYearFromUrl() ?? defaultYearOption;
        programmeSemester.value =
            getSelectedProgrammeSemesterFromUrl() ?? programmeSemesters.value[0] ?? allPhasesOption();
        openModeId.value = new URL(window.location.href).searchParams.get('mode_of_study_id') ?? '';
        syncFiltersToUrl();
    } finally {
        isYearOptionsLoading.value = false;
    }
});

const handleSelectionChange = (): void => {
    syncFiltersToUrl();
};

const onModeChange = (modeId: string): void => {
    openModeId.value = modeId;
    syncFiltersToUrl();
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <AcademicCalendarClassFilters
            v-model:academicYearModel="academicYear"
            v-model:programmeSemesterModel="programmeSemester"
            :semesters="semesters"
            :programme-semesters="programmeSemesters"
            :handle-filter-change="handleSelectionChange"
        >
            <template #end>
                <div id="department-class-mode-totals" class="min-w-0" />
            </template>
        </AcademicCalendarClassFilters>
        <DataLoadingSpinner v-if="isYearOptionsLoading || !academicYear" />
        <DepartmentClassModeBrowser
            v-else-if="academicYear"
            :department-id="institutionDepartmentId"
            :academic-year="String(academicYear.value)"
            :semester-id="isAllPhases(programmeSemester) ? null : String(programmeSemester?.value ?? '').trim() || null"
            :initial-mode-of-study-id="openModeId || null"
            totals-target="department-class-mode-totals"
            @update:mode-of-study-id="onModeChange"
        />
    </div>
</template>
