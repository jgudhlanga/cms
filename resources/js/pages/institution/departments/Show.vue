<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
import { useInstitution } from '@/composables/institution/useInstitution';
import LinkCoursesToDepartment from '@/pages/institution/departments/partials/LinkCoursesToDepartment.vue';
import LinkLevelsToDepartment from '@/pages/institution/departments/partials/LinkLevelsToDepartment.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import {DepartmentMetaData} from '@/types/department-meta-data';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department },
];

const { departmentTabs, loadDepartmentMetaData, isLoading, departmentMetaData } = useInstitution();

onMounted(async () => {
    await loadDepartmentMetaData(props.department.id?.toString() ?? '');
});

const defaultValue = ref('about_us');
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <template v-if="isLoading">
            <TableLoading />
        </template>
        <template v-else>
            <BaseTabs :tabs="departmentTabs(departmentMetaData as DepartmentMetaData)" :default-value="defaultValue" />
        </template>
        <LinkLevelsToDepartment :institution-department-id="department.id?.toString() ?? ''" />
        <LinkCoursesToDepartment :institution-department-id="department.id?.toString() ?? ''" />
    </PageContainer>
</template>
