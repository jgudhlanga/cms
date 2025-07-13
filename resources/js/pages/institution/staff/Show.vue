<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { Staff } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';

interface Props {
    department: InstitutionDepartment;
    staff: Staff;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department, staff } = props;
const institutionDepartmentId = department.id?.toString() ?? '';
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department, href: route('institution-departments.show', institutionDepartmentId) },
    { title: staff.relationships?.user?.attributes?.name },
    { transChoiceKey: 'profile', transChoiceKeyIndex: 1 },
];
</script>

<template>
    <Head :title="`${$t('trans.staff')} ${$tChoice('trans.profile', 1)}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <ComingSoonAnimated/>
    </PageContainer>
</template>
