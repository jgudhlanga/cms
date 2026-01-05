<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { computed, onMounted, ref, watch } from 'vue';
import TableLoading from '@/components/core/loader/TableLoading.vue';
import { InstitutionDepartment } from '@/types/institution';
import { useModalStore } from '@/store/core/useModalStore';
import { APP_MODULE_KEYS } from '@/lib/constants';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const { createDepartmentLevelColumns, openDepartmentLevelsModal, isLoading, departmentLevelsMetadata, loadDepartmentLevelsMetadata } =
    useDepartmentLevels();
const allowed = hasAbility('create:department-metadata');
const departmentLevelsIds = computed(() => departmentLevelsMetadata.value?.departmentLevelsIds ?? []);
const showOnCurrentApplicationPeriodIds = computed(() => departmentLevelsMetadata.value?.showOnCurrentApplicationPeriodIds ?? []);
const departmentLevels = computed(() => departmentLevelsMetadata.value?.levels ?? []);
const departmentId = props.department?.id?.toString() ?? '';

onMounted(() => {
    loadDepartmentLevelsMetadata(departmentId);
});

const { modals } = useModalStore();

const levelModalOpen = ref(false);

watch(
    () => modals![APP_MODULE_KEYS.department_levels],
    (isOpen) => {
        if (isOpen) {
            levelModalOpen.value = true;
        } else if (levelModalOpen.value) {
            loadDepartmentLevelsMetadata(departmentId);
            levelModalOpen.value = false;
        }
    },
);
</script>

<template>
    <TableLoading v-if="isLoading" />
    <DataTable v-else :data="departmentLevels" :columns="createDepartmentLevelColumns()" :show-archived-filter="false">
        <template #head-right v-if="allowed">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary_outline"
                @click="() => openDepartmentLevelsModal(departmentLevelsIds, showOnCurrentApplicationPeriodIds)"
                :title="$t('trans.link_levels')"
            />
        </template>
    </DataTable>
</template>
