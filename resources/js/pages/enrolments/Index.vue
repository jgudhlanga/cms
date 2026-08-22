<script setup lang="ts">
import { DepartmentDistribution } from '@/types/dashboard';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    departmentDistribution: DepartmentDistribution[];
    auth: AuthObject;
    intakePeriods: IntakePeriod[];
    intakePeriod: IntakePeriod;
    errors: object;
}

const props = defineProps<Props>();
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'trans.application' }];
const intakePeriodModel = ref<SelectOption | null>(null);

onMounted(async () => {
    if (props.intakePeriod) {
        intakePeriodModel.value = { value: Number(props.intakePeriod.id), label: props.intakePeriod.attributes.name };
    }
});

const handleFilterChange = (option: SelectOption) => {
    router.get(
        window.location.pathname,
        {
            intake_period_id: String(option.value),
        },
        {
            // options here
        },
    );
};
</script>

<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <DistributionByDepartment
            :department-distribution="departmentDistribution"
            :show-actions-column="true"
            :show-filters="true"
            :show-summary-cards="true"
            origin="enrolments"
            v-model:intakePeriodModel="intakePeriodModel"
            :intake-periods="intakePeriods"
            :handle-filter-change="handleFilterChange"
        />
    </PageContainer>
</template>
