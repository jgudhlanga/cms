<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { computed, onMounted } from 'vue';
import TableLoading from '@/components/core/loader/TableLoading.vue';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const { createDepartmentLevelColumns, openDepartmentLevelsModal, isLoading, departmentLevelsMetadata, loadDepartmentLevelsMetadata } = useDepartmentLevels();
const allowed = hasAbility('create:department-metadata');
const departmentLevelsIds = computed(() => departmentLevelsMetadata.value?.departmentLevelsIds ?? []);
const departmentLevels = computed(() => departmentLevelsMetadata.value?.levels ?? []);

onMounted(() => {
    loadDepartmentLevelsMetadata(props.department?.id?.toString() ?? '');
})
</script>

<template>
    <TableLoading v-if="isLoading"/>
    <DataTable v-else :data="departmentLevels" :columns="createDepartmentLevelColumns()" :show-archived-filter="false">
        <template #head-right v-if="allowed">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary_outline"
                @click="() => openDepartmentLevelsModal(departmentLevelsIds)"
                :title="$t('trans.link_levels')"
            />
        </template>
    </DataTable>
</template>
