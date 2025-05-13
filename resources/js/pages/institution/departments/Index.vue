<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import LinkDepartmentsToInstitution from '@/pages/institution/departments/partials/LinkDepartmentsToInstitution.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';

const { createInstitutionDepartmentColumns, breadcrumbs, openInstitutionDepartmentsModal } = useInstitutionDepartments();

interface Props {
    departments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
    institutionDepartmentIds: Array<string | undefined | null> | null;
}

const props = defineProps<Props>();
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="departments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :search-url="route('institution-departments.index')"
            :pagination="{ ...departments.links, ...departments.meta }"
            :columns="createInstitutionDepartmentColumns()"
        >
            <template #head-right v-if="can['create:department-metadata']">
                <GenericButton
                    :icon="IconName.add"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary"
                    @click="() => openInstitutionDepartmentsModal(institutionDepartmentIds)"
                    :title="$t('trans.link_department')"
                />
            </template>
        </DataTable>
        <LinkDepartmentsToInstitution />
    </PageContainer>
</template>
