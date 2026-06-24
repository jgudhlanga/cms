<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { DepartmentDistribution } from '@/types/dasboard';
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
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'enrolment' }];
const { navigateTo } = useUtils();
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
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-6 flex items-center justify-end space-x-2">
            <GenericButton
                v-if="hasAbility('root:manage')"
                :icon="IconName.danger"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.danger_outline"
                @click="() => navigateTo(route('enrolments.faulty-applications'))"
                :title="$t('trans.ui_faulty_applications')"
            />
            <GenericButton
                v-if="hasAbility('create:student-applications')"
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary_outline"
                @click="() => navigateTo(route('enrolments.enrolment-lookup'))"
                :title="$t('trans.ui_create_new_enrolment')"
            />
        </div>
        <DistributionByDepartment
            :department-distribution="departmentDistribution"
            :show-actions-column="true"
            :show-filters="true"
            v-model:intakePeriodModel="intakePeriodModel"
            :intake-periods="intakePeriods"
            :handle-filter-change="handleFilterChange"
        />
    </PageContainer>
</template>
