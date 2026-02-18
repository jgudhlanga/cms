<script setup lang="ts">
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { useUtils } from '@/composables/core/useUtils';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { DepartmentCourseClassCount } from '@/types/academic-calendar';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { trans_choice } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = String(department?.id) ?? '';
const { getData, isLoading } = useServerSide();
const academicCalendar = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const { isLoading: academicCalendarLoading, listAcademicCalendars, academicCalendars } = useAcademicCalendars();
const { isLoading: modesOfStudyLoading, listModesOfStudy, modesOfStudy } = useModeOfStudy();
const { navigateTo } = useUtils();

const classStates = ref<DepartmentCourseClassCount[] | []>([]);

onMounted(async () => {
    await listAcademicCalendars();
    await listModesOfStudy();
    const academicCalendarOption = academicCalendars.value?.[0] ?? null;
    const modeOption = modesOfStudy.value?.filter((row: ModeOfStudy) => row.attributes.name.toLowerCase() == 'full time')[0] ?? null;
    academicCalendar.value = academicCalendarOption
        ? {
              value: Number(academicCalendarOption.id),
              label: `${academicCalendarOption.attributes.name} ${academicCalendarOption.attributes.calendarYear}`,
          }
        : null;
    modeOfStudy.value = modeOption ? { value: Number(modeOption.id), label: modeOption.attributes.name } : null;

    await loadClassConfigs();
    /*classStates.value = [
        {
            institutionDepartmentId: institutionDepartmentId,
            departmentCourseId: '1',
            courseName: 'Information Technology',
            levels: [
                { departmentLevelId: '1', levelName: 'NC', studentsPerClass: 20, totalFinalClass: 100 },
                { departmentLevelId: '2', levelName: 'ND', studentsPerClass: 20, totalFinalClass: 100 },
                { departmentLevelId: '3', levelName: 'HND', studentsPerClass: 20, totalFinalClass: 100 },
            ],
        },
        {
            institutionDepartmentId: institutionDepartmentId,
            departmentCourseId: '2',
            courseName: 'Professional Computing & Information Systems',
            levels: [
                { departmentLevelId: '4', levelName: 'ABMA Level 3', studentsPerClass: 20, totalFinalClass: 60 },
                { departmentLevelId: '5', levelName: 'ABMA Level 4', studentsPerClass: 20, totalFinalClass: 40 },
                { departmentLevelId: '6', levelName: 'ABMA Level 5', studentsPerClass: 20, totalFinalClass: 20 },
            ],
        },
        {
            institutionDepartmentId: institutionDepartmentId,
            departmentCourseId: '2',
            courseName: 'Professional Computer Engineering',
            levels: [
                { departmentLevelId: '4', levelName: 'ABMA Level 3', studentsPerClass: 20, totalFinalClass: 60 },
                { departmentLevelId: '5', levelName: 'ABMA Level 4', studentsPerClass: 20, totalFinalClass: 40 },
                { departmentLevelId: '6', levelName: 'ABMA Level 5', studentsPerClass: 20, totalFinalClass: 20 },
            ],
        },
    ];*/
});

const loadClassConfigs = async () => {
    classStates.value = await getData(
        `api/v1/departments/${institutionDepartmentId}/academic-calendars?academic_calendar=${String(academicCalendar.value?.value)}&mode_of_study_id=${String(modeOfStudy.value?.value)}`,
        () => trans_choice('trans.enrolment', 2),
    );
};
const handleSelectionChange = async () => {
    await loadClassConfigs();
};

const calculateClasses = (totalFinalClass: number, studentsPerClass: number) => {
    if (totalFinalClass === 0 || studentsPerClass === 0) {
        return 0;
    }
    return totalFinalClass / studentsPerClass;
};
</script>

<template>
    <div class="my-8 flex flex-col space-y-4">
        <div class="mb-10 flex w-full justify-between space-x-4">
            <AcademicCalendarClassFilters
                v-model:academic-calendar-model="academicCalendar"
                v-model:modeOfStudyModel="modeOfStudy"
                :academic-calendars="academicCalendars ?? []"
                :modes-of-study="modesOfStudy ?? []"
                :handle-filter-change="handleSelectionChange"
            />
        </div>
        <DataLoadingSpinner v-if="isLoading || academicCalendarLoading || modesOfStudyLoading" />
        <div class="flex flex-col space-y-10" v-else>
            <template v-if="classStates && classStates.length > 0">
                <table class="j-table">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">{{ $tChoice('trans.level', 1) }}</th>
                            <th class="j-th text-center">{{ $tChoice('academic_calendar.class_unit_size', 1) }}</th>
                            <th class="j-th text-center">{{ $tChoice('trans.class', 2) }}</th>
                            <th class="j-th w-8 text-center">{{ $tChoice('academic_calendar.setup', 2) }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <template v-for="stats in classStates" :key="stats.departmentCourseId">
                            <tr class="j-tr">
                                <td class="j-td text-left" colspan="4">
                                    <HeadingSmall :title="stats.courseName" />
                                </td>
                            </tr>
                            <tr class="j-tr" v-for="(level, index) in stats.levels" :key="index">
                                <td class="j-td text-left">{{ level.levelName }}</td>
                                <td class="j-td text-center">{{ level.studentsPerClass }}</td>
                                <td class="j-td text-center">
                                    {{ calculateClasses(Number(level.totalFinalClass), Number(level.studentsPerClass)) }}
                                </td>
                                <td class="j-td text-center">
                                    <BaseButton
                                        :size="ButtonSize.xs"
                                        :variant="ColorVariant.primary_outline"
                                        classes="rounded-full"
                                        :title="$t('academic_calendar.config')"
                                        @click="
                                            () =>
                                                navigateTo(
                                                    route('academic-calendars.classes-config', {
                                                        institution_department: institutionDepartmentId,
                                                        academic_calendar: String(academicCalendar?.value) ?? '',
                                                        department_level: String(level.departmentLevelId),
                                                        department_course: stats.departmentCourseId,
                                                        mode_of_study: String(modeOfStudy?.value),
                                                    }),
                                                )
                                        "
                                    />
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
