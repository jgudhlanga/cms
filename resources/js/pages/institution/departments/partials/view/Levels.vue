<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { PageProps } from '@/types';
import { DepartmentLevel } from '@/types/department-meta-data';
import { usePage } from '@inertiajs/vue3';

interface Props {
    institutionDepartmentId: string;
    departmentLevels: DepartmentLevel[];
}

defineProps<Props>();
const { props: pageProps } = usePage<PageProps>();
const { can } = pageProps?.auth;

const { createDepartmentLevelColumns, openDepartmentLevelsModal } = useDepartmentLevels();
</script>

<template>
    <DataTable :data="departmentLevels" :columns="createDepartmentLevelColumns()" :show-archived-filter="false">
        <template #head-right v-if="can['create:department-metadata']">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => openDepartmentLevelsModal([])"
                :title="$t('trans.link_levels')"
            />
        </template>
    </DataTable>
</template>
