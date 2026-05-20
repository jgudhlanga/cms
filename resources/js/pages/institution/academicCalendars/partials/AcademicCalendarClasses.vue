<script setup lang="ts">
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { AcademicClassConfigPayload, ClassLevelSummary, DepartmentCourseClassCount } from '@/types/academic-calendar';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';
import { ColorVariant } from '@/enums/colors';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id) ?? '';
const { getData, isLoading } = useServerSide();
const academicYear = ref<SelectOption | null>(null);
const resolvedAcademicCalendarId = ref<number | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const { listAcademicYearOptions } = useAcademicCalendars();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();

const isYearOptionsLoading = ref(false);
const academicYearOptions = ref<SelectOption[]>([]);
const classStates = ref<DepartmentCourseClassCount[] | []>([]);

const getSelectedAcademicYearFromUrl = (): SelectOption | null => {
    const raw = new URL(window.location.href).searchParams.get('academic_year');
    if (!raw) {
        return null;
    }
    const match = academicYearOptions.value.find((o) => String(o.value) === raw) ?? null;
    if (!match) {
        return { value: raw, label: raw };
    }
    return match;
};

const getSelectedModeOfStudyFromUrl = (): SelectOption | null => {
    const selectedModeOfStudyId = Number(new URL(window.location.href).searchParams.get('mode_of_study_id'));
    const selectedModeOfStudy = modesOfStudy.value?.find((row) => Number(row.id) === selectedModeOfStudyId) ?? null;

    if (!selectedModeOfStudy) {
        return null;
    }

    return {
        value: Number(selectedModeOfStudy.id),
        label: selectedModeOfStudy.attributes.name,
    };
};

const syncFiltersToUrl = (): void => {
    const currentUrl = new URL(window.location.href);

    currentUrl.searchParams.set('academic_year', String(academicYear.value?.value ?? ''));
    currentUrl.searchParams.set('mode_of_study_id', String(modeOfStudy.value?.value ?? ''));
    currentUrl.searchParams.delete('academic_calendar_type');
    currentUrl.searchParams.delete('academic_year_option_id');

    window.history.replaceState({}, '', currentUrl.toString());
};

onMounted(async () => {
    isYearOptionsLoading.value = true;
    try {
        academicYearOptions.value = await listAcademicYearOptions();
    } finally {
        isYearOptionsLoading.value = false;
    }
    await listModesOfStudy();

    const currentCalendarYear = String(new Date().getFullYear());
    const defaultYearOption =
        academicYearOptions.value.find((o) => String(o.value) === currentCalendarYear) ?? academicYearOptions.value[0] ?? null;

    const defaultModeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() === 'full time')[0] ?? null;

    academicYear.value = getSelectedAcademicYearFromUrl() ?? defaultYearOption;
    modeOfStudy.value = getSelectedModeOfStudyFromUrl() ?? (defaultModeOption ? { value: Number(defaultModeOption.id), label: defaultModeOption.attributes.name } : null);
    syncFiltersToUrl();

    await loadClassConfigs();
});

const loadClassConfigs = async () => {
    const payload = await getData(
        route(
            'v1.departments.academic-calendars',
            {
                institution_department: institutionDepartmentId,
                academic_year: String(academicYear.value?.value ?? ''),
                mode_of_study_id: String(modeOfStudy.value?.value),
            },
            false,
        ),
        () => trans_choice('trans.enrolment', 2),
    );
    if (payload && typeof payload === 'object' && 'data' in payload) {
        classStates.value = (payload as { data: DepartmentCourseClassCount[] }).data ?? [];
        const meta = (payload as { meta?: { resolvedAcademicCalendarId?: number } }).meta;
        resolvedAcademicCalendarId.value = meta?.resolvedAcademicCalendarId ?? null;
    } else {
        classStates.value = [];
        resolvedAcademicCalendarId.value = null;
    }
};

const departmentMetaStore = useDepartmentMetaStore();
const { academicClassConfigsRefreshNonce } = storeToRefs(departmentMetaStore);

watch(academicClassConfigsRefreshNonce, (next, prev) => {
    if (prev === undefined) {
        return;
    }

    if (next > prev) {
        void loadClassConfigs();
    }
});

const handleSelectionChange = async () => {
    syncFiltersToUrl();
    await loadClassConfigs();
};

const getDisplayedTotalFinalList = (totalFinalList: string | number | null, totalnClass: string | number | null): number => {
    return Number(totalFinalList ?? totalnClass ?? 0);
};

const getDisplayedStudentsPerClass = (studentsPerClass: string | number | null): number => {
    return Number(studentsPerClass ?? 0);
};

const getSuggestedClassCount = (level: ClassLevelSummary): number | null => {
    const perClass = getDisplayedStudentsPerClass(level.studentsPerClass);
    if (perClass < 1) {
        return null;
    }
    const total = getDisplayedTotalFinalList(level.totalFinalList, level.totalnClass);
    if (total < 1) {
        return null;
    }
    return Math.ceil(total / perClass);
};

