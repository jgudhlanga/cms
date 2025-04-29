<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';

const { createInstitutionDepartmentColumns, breadcrumbs, linkDepartmentsToInstitution } = useInstitutionDepartments();

interface Props {
    departments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
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
            <template #head-right v-if="can['create:institution-departments']">
                <GenericButton
                    :icon="IconName.add"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary"
                    @click="() => linkDepartmentsToInstitution()"
                    :title="$tChoice('trans.add_department', 2)"
                />
            </template>
        </DataTable>
    </PageContainer>
</template>
