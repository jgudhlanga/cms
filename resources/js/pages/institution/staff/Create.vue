<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import StaffForm from '@/pages/institution/staff/partials/StaffForm.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = department.id?.toString() ?? '';
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department, href: route('institution-departments.show', institutionDepartmentId) },
    { transKey: 'create_staff' },
];
</script>

<template>
    <Head :title="$t('trans.create_staff')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <StaffForm :department="department" />
    </PageContainer>
</template>
