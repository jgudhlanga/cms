<script setup lang="ts">
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import HttpService from '@/services/http.service';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { AcademicClassConfigPayload, ClassConfigPeriodOption, ClassLevelConfigSummary, ClassLevelSummary, DepartmentCourseClassCount } from '@/types/academic-calendar';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { onMounted, ref, watch } from 'vue';
import ClassConfigCreateControl from './ClassConfigCreateControl.vue';

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
const programmeSemester = ref<SelectOption | null>(null);
const { listSemesters } = useAcademicCalendars();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();

const isYearOptionsLoading = ref(false);
const semesters = ref<SelectOption[]>([]);
const programmeSemesters = ref<SelectOption[]>([]);
const classStates = ref<DepartmentCourseClassCount[] | []>([]);

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
    const match = programmeSemesters.value.find((o) => String(o.value) === raw) ?? null;
    if (!match) {
        return { value: raw, label: raw };
    }
    return match;
};

const loadProgrammeSemesters = async (): Promise<void> => {
    try {
        const body = await HttpService.get(`${route('v1.semesters.index')}?page_size=all`);
        const rows = (body?.data ?? []) as Array<{ id: number | string; attributes?: { name?: string } }>;
        programmeSemesters.value = [
            { value: '', label: trans('academic_calendar.all_programme_semesters') },
            ...rows.map((row) => ({
                value: String(row.id),
                label: row.attributes?.name ?? String(row.id),
            })),
        ];
    } catch {
        programmeSemesters.value = [{ value: '', label: trans('academic_calendar.all_programme_semesters') }];
    }
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
    const selectedSemesterId = String(programmeSemester.value?.value ?? '').trim();
    if (selectedSemesterId !== '') {
        currentUrl.searchParams.set('semester_id', selectedSemesterId);
    } else {
        currentUrl.searchParams.delete('semester_id');
    }

    window.history.replaceState({}, '', currentUrl.toString());
};

