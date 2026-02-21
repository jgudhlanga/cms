<script setup lang="ts">
import { useAcademicCalendars } from '@/composables/academicCalendars/useAcademicCalendars';
import { useUtils } from '@/composables/core/useUtils';
import { useModeOfStudy } from '@/composables/institution/useModeOfStudy';
import { useServerSide } from '@/composables/shared/useServerSide';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { AcademicClassConfigPayload, DepartmentCourseClassCount } from '@/types/academic-calendar';
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

const showConfigModal = (payload: AcademicClassConfigPayload) => {
    openModal({ name: APP_MODULE_KEYS.student_per_class, edit: payload });
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
                                                showConfigModal({
                                                    academic_calendar_id: String(academicCalendar?.value) ?? '',
                                                    department_level_id: String(level.departmentLevelId),
                                                    department_course_id: stats.departmentCourseId,
                                                    mode_of_study_id: String(modeOfStudy?.value),
                                                    students_per_class: String(level.studentsPerClass),
                                                })
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
