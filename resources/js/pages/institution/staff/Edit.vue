<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import Form from '@/pages/institution/staff/partials/Form.vue';
import { Staff } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount } from 'vue';
import { useStaffCreateFormStore } from '@/store/institution/useStaffStore';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
    staff: Staff;
}

const props = defineProps<Props>();
const { department, staff } = props;
const institutionDepartmentId = department.id?.toString() ?? '';
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department, href: route('institution-departments.show', institutionDepartmentId) },
    { title: staff?.relationships?.user?.attributes?.name ?? '' },
    { transKey: 'edit_staff' },
];

onBeforeUnmount(() => {
    const store = useStaffCreateFormStore();
    store.$reset();
    store.$dispose();
})
</script>

<template>
    <Head :title="$t('trans.edit_staff')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <Form :department="department" :staff="staff" />
    </PageContainer>
</template>