const getClassesLinkLabel = (level: ClassLevelSummary): string => {
    const created = Number(level.classesCount ?? 0);
    const suggested = getSuggestedClassCount(level);
    if (suggested !== null && suggested > created) {
        return String(
            trans('academic_calendar.classes_count_with_suggested', {
                created,
                suggested,
            }),
        );
    }
    return String(created);
};

const getClassConfigTagTitle = (level: ClassLevelSummary): string => {
    const base = `${trans_choice('academic_calendar.class_unit_size', 1)}: ${getDisplayedStudentsPerClass(level.studentsPerClass)} - ${level.academicYearOption ?? 'semester'}`;
    return `${base}`;
};

const codesLabel = (level: ClassLevelSummary): string => {
    const codes = (level.courseSyllabusCodes ?? []).filter((c) => String(c).trim() !== '');
    if (codes.length === 0) {
        return '---';
    }
    return codes.map((c) => `${c}`).join(', ');
};

const showConfigModal = (payload: AcademicClassConfigPayload) => {
    openModal({ name: APP_MODULE_KEYS.student_per_class, edit: payload });
};
</script>

<template>
    <div class="my-8 flex flex-col space-y-4">
        <div class="mb-10 flex w-full justify-between space-x-4">
            <AcademicCalendarClassFilters
                v-model:academicYearModel="academicYear"
                v-model:modeOfStudyModel="modeOfStudy"
                :academic-year-options="academicYearOptions"
                :modes-of-study="modesOfStudy ?? []"
                :handle-filter-change="handleSelectionChange"
            />
        </div>
        <DataLoadingSpinner v-if="isLoading || isYearOptionsLoading || modesOfStudyLoading" />
        <div class="flex flex-col space-y-10" v-else>
            <template v-if="classStates && classStates.length > 0">
                <table class="j-table">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">{{ $tChoice('trans.level', 1) }}</th>
                            <th class="j-th text-center">{{ $tChoice('academic_calendar.confirmed_student', 2) }}</th>
                            <th class="j-th text-center">{{ $tChoice('trans.config', 1) }}</th>
                            <th class="j-th text-center">{{ $tChoice('syllabus.course_syllabus', 2) }}</th>
                            <th class="j-th text-center">{{ $tChoice('trans.class', 2) }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <template v-for="stats in classStates" :key="stats.departmentCourseId">
                            <tr class="j-tr">
                                <td class="j-td text-left" colspan="5">
                                    <HeadingSmall :title="stats.courseName" />
                                </td>
                            </tr>
                            <tr class="j-tr" v-for="(level, index) in stats.levels" :key="index">
                                <td class="j-td text-left">{{ level.levelName }}</td>
                                <td class="j-td text-center">{{ getDisplayedTotalFinalList(level.totalFinalList, level.totalnClass) }} </td>
                                <td class="j-td text-center">
                                    <button
                                        type="button"
                                        class="text-primary decoration-persian-200 cursor-pointer underline-offset-4 transition-colors duration-300 ease-out hover:text-accent-foreground"
                                        @click="
                                            () =>
                                                showConfigModal({
                                                    academic_calendar_id: String(resolvedAcademicCalendarId ?? ''),
                                                    department_level_id: String(level.departmentLevelId ?? ''),
                                                    department_course_id: String(stats.departmentCourseId ?? ''),
                                                    mode_of_study_id: String(modeOfStudy?.value ?? ''),
                                                    students_per_class: String(level.studentsPerClass ?? ''),
                                                    calendarType: level.calendarType ?? 'semester',
                                                    academic_year_option_id: level.academicYearOptionId ?? null,
                                                    course_syllabus_ids: (level.courseSyllabusIds ?? []).map((id) => String(id)),
                                                    courseSyllabusCodes: level.courseSyllabusCodes,
                                                })
                                        "
                                    >
                                    <BaseTag :title="getClassConfigTagTitle(level)" :variant="ColorVariant.info" />
                                    </button>
                                </td>
                                <td class="j-td text-center">{{ codesLabel(level) }}</td>
                                <td class="j-td text-center">
                                    <TextLink
                                        v-if="level.classConfigId !== null"
                                        :title="String(level.classesCount ?? 0)"
                                        :href="
                                            route('academic-calendars.department-classes', {
                                                institution_department: institutionDepartmentId,
                                                calendar_year: String(academicYear?.value ?? ''),
                                                mode_of_study_id: String(modeOfStudy?.value),
                                                department_course_id: stats.departmentCourseId,
                                                department_level_id: String(level.departmentLevelId),
                                                class_config_id: String(level.classConfigId),
                                            })
                                        "
                                        classes="size-4 bg-green-100 rounded-full px-2 py-1 hover:bg-green-600 text-green-600 hover:text-green-100"
                                    />
                                    <span v-else>---</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
            <BaseAlert v-else :title="$t('trans.no_data')" :description="$t('academic_calendar.academic_calendar_class_not_found')" />
        </div>
    </div>
</template>
