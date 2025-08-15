<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import LinkDepartmentsToInstitution from '@/pages/institution/departments/partials/LinkDepartmentsToInstitution.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { Link } from '@/types/ui';

const { createInstitutionDepartmentColumns, openInstitutionDepartmentsModal } = useInstitutionDepartments();

interface Props {
    departments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
    institutionDepartmentIds: Array<string | undefined | null> | null;
}

defineProps<Props>();
const params = route().params;
const departmentsType = Number(params?.is_academic) == 1 ? 'academic_department' : 'non_academic_department';
const breadcrumbs: Array<Link> = [
    {
        transChoiceKey: 'institution',
        transChoiceKeyIndex: 1,
        href: route('institution.index'),
    },
    { transChoiceKey: departmentsType },
];
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="departments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :show-archived-filter="false"
            :search-url="route('institution-departments.index', { is_academic: params?.is_academic })"
            :pagination="{ ...departments.links, ...departments.meta }"
            :columns="createInstitutionDepartmentColumns()"
        >
            <template #head-right v-if="hasAbility('create:department-metadata')">
                <GenericButton
                    :icon="IconName.add"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary_outline"
                    @click="() => openInstitutionDepartmentsModal(institutionDepartmentIds)"
                    :title="$t('trans.link_department')"
                />
            </template>
        </DataTable>
        <LinkDepartmentsToInstitution />
    </PageContainer>
</template>
