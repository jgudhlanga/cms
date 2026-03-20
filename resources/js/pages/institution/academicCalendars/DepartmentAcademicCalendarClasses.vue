<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageContainer from '@/components/core/page/PageContainer.vue';

import { AcademicCalendar } from '@/types/academic-calendar';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';

const props = defineProps<{
    department: InstitutionDepartment;
    academicCalendar: AcademicCalendar;
    course: DepartmentCourse;
    level: DepartmentLevel;
    mode: ModeOfStudy;
    auth: AuthObject;
    classConfig: ClassConfig;
    errors: object;
}>();

const { department, level, course, mode } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.departmentCode, href: route('institution-departments.show', String(department.id)) },
    { title: level.attributes.level, href: route('institution-departments.show', String(department.id)) },
    { title: course.attributes.course, href: route('institution-departments.show', String(department.id)) },
    { title: mode.attributes.name, href: route('institution-departments.show', String(department.id)) },
    { transChoiceKey: 'class' },
];

</script>

<template>
    <Head :title="$tChoice('academic_calendar.academic_calendar', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col space-y-6">
            <BaseCard :title="classConfig?.attributes?.academicCalendar">
                <div class="grid grid-cols-2 gap-4">
                    <LabelValue :label="$tChoice('academic_calendar.class_unit_size', 1)" :value="classConfig?.attributes?.studentsPerClass ?? '---'" />
                </div>
            </BaseCard>
        </div>
    </PageContainer>
</template>
