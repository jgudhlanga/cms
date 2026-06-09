<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useMaintenanceUserSelection } from '@/composables/maintenance/useMaintenanceUserSelection';
import { useMaintenanceUsers } from '@/composables/maintenance/useMaintenanceUsers';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { DataListProps } from '@/types/data-pagination';
import type { MaintenanceUsersFiltersState, NonEnrolledStudentUser } from '@/types/maintenance-users';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

const {
    createMaintenanceUserColumns,
    fetchNonEnrolledStudentUsers,
    handleBulkPurgeUsers,
    isLoading,
    isPurging,
} = useMaintenanceUsers();

const users = ref<DataListProps<NonEnrolledStudentUser>>({
    data: [],
    links: {
        first: null,
        last: null,
        prev: null,
        next: null,
    },
    meta: {
        total: 0,
        per_page: 0,
        current_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
        path: null,
        links: null,
    },
});

const filters = ref<MaintenanceUsersFiltersState>({});

const visibleUsers = computed(() => users.value.data);

const isPurgeSelectable = (user: NonEnrolledStudentUser) => !user.attributes.hasStudentProfile;

const { selectedUserIds, selectAllModel, selectedCount, clearSelection, pruneSelectionToVisibleUsers } =
    useMaintenanceUserSelection(visibleUsers, isPurgeSelectable);

const reloadUsers = async () => {
    await loadUsers(filters.value);
};

const columns = computed(() => {
    void selectedUserIds.value;
    void selectAllModel.value;

    return createMaintenanceUserColumns({
        selectedUserIds,
        selectAllModel,
        onPurgeSuccess: async () => {
            clearSelection();
            await reloadUsers();
        },
    });
});

const loadUsers = async (nextFilters: MaintenanceUsersFiltersState = {}) => {
    filters.value = nextFilters;
    const response = await fetchNonEnrolledStudentUsers(nextFilters);

    if (response) {
        users.value = {
            data: (response.data ?? []) as NonEnrolledStudentUser[],
            links: response.links ?? users.value.links,
            meta: response.meta ?? users.value.meta,
        };
        pruneSelectionToVisibleUsers();
    }
};

const loadUsersFromUrl = async (url: string) => {
    const response = await fetchNonEnrolledStudentUsers(filters.value, url);

    if (response) {
        users.value = {
            data: (response.data ?? []) as NonEnrolledStudentUser[],
            links: response.links ?? users.value.links,
            meta: response.meta ?? users.value.meta,
        };
        pruneSelectionToVisibleUsers();
    }
};

const onBulkPurge = () => {
    const selectedUsers = visibleUsers.value.filter((user) => selectedUserIds.value.includes(user.id));
    handleBulkPurgeUsers(selectedUsers, async () => {
        clearSelection();
        await reloadUsers();
    });
};

onMounted(() => loadUsers());

watch(visibleUsers, () => pruneSelectionToVisibleUsers());
</script>

<template>
    <div class="space-y-4">
        <BaseAlert
            :type="TypeVariant.info"
            :description="trans('trans.maintenance_users_description')"
        />

        <DataTable
            :data="users.data"
            :filters="filters"
            :show-archived-filter="false"
            :pagination="{ ...users.links, ...users.meta }"
            :columns="columns"
            :use-api="true"
            :search-url="route('maintenance.non-enrolled-student-users')"
            :api-fetch-action="loadUsersFromUrl"
            :loading="isLoading || isPurging"
            :disable-create="true"
            :disable-import="true"
            :disable-export="true"
            :show-column-filters="false"
        >
            <template #head-right>
                <div v-if="selectedCount > 0" class="flex items-center gap-3">
                    <span class="text-sm font-medium">
                        {{ trans('trans.maintenance_users_selected_count', { count: selectedCount }) }}
                    </span>
                    <BaseButton
                        v-if="selectedCount > 0"
                        :size="ButtonSize.xs"
                        :variant="ColorVariant.danger"
                        type="button"
                        class="rounded-full"
                        :disabled="isPurging"
                        @click="onBulkPurge"
                    >
                        {{
                            trans('trans.maintenance_users_bulk_purge', {
                                count: selectedCount,
                            })
                        }}
                    </BaseButton>
                    <BaseButton
                        :size="ButtonSize.xs"
                        :variant="ColorVariant.secondary"
                        type="button"
                        class="rounded-full"
                        @click="clearSelection"
                    >
                        {{ trans('trans.clear_selection') }}
                    </BaseButton>
                </div>
            </template>
        </DataTable>
    </div>
</template>
