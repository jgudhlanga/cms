<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import IntakePeriodSelect from '@/components/core/form/select/IntakePeriodSelect.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Avatar from '@/components/core/util/Avatar.vue';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ItemTitle from '@/components/core/util/ItemTitle.vue';
import { useIntakePeriods } from '@/composables/institution/useIntakePeriods';
import { useServerSide } from '@/composables/shared/useServerSide';
import { DepartmentEnrolmentCount } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { Link } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { department } = props;
const institutionDepartmentId = department?.id?.toString() ?? '';
const { getData, isLoading } = useServerSide();
const enrolments = ref<DepartmentEnrolmentCount[] | []>([]);
const intakePeriodId = ref<string | number | null>(null);
const { isLoading: intakePeriodsLoading, listIntakePeriods, intakePeriods } = useIntakePeriods();

onMounted(async () => {
    await listIntakePeriods(`api/v1/intake-periods?page_size=all`);
    intakePeriodId.value = intakePeriods.value?.data![0]?.id ?? null;
    await loadEnrolments();
});

const loadEnrolments = async () => {
    enrolments.value = await getData(`api/v1/departments/${institutionDepartmentId}/enrolments?intake_period=${intakePeriodId.value}`, () =>
        trans_choice('trans.enrolment', 2),
    );
};
const handleSelectionChange = async () => {
    await loadEnrolments();
};
</script>

<template>
    <div class="my-8 flex flex-col space-y-4">
        <div class="mb-8 grid grid-cols-1 gap-3 md:grid-cols-2">
            <IntakePeriodSelect
                :loading="intakePeriodsLoading"
                :data="intakePeriods?.data ?? []"
                :label-uppercase="true"
                :is-multi="false"
                :is-searchable="true"
                v-model="intakePeriodId"
                :vertical-layout="false"
                @update:modelValue="handleSelectionChange"
            />
        </div>
        <DataLoadingSpinner v-if="isLoading || intakePeriodsLoading" />
        <div class="flex flex-col" v-else>
            <template v-if="enrolments && enrolments.length > 0">
                <div v-for="enrolment in enrolments" :key="enrolment.departmentCourseId" class="flex flex-col space-y-4">
                    <CustomSeparator classes="h-[1px] mb-6" />
                    <HeadingSmall :title="enrolment.courseName" />
                    <Link
                        v-for="level in enrolment.levels"
                        :key="level.departmentLevelId"
                        :href="
                            route('department-levels.enrolments', {
                                institution_department: institutionDepartmentId,
                                department_level: level.departmentLevelId,
                            })
                        "
                    >
                        <div class="flex items-center space-x-2">
                            <ItemTitle :title="level.levelName" class="text-primary font-bold" />
                            <Avatar src="" :name="level.enrolmentsCount" :is-number="true" class="bg-primary text-white" />
                        </div>
                    </Link>
                    <CustomSeparator classes="h-[1px]" />
                </div>
            </template>
            <BaseAlert
                v-else
                :title="$t('trans.no_data')"
                :description="$t('trans.no_data_found_description', { data: $tChoice('trans.enrolment', 2) })"
            />
        </div>
    </div>
</template>
