<script setup lang="ts">
import EnrolmentApplicantLookupDrawer from '@/components/enrolments/EnrolmentApplicantLookupDrawer.vue';
import { canUseEnrolmentApplicantLookup } from '@/lib/enrolmentStatusNavigation';
import { getUserAbilities } from '@/lib/permissions';
import { DepartmentDistribution } from '@/types/dashboard';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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
const lookupOpen = ref(false);

const canLookup = computed(
    () => Boolean(props.intakePeriod?.id) && canUseEnrolmentApplicantLookup(getUserAbilities()),
);

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
        <template v-if="canLookup" #backNavigationTrailing>
            <button
                type="button"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                @click="lookupOpen = true"
            >
                <Search class="h-3.5 w-3.5 shrink-0" />
                {{ $t('enrolments.find_applicant') }}
            </button>
        </template>

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

        <EnrolmentApplicantLookupDrawer
            v-if="canLookup"
            v-model:open="lookupOpen"
            :intake-period-id="intakePeriod.id"
            :intake-period-name="intakePeriod.attributes.name"
            from="enrolments"
        />
    </PageContainer>
</template>
