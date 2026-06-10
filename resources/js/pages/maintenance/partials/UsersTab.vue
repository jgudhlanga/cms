<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useMaintenanceUserSelection } from '@/composables/maintenance/useMaintenanceUserSelection';
import { useMaintenanceUsers } from '@/composables/maintenance/useMaintenanceUsers';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { DataListProps } from '@/types/data-pagination';
import type {
    MaintenanceApplicationStatusFilter,
    MaintenanceUsersFiltersState,
    NonEnrolledStudentUser,
} from '@/types/maintenance-users';
import type { SelectOption } from '@/types/utils';
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

const applicationStatusOptions = computed<SelectOption[]>(() => [
    { value: 'all', label: trans('trans.maintenance_users_filter_all_statuses') },
    { value: 'no_profile', label: trans('trans.maintenance_users_status_no_profile') },
    { value: 'no_programmes', label: trans('trans.maintenance_users_status_no_programmes') },
    { value: 'review', label: trans('trans.maintenance_users_status_review') },
    { value: 'waitlisted', label: trans('trans.maintenance_users_status_waitlisted') },
    { value: 'verified', label: trans('trans.maintenance_users_status_verified') },
    { value: 'unknown', label: trans('trans.maintenance_users_status_unknown') },
]);

const applicationStatusSelection = ref<SelectOption | null>({
    value: 'all',
    label: 'All application statuses',
});

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

const onApplicationStatusChange = async (selection: SelectOption | null) => {
    applicationStatusSelection.value = selection;

    const status = selection?.value;

    const nextFilters: MaintenanceUsersFiltersState = {
        ...filters.value,
        applicationStatus:
            status && status !== 'all' ? (status as MaintenanceApplicationStatusFilter) : undefined,
    };

    await loadUsers(nextFilters);
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
            <template #head-left>
                <BaseCombobox
                    :model-value="applicationStatusSelection"
                    :options="applicationStatusOptions"
                    :placeholder="trans('trans.maintenance_users_filter_all_statuses')"
                    class="min-w-56 rounded-full"
                    @update:model-value="onApplicationStatusChange"
                />
            </template>
            <template #head-right>
                <div v-if="selectedCount > 0" class="flex items-center ml-2 gap-3">
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
