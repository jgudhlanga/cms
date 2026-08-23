<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import LevelCodeBadge from '@/components/core/util/LevelCodeBadge.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import ClassConfigCreateControl from '@/pages/institution/academicCalendars/partials/ClassConfigCreateControl.vue';
import {
    AcademicClassConfigPayload,
    ClassConfigPeriodOption,
    ClassLevelConfigSummary,
    ClassLevelSummary,
    DepartmentCourseClassCount,
} from '@/types/academic-calendar';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface Props {
    classStates: DepartmentCourseClassCount[];
    departmentId: string;
    academicYear: string;
    modeOfStudyId: string;
    resolvedAcademicCalendarId: number | null;
}

const props = defineProps<Props>();

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

const classesHref = (stats: DepartmentCourseClassCount, level: ClassLevelSummary, config: ClassLevelConfigSummary): string =>
    route('academic-calendars.department-classes', {
        institution_department: props.departmentId,
        calendar_year: props.academicYear,
        mode_of_study_id: props.modeOfStudyId,
        department_course_id: stats.departmentCourseId,
        department_level_id: String(level.departmentLevelId),
        class_config_id: String(config.classConfigId),
    });

const primaryClassesHref = (stats: DepartmentCourseClassCount, level: ClassLevelSummary): string => {
    const config = primaryConfig(level);
    if (config == null) {
        return '#';
    }

    return classesHref(stats, level, config);
};

const codesLabel = (level: ClassLevelSummary): string => {
    const codes = (level.configs ?? []).flatMap((config) => config.courseSyllabusCodes ?? []).filter((code) => String(code).trim() !== '');
    if (codes.length === 0) {
        return '';
    }
    return [...new Set(codes.map((code) => String(code)))].join(', ');
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
        academic_calendar_id: String(props.resolvedAcademicCalendarId ?? ''),
        department_level_id: String(level.departmentLevelId ?? ''),
        department_course_id: String(stats.departmentCourseId ?? ''),
        mode_of_study_id: props.modeOfStudyId,
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
        academic_calendar_id: String(props.resolvedAcademicCalendarId ?? ''),
        department_level_id: String(level.departmentLevelId ?? ''),
        department_course_id: String(stats.departmentCourseId ?? ''),
        mode_of_study_id: props.modeOfStudyId,
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
    <div class="flex flex-col gap-3">
        <div v-for="stats in classStates" :key="stats.departmentCourseId" class="flex flex-col gap-2">
            <h4 class="text-xs font-bold uppercase tracking-[0.12em] text-foreground/80">
                {{ stats.courseName }}
            </h4>

            <div class="flex flex-col gap-1.5">
                <div
                    v-for="level in stats.levels"
                    :key="String(level.departmentLevelId)"
                    class="flex min-h-9 flex-wrap items-center gap-x-2 gap-y-1.5 rounded-lg bg-primary/5 px-2.5 py-1.5"
                >
                    <LevelCodeBadge :label="levelBadge(level.levelName)" :title="level.levelName" />
                    <span v-if="codesLabel(level)" class="min-w-0 max-w-40 truncate text-[11px] text-muted-foreground">
                        {{ codesLabel(level) }}
                    </span>
                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                        <button
                            v-if="(level.configs ?? []).length === 1 && primaryConfig(level)"
                            type="button"
                            class="inline-flex h-6 shrink-0 items-center rounded-full bg-blue-200 px-2.5 text-xs font-medium whitespace-nowrap text-blue-600 transition-colors hover:bg-blue-600 hover:text-blue-200"
                            @click="editPrimaryConfig(stats, level)"
                        >
                            {{ primaryConfigTitle(level) }}
                        </button>
                        <DropdownMenu v-else-if="(level.configs ?? []).length > 1 && primaryConfig(level)">
                            <DropdownMenuTrigger
                                class="inline-flex h-6 shrink-0 items-center gap-1 rounded-full bg-blue-200 px-2.5 text-xs font-medium whitespace-nowrap text-blue-600 transition-colors hover:bg-blue-600 hover:text-blue-200"
                            >
                                {{ primaryConfigTitle(level) }}
                                <BaseIcon :name="IconName.chevron_down" class="h-3 w-3 shrink-0 text-current" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
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
                        <InertiaLink
                            v-if="(level.configs ?? []).length === 1 && primaryConfig(level)"
                            :href="primaryClassesHref(stats, level)"
                            class="inline-flex shrink-0"
                        >
                            <BaseButton
                                :title="primaryViewClassesLabel(level)"
                                classes="rounded-full"
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.success_outline"
                            />
                        </InertiaLink>
                        <DropdownMenu v-else-if="(level.configs ?? []).length > 1 && primaryConfig(level)">
                            <DropdownMenuTrigger as-child>
                                <BaseButton
                                    :title="primaryViewClassesLabel(level)"
                                    classes="rounded-full"
                                    :size="ButtonSize.xs"
                                    :variant="ColorVariant.success_outline"
                                >
                                    <BaseIcon :name="IconName.chevron_down" class="h-3 w-3 shrink-0 text-current" />
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
                    </div>
                    <span class="ml-auto shrink-0 text-sm font-bold tabular-nums text-foreground">
                        {{ getDisplayedTotalFinalList(level.totalFinalList) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
