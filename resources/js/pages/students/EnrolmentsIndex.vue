<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import { GenericButton } from '@/components/core/button';
import DepartmentCourseComboSelect from '@/components/core/form/combobox/DepartmentCourseComboSelect.vue';
import DepartmentLevelComboSelect from '@/components/core/form/combobox/DepartmentLevelComboSelect.vue';
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import { AuthObject, DataFilters, DataListProps } from '@/types/data-pagination';
import { EnrolmentSearchParams } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { ref } from 'vue';
import { useUtils } from '@/composables/core/useUtils';

const { enrolmentColumns } = useEnrolments();

interface Props {
    enrolments: DataListProps;
    trashedCount: any;
    filters: DataFilters;
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();
const breadcrumbs: Array<Link> = [{ transKey: 'dashboard', href: route('dashboard') }, { transChoiceKey: 'enrolment' }];
const {navigateTo} = useUtils()
const department = ref<SelectOption | null>(null);
const level = ref<SelectOption | null>(null);
const course = ref<SelectOption | null>(null);
const modeOfStudy = ref<SelectOption | null>(null);
const intakePeriod = ref<SelectOption | null>(null);

const form = useForm<EnrolmentSearchParams>({
    department_course_id: '',
    department_level_id: '',
    institution_department_id: '',
    intake_period_id: '',
    mode_of_study_id: '',
});
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5 mb-3">
                <IntakePeriodComboSelect v-model="intakePeriod" :is-required="true" class="w-full" />
                <ModeOfStudyComboSelect :form="form" v-model="modeOfStudy" :error="form.errors.mode_of_study_id" :is-required="true" />
                <InstitutionDepartmentComboSelect
                    :form="form"
                    v-model="department"
                    :error="form.errors.institution_department_id"
                    :is-required="true"
                />
                <DepartmentLevelComboSelect
                    :form="form"
                    :institution-department-id="department?.value?.toString() ?? ''"
                    v-model="level"
                    :error="form.errors.department_level_id"
                    :is-required="true"
                />
                <DepartmentCourseComboSelect
                    :form="form"
                    :department-level-id="level?.value?.toString() ?? ''"
                    v-model="course"
                    :error="form.errors.department_course_id"
                    :is-required="true"
                />
            </div>
        </form>
        <DataTable
            :data="enrolments.data"
            :trashed-count="trashedCount"
            :filters="filters"
            :show-archived-filter="false"
            :search-url="route('enrolments.index')"
            :pagination="{ ...enrolments.links, ...enrolments.meta }"
            :columns="enrolmentColumns()"
        >
            <template #head-right v-if="hasAbility('create:students')">
                <GenericButton
                    :icon="IconName.danger"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.danger_outline"
                    @click="() => navigateTo(route('enrolments.faulty-applications'))"
                    title="Faulty Applications"
                />
                <GenericButton
                    :icon="IconName.add"
                    class="rounded-full"
                    :icon-variant="ColorVariant.white"
                    :variant="ColorVariant.primary_outline"
                    @click="() => navigateTo(route('enrolments.payment-verification'))"
                    title="Create New Enrolment"
                />
            </template>
        </DataTable>
    </PageContainer>
</template>
