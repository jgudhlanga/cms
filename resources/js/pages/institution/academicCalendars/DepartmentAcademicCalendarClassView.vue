<script setup lang="ts">
import { Head, Link as InertiaLink } from '@inertiajs/vue3';
import { UserIcon, UserRoundIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AcademicCalendar, AcademicCalendarClassDetail, ClassConfig } from '@/types/academic-calendar';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    classConfig: ClassConfig | null;
    academicCalendarClass: AcademicCalendarClassDetail;
}>();

const { department, academicCalendar, academicCalendarClass, course, level, mode, classConfig } = props;

const departmentClassesUrl = computed(() =>
    route('academic-calendars.department-classes', {
        institution_department: String(department.id),
        academic_calendar: String(academicCalendar.id),
        department_level_id: String(level.id),
        department_course_id: String(course.id),
        mode_of_study_id: String(mode.id),
        ...(classConfig?.id ? { class_config_id: String(classConfig.id) } : {}),
    }),
);

const normalizeGender = (gender: string | null | undefined): 'female' | 'male' | 'unknown' => {
    const normalized = String(gender ?? '').trim().toLowerCase();

    if (normalized.includes('female')) {
        return 'female';
    }

    if (normalized.includes('male')) {
        return 'male';
    }

    return 'unknown';
};

const sortedStudents = computed(() => {
    return [...academicCalendarClass.students].sort((a, b) => {
        const genderPriority: Record<'female' | 'male' | 'unknown', number> = {
            female: 0,
            male: 1,
            unknown: 2,
        };

        const genderSort = genderPriority[normalizeGender(a.gender)] - genderPriority[normalizeGender(b.gender)];

        if (genderSort !== 0) {
            return genderSort;
        }

        return a.name.localeCompare(b.name);
    });
});

const breadcrumbs = computed<Array<Link>>(() => {
    const departmentShowUrl = route('institution-departments.show', String(department.id));

    return [
        { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
        { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
        { title: department.attributes.departmentCode, href: departmentShowUrl },
        { title: level.attributes.level, href: departmentShowUrl },
        { title: course.attributes.course, href: departmentShowUrl },
        { title: mode.attributes.name, href: departmentShowUrl },
        { transChoiceKey: 'class', href: departmentClassesUrl.value },
        { title: academicCalendarClass.name },
    ];
});
</script>

<template>
    <Head :title="academicCalendarClass.name" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="departmentClassesUrl">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="academicCalendarClass.name">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <LabelValue :label="$tChoice('trans.student', 2)" :value="String(academicCalendarClass.studentCount)" />
                    <LabelValue :label="$t('academic_calendar.description')" :value="academicCalendarClass.description ?? '---'" />
                </div>
            </BaseCard>

            <BaseCard title="Class metadata">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <LabelValue v-for="meta in academicCalendarClass.metadata" :key="meta.key" :label="meta.label" :value="meta.value || '---'" />
                </div>
            </BaseCard>
                <table class="j-table">
                    <thead class="j-thead">
                        <tr class="j-th">
                            <th class="j-th text-left">#</th>
                            <th class="j-th text-left">{{ $t('general.name') }}</th>
                            <th class="j-th text-left">{{ $t('student.student_number') }}</th>
                            <th class="j-th text-center">{{ $tChoice('trans.gender', 1) }}</th>
                            <th class="j-th text-right">{{ $tChoice('trans.action', 2) }}</th>
                        </tr>
                    </thead>
                    <tbody class="j-tbody">
                        <tr class="j-tr" v-for="(student, index) in sortedStudents" :key="student.studentProgramId">
                            <td class="j-td">{{ index + 1 }}</td>
                            <td class="j-td">{{ student.name }}</td>
                            <td class="j-td">{{ student.studentNumber ?? '---' }}</td>
                            <td class="j-td text-center">
                                <span class="inline-flex items-center gap-1">
                                    <UserRoundIcon v-if="normalizeGender(student.gender) === 'female'" class="h-4 w-4 text-pink-600" />
                                    <UserIcon v-else-if="normalizeGender(student.gender) === 'male'" class="h-4 w-4 text-blue-600" />
                                    <UserIcon v-else class="h-4 w-4 text-gray-500" />
                                </span>
                            </td>
                            <td class="j-td text-right">
                                <InertiaLink :href="route('students.profile', String(student.studentId))">
                                    <BaseButton :size="ButtonSize.xs" :variant="ColorVariant.success" :title="$tChoice('students.profile', 1)" classes="rounded-full" />
                                </InertiaLink>
                            </td>
                        </tr>
                    </tbody>
                </table>
        </div>
    </PageContainer>
</template>
