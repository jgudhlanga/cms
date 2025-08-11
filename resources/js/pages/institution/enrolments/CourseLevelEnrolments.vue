<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { DepartmentLevel } from '@/types/department-meta-data';
import { Enrolment } from '@/types/enrolments';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    department: InstitutionDepartment;
    level: DepartmentLevel;
    enrolments: Enrolment[];
}

const props = defineProps<Props>();

const { department, level } = props;

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department, href: route('institution-departments.show', department?.id?.toString()) },
    { title: level.attributes.level },
    { transChoiceKey: 'enrolment' },
];
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        {{ enrolments[0] }}
    </PageContainer>
</template>
