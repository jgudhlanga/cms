<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject, DataListProps } from '@/types/data-pagination';
import { DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Enrolment } from '@/types/enrolments';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    enrolments: DataListProps<Enrolment>;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { department, level, enrolments } = props;
const {createCourseLevelEnrolmentColumns} = useDepartmentCourses();

const institutionDepartmentId = department?.id?.toString() ?? '';
const departmentLevelId = level?.id?.toString() ?? '';

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { transChoiceKey: 'enrolment' },
];
const firstCourseName = enrolments.data?.[0]?.attributes?.course ?? '';
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall v-if="firstCourseName" :title="firstCourseName" />
        {{ enrolments.data[0] }}
        <DataTable
            :data="enrolments.data"
            :show-archived-filter="false"
            :search-url="route('department-levels.enrolments', {institution_department: institutionDepartmentId, department_level: departmentLevelId,})"
            :pagination="{ ...enrolments.links, ...enrolments.meta }"
            :columns="createCourseLevelEnrolmentColumns()"
        />
    </PageContainer>
</template>
