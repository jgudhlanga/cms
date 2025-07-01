<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentLevel } from '@/types/department-meta-data';

interface Props {
    institutionDepartmentId: string;
    departmentLevels: DepartmentLevel[];
    departmentLevelsIds: Array<string | undefined | null> | null;
}

defineProps<Props>();

const { createDepartmentLevelColumns, openDepartmentLevelsModal } = useDepartmentLevels();
const allowed = hasAbility('create:department-metadata');
</script>

<template>
    <DataTable :data="departmentLevels" :columns="createDepartmentLevelColumns()" :show-archived-filter="false">
        <template #head-right v-if="allowed">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => openDepartmentLevelsModal(departmentLevelsIds)"
                :title="$t('trans.link_levels')"
            />
        </template>
    </DataTable>
</template>
