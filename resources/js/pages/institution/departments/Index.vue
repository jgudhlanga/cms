<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { GenericButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import LinkDepartmentsToInstitution from '@/pages/institution/departments/partials/LinkDepartmentsToInstitution.vue';
import EditDepartmentDivision from '@/pages/institution/departments/partials/EditDepartmentDivision.vue';
import InstitutionDepartmentExpandedDetail from '@/pages/institution/departments/partials/InstitutionDepartmentExpandedDetail.vue';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { Link } from '@/types/ui';

const {
    createInstitutionDepartmentColumns,
    departmentShowUrl,
    institutionDepartmentRowKey,
    openDepartmentDivisionModal,
    openInstitutionDepartmentsModal,
} = useInstitutionDepartments();

const isAcademic = Number(route().params?.is_academic) === 1;
const expandedRowId = ref<string | null>(null);

const toggleRow = (id: string) => {
    expandedRowId.value = expandedRowId.value === id ? null : id;
};

interface Props {
    departments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
    institutionDepartmentIds: Array<string | undefined | null> | null;
    divisionOptions: Array<{ id: number | string; name: string | null }>;
}

const props = defineProps<Props>();
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

const columns = computed(() =>
    createInstitutionDepartmentColumns({
        expandedRowId,
        onToggleExpand: toggleRow,
    }),
);

const handleDepartmentRowClick = (row: InstitutionDepartment) => {
    toggleRow(institutionDepartmentRowKey(row));
};

const canViewDepartment = computed(() => hasAbility('view:department-metadata'));
const canEditDepartment = computed(() => hasAbility('update:department-metadata'));
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution.index')">
        <DataTable
            :data="departments?.data ?? []"
            :trashed-count="trashedCount"
            :filters="filters"
            :show-archived-filter="false"
            :search-url="route('institution-departments.index', { is_academic: params?.is_academic })"
            :pagination="{ ...departments.links, ...departments.meta }"
            :columns="columns"
            :expanded-row-id="expandedRowId"
            :on-row-click="handleDepartmentRowClick"
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
            <template #expanded-row="{ row }: { row: InstitutionDepartment }">
                <InstitutionDepartmentExpandedDetail
                    :department="row"
                    :is-academic="isAcademic"
                    :can-view="canViewDepartment"
                    :can-edit="canEditDepartment"
                    :view-url="departmentShowUrl(row)"
                    @edit="openDepartmentDivisionModal(row)"
                />
            </template>
        </DataTable>
        <LinkDepartmentsToInstitution />
        <EditDepartmentDivision :division-options="divisionOptions" />
    </PageContainer>
</template>
