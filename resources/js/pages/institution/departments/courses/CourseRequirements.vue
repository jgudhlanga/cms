<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { getIdParams } from '@/lib/utils';
import { AuthObject } from '@/types/data-pagination';
import { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    institutionDepartment: InstitutionDepartment;
    levels: DepartmentLevel[];
    departmentCourse: DepartmentCourse;
    requirements: any;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const { institutionDepartment, departmentCourse } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    {
        title: institutionDepartment.attributes.department,
        href: route('institution-departments.show', getIdParams(institutionDepartment.id?.toString() ?? '')),
    },
    { title: departmentCourse.attributes.course },
    { title: 'Course Requirements' },
];
</script>

<template>
    <Head :title="$t('trans.level_requirements')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent="() => {}" class="flex flex-col">
            <div class="flex flex-col"></div>
        </form>
    </PageContainer>
</template>