onMounted(async () => {
    isYearOptionsLoading.value = true;
    try {
        semesters.value = await listSemesters();
    } finally {
        isYearOptionsLoading.value = false;
    }
    await listModesOfStudy();
    await loadProgrammeSemesters();

    const currentCalendarYear = String(new Date().getFullYear());
    const defaultYearOption =
        semesters.value.find((o) => String(o.value) === currentCalendarYear) ?? semesters.value[0] ?? null;

    const defaultModeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() === 'full time')[0] ?? null;

    academicYear.value = getSelectedAcademicYearFromUrl() ?? defaultYearOption;
    modeOfStudy.value = getSelectedModeOfStudyFromUrl() ?? (defaultModeOption ? { value: Number(defaultModeOption.id), label: defaultModeOption.attributes.name } : null);
    programmeSemester.value = getSelectedProgrammeSemesterFromUrl() ?? programmeSemesters.value[0] ?? null;
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
                ...(String(programmeSemester.value?.value ?? '').trim() !== ''
                    ? { semester_id: String(programmeSemester.value?.value) }
                    : {}),
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

const getDisplayedTotalFinalList = (totalFinalList: string | number | null): number => {
    return Number(totalFinalList ?? 0);
};

const getViewClassesLabel = (config: ClassLevelConfigSummary): string =>
    trans('academic_calendar.view_classes', { count: Number(config.classesCount ?? 0) });

const getViewClassesMenuLabel = (config: ClassLevelConfigSummary): string =>
    trans('academic_calendar.view_classes_for_period', {
        period: config.semester ?? '',
        count: Number(config.classesCount ?? 0),
    });

const getClassConfigTagTitle = (config: ClassLevelConfigSummary): string => {
    return `${trans_choice('academic_calendar.class_unit_size', 1)}: ${Number(config.studentsPerClass ?? 0)} - ${config.semester ?? ''}`;
};

const configsForDisplay = (level: ClassLevelSummary): ClassLevelConfigSummary[] => {
    const configs = [...(level.configs ?? [])];
    const currentId = level.currentSemesterId != null ? String(level.currentSemesterId) : null;
    if (currentId === null) {
        return configs;
    }

    return configs.sort((left, right) => {
        const leftCurrent = String(left.semesterId ?? '') === currentId ? 0 : 1;
        const rightCurrent = String(right.semesterId ?? '') === currentId ? 0 : 1;

        return leftCurrent - rightCurrent;
    });
};

const primaryConfig = (level: ClassLevelSummary): ClassLevelConfigSummary | undefined => configsForDisplay(level)[0];

const editPrimaryConfig = (stats: DepartmentCourseClassCount, level: ClassLevelSummary): void => {
    const config = primaryConfig(level);
    if (config == null) {
        return;
    }

    openEditConfigModal(stats, level, config);
};

const primaryConfigTitle = (level: ClassLevelSummary): string => {
    const config = primaryConfig(level);

    return config == null ? '' : getClassConfigTagTitle(config);
};

const primaryViewClassesLabel = (level: ClassLevelSummary): string => {
    const config = primaryConfig(level);

    return config == null ? '' : getViewClassesLabel(config);
};

const primaryClassesHref = (stats: DepartmentCourseClassCount, level: ClassLevelSummary): string => {
    const config = primaryConfig(level);
    if (config == null) {
        return '#';
    }

    return classesHref(stats, level, config);
};

const classesHref = (stats: DepartmentCourseClassCount, level: ClassLevelSummary, config: ClassLevelConfigSummary): string =>
    route('academic-calendars.department-classes', {
        institution_department: institutionDepartmentId,
        calendar_year: String(academicYear.value?.value ?? ''),
        mode_of_study_id: String(modeOfStudy.value?.value),
        department_course_id: stats.departmentCourseId,
        department_level_id: String(level.departmentLevelId),
        class_config_id: String(config.classConfigId),
        ...(config.semesterId != null ? { semester_id: String(config.semesterId) } : {}),
    });

const codesLabel = (level: ClassLevelSummary): string => {
    const codes = (level.configs ?? []).flatMap((config) => config.courseSyllabusCodes ?? []).filter((code) => String(code).trim() !== '');
    if (codes.length === 0) {
        return '---';
    }
    return [...new Set(codes.map((code) => String(code)))].join(', ');
};

const periodOptionsForModal = (level: ClassLevelSummary, config?: ClassLevelConfigSummary): ClassConfigPeriodOption[] => {
    const remaining = level.remainingPeriods ?? [];
    if (config == null || config.semesterId == null) {
        return remaining;
    }

    const selectedId = String(config.semesterId);
    if (remaining.some((period) => String(period.id) === selectedId)) {
        return remaining;
    }

    return [
        {
            id: config.semesterId,
            name: config.semester ?? '',
            isCurrent: String(level.currentSemesterId ?? '') === selectedId,
        },
        ...remaining,
    ];
};

const showConfigModal = (payload: AcademicClassConfigPayload) => {
    openModal({ name: APP_MODULE_KEYS.student_per_class, edit: payload });
};

const openCreateConfigModal = (stats: DepartmentCourseClassCount, level: ClassLevelSummary, period: ClassConfigPeriodOption) => {
    showConfigModal({
        academic_calendar_id: String(resolvedAcademicCalendarId.value ?? ''),
        department_level_id: String(level.departmentLevelId ?? ''),
        department_course_id: String(stats.departmentCourseId ?? ''),
        mode_of_study_id: String(modeOfStudy.value?.value ?? ''),
        students_per_class: null,
        calendarType: level.calendarType ?? 'semester',
        semester_id: period.id,
        semester: period.name,
        class_config_id: null,
        named_classes_count: 0,
        course_syllabus_ids: [],
        remaining_periods: level.remainingPeriods ?? [],
    });
};

const openEditConfigModal = (stats: DepartmentCourseClassCount, level: ClassLevelSummary, config: ClassLevelConfigSummary) => {
    showConfigModal({
        academic_calendar_id: String(resolvedAcademicCalendarId.value ?? ''),
        department_level_id: String(level.departmentLevelId ?? ''),
        department_course_id: String(stats.departmentCourseId ?? ''),
        mode_of_study_id: String(modeOfStudy.value?.value ?? ''),
        students_per_class: String(config.studentsPerClass ?? ''),
        calendarType: level.calendarType ?? 'semester',
        semester_id: config.semesterId ?? null,
        semester: config.semester,
        class_config_id: config.classConfigId,
        named_classes_count: Number(config.classesCount ?? 0),
        course_syllabus_ids: (config.courseSyllabusIds ?? []).map((id) => String(id)),
        courseSyllabusCodes: config.courseSyllabusCodes,
        remaining_periods: periodOptionsForModal(level, config),
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <AcademicCalendarClassFilters
            v-model:academicYearModel="academicYear"
            v-model:modeOfStudyModel="modeOfStudy"
            v-model:programmeSemesterModel="programmeSemester"
            :semesters="semesters"
            :programme-semesters="programmeSemesters"
            :modes-of-study="modesOfStudy ?? []"
            :handle-filter-change="handleSelectionChange"
        />
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
                            <tr class="j-tr" v-for="level in stats.levels" :key="String(level.departmentLevelId)">
                                <td class="j-td text-left">{{ level.levelName }}</td>
                                <td class="j-td text-center">{{ getDisplayedTotalFinalList(level.totalFinalList) }} </td>
                                <td class="j-td text-center">
                                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                                        <template v-if="(level.configs ?? []).length === 1 && primaryConfig(level)">
                                            <button
                                                type="button"
                                                class="text-primary decoration-persian-200 cursor-pointer underline-offset-4 transition-colors duration-300 ease-out hover:text-accent-foreground"
                                                @click="editPrimaryConfig(stats, level)"
                                            >
                                                <BaseTag :title="primaryConfigTitle(level)" :variant="ColorVariant.info" />
                                            </button>
                                        </template>
                                        <DropdownMenu v-else-if="(level.configs ?? []).length > 1 && primaryConfig(level)">
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 text-primary decoration-persian-200 cursor-pointer underline-offset-4 transition-colors duration-300 ease-out hover:text-accent-foreground"
                                                >
                                                    <BaseTag :title="primaryConfigTitle(level)" :variant="ColorVariant.info" />
                                                    <BaseIcon :name="IconName.chevron_down" class="h-3 w-3 text-current" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuGroup>
                                                    <DropdownMenuItem
                                                        v-for="config in configsForDisplay(level)"
                                                        :key="String(config.classConfigId)"
                                                        @select="openEditConfigModal(stats, level, config)"
                                                    >
                                                        {{ getClassConfigTagTitle(config) }}
                                                    </DropdownMenuItem>
                                                </DropdownMenuGroup>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <ClassConfigCreateControl
                                            v-if="(level.remainingPeriods ?? []).length > 0"
                                            :remaining-periods="level.remainingPeriods ?? []"
                                            @create="(period) => openCreateConfigModal(stats, level, period)"
                                        />
                                    </div>
                                </td>
                                <td class="j-td text-center">{{ codesLabel(level) }}</td>
                                <td class="j-td text-center">
                                    <div v-if="(level.configs ?? []).length === 1 && primaryConfig(level)" class="flex flex-wrap items-center justify-center gap-1.5">
                                        <InertiaLink :href="primaryClassesHref(stats, level)">
                                            <BaseButton
                                                :title="primaryViewClassesLabel(level)"
                                                classes="rounded-full"
                                                :size="ButtonSize.xs"
                                                :variant="ColorVariant.success_outline"
                                            />
                                        </InertiaLink>
                                    </div>
                                    <DropdownMenu v-else-if="(level.configs ?? []).length > 1 && primaryConfig(level)">
                                        <DropdownMenuTrigger as-child>
                                            <BaseButton
                                                :title="primaryViewClassesLabel(level)"
                                                classes="rounded-full"
                                                :size="ButtonSize.xs"
                                                :variant="ColorVariant.success_outline"
                                            >
                                                <BaseIcon :name="IconName.chevron_down" class="ml-1 h-3 w-3 text-current" />
                                            </BaseButton>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuGroup>
                                                <DropdownMenuItem v-for="config in configsForDisplay(level)" :key="`classes-${config.classConfigId}`" as-child>
                                                    <InertiaLink :href="classesHref(stats, level, config)" class="flex w-full items-center">
                                                        {{ getViewClassesMenuLabel(config) }}
                                                    </InertiaLink>
                                                </DropdownMenuItem>
                                            </DropdownMenuGroup>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
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
