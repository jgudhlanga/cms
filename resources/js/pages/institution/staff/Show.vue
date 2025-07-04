<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { Staff } from '@/types/staff';

interface Props {
    department: InstitutionDepartment;
    staff: Staff;
    message?: string;
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
        {{ message }}
    </PageContainer>
</template>
