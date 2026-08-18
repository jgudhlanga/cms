<script setup lang="ts">
import SettingsButton from '@/components/core/button/SettingsButton.vue';
import { GenericButton } from '@/components/core/button';
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { BaseInputWithIcon } from '@/components/core/form';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useIdCardRequests } from '@/composables/students/useIdCardRequests';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import IdCardSettingsModal from '@/pages/students/id-card-requests/partials/IdCardSettingsModal.vue';
import type { AuthObject, DataListProps } from '@/types/data-pagination';
import type { IdCardFilterOption, IdCardRequest, IdCardRequestFiltersState, StudentIdCardSettings } from '@/types/id-cards';
import type { BreadcrumbItemInterface } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    idCardSettings: StudentIdCardSettings;
    statusOptions: IdCardFilterOption[];
    reasonOptions: IdCardFilterOption[];
    canBulkPrint: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'trans.student_id' },
];

const { fetchIdCardRequests, idCardRequestColumns, isLoading } = useIdCardRequests();

const requests = ref<DataListProps<IdCardRequest>>({
    data: [],
    links: { first: null, last: null, prev: null, next: null },
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

const filters = ref<IdCardRequestFiltersState>({});
const search = ref('');
const statusSelection = ref<SelectOption | null>(null);
const reasonSelection = ref<SelectOption | null>(null);

const optionValue = (option: SelectOption | null): string | undefined => {
    const value = option?.value;

    return value === undefined || value === null || value === '' ? undefined : String(value);
};

const loadRequests = async (next: IdCardRequestFiltersState = {}) => {
    const res = await fetchIdCardRequests(next);
    if (res) {
        requests.value = res;
    }
};

const loadRequestsFromUrl = async (url: string) => {
    const res = await fetchIdCardRequests(filters.value, url);
    if (res) {
        requests.value = res;
    }
};

const applyFilters = useDebounceFn(() => {
    filters.value = {
        search: search.value.trim() || undefined,
        status: optionValue(statusSelection.value),
        reason: optionValue(reasonSelection.value),
    };
    void loadRequests(filters.value);
}, 400);

watch([search, statusSelection, reasonSelection], applyFilters);

const openSettings = () => {
    openModal({ name: APP_MODULE_KEYS.student_id_card_settings });
};

const bulkPrint = () => {
    if (! props.canBulkPrint) {
        return;
    }

    window.open(route('admin.students.id-card-requests.bulk-print'), '_blank');
};

onMounted(() => loadRequests());
</script>

<template>
    <Head :title="$tChoice('trans.student_id', 2)" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <DataTable
            :data="requests.data"
            :filters="filters"
            :pagination="{ ...requests.links, ...requests.meta }"
            :columns="idCardRequestColumns()"
            :show-archived-filter="false"
            :use-api="true"
            :use-json-api="true"
            :search-url="route('v1.json.students.student-id-card-requests.index')"
            :api-fetch-action="loadRequestsFromUrl"
            :hide-built-in-search="true"
            :loading="isLoading"
            :show-column-filters="false"
        >
            <template #head-left>
                <div class="flex min-w-0 flex-nowrap items-center gap-3">
                    <div class="w-64 shrink-0">
                        <BaseInputWithIcon
                            v-model="search"
                            :icon="IconName.search"
                            full-width
                            :placeholder="$t('trans.search')"
                            class="rounded-full"
                        />
                    </div>
                    <div class="w-48 shrink-0">
                        <BaseCombobox
                            v-model="statusSelection"
                            :options="props.statusOptions"
                            :placeholder="$t('trans.student_id_card_filter_status')"
                            class="w-full rounded-full"
                        />
                    </div>
                    <div class="w-44 shrink-0">
                        <BaseCombobox
                            v-model="reasonSelection"
                            :options="props.reasonOptions"
                            :placeholder="$t('trans.student_id_card_filter_reason')"
                            class="w-full rounded-full"
                        />
                    </div>
                </div>
            </template>
            <template #head-right>
                <div class="ml-8 flex items-center gap-3">
                    <GenericButton
                        v-if="hasAbility('print:student-id-card-requests')"
                        :icon="IconName.import"
                        class="h-8 w-36 justify-center rounded-full"
                        :icon-variant="ColorVariant.white"
                        :variant="ColorVariant.primary"
                        :title="$t('trans.student_id_card_import_list')"
                        @click="router.visit(route('admin.students.id-card-requests.import'))"
                    />
                    <GenericButton
                        v-if="hasAbility('print:student-id-card-requests')"
                        :icon="IconName.printer"
                        class="h-8 w-36 justify-center rounded-full"
                        :icon-variant="ColorVariant.white"
                        :variant="ColorVariant.danger"
                        :title="$t('trans.student_id_card_bulk_print')"
                        :disabled="!canBulkPrint"
                        @click="bulkPrint"
                    />
                    <SettingsButton
                        v-if="hasAbility('update:student-id-card-settings')"
                        classes="w-36 justify-center"
                        :title="$t('trans.settings')"
                        @click="openSettings"
                    />
                </div>
            </template>
        </DataTable>
        <IdCardSettingsModal :id-card-settings="idCardSettings" />
    </PageContainer>
</template>
