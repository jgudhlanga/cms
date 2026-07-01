<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import GenericButton from '@/components/core/button/GenericButton.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import VerifiedStudentsFinalEnrolmentFilters from '@/components/maintenance/VerifiedStudentsFinalEnrolmentFilters.vue';
import { useVerifiedStudentsFinalEnrolment } from '@/composables/maintenance/useVerifiedStudentsFinalEnrolment';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import type { DataListProps } from '@/types/data-pagination';
import type {
    VerifiedStudentForFinalEnrolment,
    VerifiedStudentsFinalEnrolmentFiltersState,
    VerifiedStudentsFinalEnrolmentPaymentWindow,
    VerifiedStudentsFinalEnrolmentSummary,
} from '@/types/verified-students-final-enrolment';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    paymentWindow: VerifiedStudentsFinalEnrolmentPaymentWindow;
}>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transKey: 'trans.maintenance', href: route('maintenance.index') },
    { transKey: 'trans.maintenance_verified_students_final_enrolment' },
];

const {
    createVerifiedStudentColumns,
    fetchVerifiedStudents,
    fetchPaymentSummary,
    confirmAndRunBulkFinalise,
    isLoading,
    isSummaryLoading,
    isRunning,
    runButtonLabel,
} = useVerifiedStudentsFinalEnrolment();

const students = ref<DataListProps<VerifiedStudentForFinalEnrolment>>({
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

const filters = ref<VerifiedStudentsFinalEnrolmentFiltersState>({});
const summary = ref<VerifiedStudentsFinalEnrolmentSummary>({
    total: 0,
    eligible: null,
    noPayment: null,
    missingStudentNumber: 0,
    paymentSummaryReady: false,
});

const paymentWindowLabel = computed(() =>
    trans('trans.maintenance_verified_students_final_enrolment_payment_window', {
        start: props.paymentWindow.startDate,
        end: props.paymentWindow.endDate,
    }),
);

const columns = computed(() => createVerifiedStudentColumns());

const applyResponse = (response: Awaited<ReturnType<typeof fetchVerifiedStudents>>): void => {
    if (!response) {
        return;
    }

    students.value = {
        data: (response.data ?? []) as VerifiedStudentForFinalEnrolment[],
        links: (response.links ?? students.value.links) as DataListProps<VerifiedStudentForFinalEnrolment>['links'],
        meta: (response.meta ?? students.value.meta) as DataListProps<VerifiedStudentForFinalEnrolment>['meta'],
    };

    if (response.summary) {
        summary.value = response.summary;
    }
};

const loadPaymentSummary = async (): Promise<void> => {
    const response = await fetchPaymentSummary(filters.value);

    if (response?.summary) {
        summary.value = response.summary;
    }
};

const loadStudents = async (nextFilters: VerifiedStudentsFinalEnrolmentFiltersState = filters.value) => {
    filters.value = nextFilters;
    applyResponse(await fetchVerifiedStudents(nextFilters));

    summary.value = {
        ...summary.value,
        eligible: null,
        noPayment: null,
        paymentSummaryReady: false,
    };

    void loadPaymentSummary();
};

const onFiltersChange = async (nextFilters: VerifiedStudentsFinalEnrolmentFiltersState) => {
    await loadStudents(nextFilters);
};

const loadStudentsFromUrl = async (url: string) => {
    applyResponse(await fetchVerifiedStudents(filters.value, url));
};

const reloadStudents = async () => {
    await loadStudents(filters.value);
    await loadPaymentSummary();
};

const onRunBulkFinalise = () => {
    void confirmAndRunBulkFinalise(reloadStudents);
};

onMounted(() => {
    void loadStudents();
});
</script>

<template>
    <Head :title="trans('trans.maintenance_verified_students_final_enrolment')" />

    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6">
            <div class="space-y-4 lg:sticky lg:top-4 lg:self-start">
                <BaseAlert
                    :type="TypeVariant.info"
                    :description="trans('trans.maintenance_verified_students_final_enrolment_page_description')"
                />
                <p class="text-sm text-muted-foreground">
                    {{ paymentWindowLabel }}
                </p>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">
                        {{
                            trans('trans.maintenance_verified_students_final_enrolment_summary_total', {
                                count: String(summary.total),
                            })
                        }}
                    </span>
                    <span
                        v-if="isSummaryLoading || summary.eligible === null"
                        class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                    >
                        {{ trans('trans.maintenance_verified_students_final_enrolment_summary_loading') }}
                    </span>
                    <template v-else>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                            {{
                                trans('trans.maintenance_verified_students_final_enrolment_summary_eligible', {
                                    count: String(summary.eligible),
                                })
                            }}
                        </span>
                        <span class="rounded-full bg-destructive/15 px-2.5 py-1 text-xs font-medium text-destructive">
                            {{
                                trans('trans.maintenance_verified_students_final_enrolment_summary_no_payment', {
                                    count: String(summary.noPayment),
                                })
                            }}
                        </span>
                    </template>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900 dark:bg-amber-950 dark:text-amber-100">
                        {{
                            trans('trans.maintenance_verified_students_final_enrolment_summary_missing_number', {
                                count: String(summary.missingStudentNumber),
                            })
                        }}
                    </span>
                </div>
            </div>

            <div class="min-w-0">
                <DataTable
                    :data="students.data"
                    :filters="filters"
                    :show-archived-filter="false"
                    :pagination="{ ...students.links, ...students.meta }"
                    :columns="columns"
                    :use-api="true"
                    :search-url="route('maintenance.verified-students-final-enrolment.data')"
                    :api-fetch-action="loadStudentsFromUrl"
                    :hide-built-in-search="true"
                    :loading="isLoading"
                    :disable-create="true"
                    :disable-import="true"
                    :disable-export="true"
                    :show-column-filters="false"
                >
                    <template #head-left>
                        <VerifiedStudentsFinalEnrolmentFilters :filters="filters" @change="onFiltersChange" />
                    </template>
                    <template #head-right>
                        <GenericButton
                            class="ml-2 rounded-full"
                            :icon="IconName.check"
                            :variant="ColorVariant.primary"
                            :title="runButtonLabel()"
                            :disabled="isRunning"
                            :processing="isRunning"
                            @click="onRunBulkFinalise"
                        />
                    </template>
                </DataTable>
            </div>
        </div>
    </PageContainer>
</template>
