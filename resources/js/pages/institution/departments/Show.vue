<script setup lang="ts">
import InstitutionDepartmentComboSelect from '@/components/core/form/combobox/InstitutionDepartmentComboSelect.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import { useInstitution } from '@/composables/institution/useInstitution';
import { hasAbility } from '@/lib/permissions';
import ClassConfig from '@/pages/institution/academicCalendars/partials/ClassConfig.vue';
import LinkApplicationStepsToDepartment from '@/pages/institution/departments/partials/LinkApplicationStepsToDepartment.vue';
import LinkCoursesToDepartment from '@/pages/institution/departments/partials/LinkCoursesToDepartment.vue';
import LinkLevelsToDepartment from '@/pages/institution/departments/partials/LinkLevelsToDepartment.vue';
import StepActions from '@/pages/institution/departments/partials/StepActions.vue';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router, useForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;

const institutionDepartmentId = String(department.id);

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: department.attributes.department },
];

const { departmentTabs } = useInstitution();
const { activeTab } = storeToRefs(useDepartmentMetaStore());
const canViewAnyDepartmentMetaData = hasAbility('viewAny:department-metadata');
const switchDepartmentForm = useForm({
    department: null,
});
const selectedDepartment = ref<SelectOption>({
    value: Number(department.id ?? 0),
    label: department.attributes?.department ?? '',
});

watch(selectedDepartment, (nextDepartment) => {
    const selectedDepartmentId = Number(nextDepartment?.value ?? 0);
    const currentDepartmentId = Number(props.department.id ?? 0);

    if (selectedDepartmentId <= 0 || selectedDepartmentId === currentDepartmentId) {
        return;
    }

    router.get(route('institution-departments.show', selectedDepartmentId));
});

const visibleTabs = computed(() => {
    return departmentTabs(props.department).filter((tab) => tab.show);
});

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution.index')">
        <template #backNavigationLeading v-if="canViewAnyDepartmentMetaData">
            <div class="flex w-full min-w-0 grow">
                <InstitutionDepartmentComboSelect
                    class="min-w-0 flex-1"
                    :form="switchDepartmentForm"
                    v-model="selectedDepartment"
                    :label="$t('trans.ui_change_department')"
                    :vertical-layout="false"
                    :label-uppercase="true"
                    width-class="w-full"
                />
            </div>
        </template>
        <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
        <div class="py-4">
            <component :is="activeSection?.component" v-if="activeSection" />
        </div>
        <LinkLevelsToDepartment :institution-department-id="institutionDepartmentId" />
        <LinkCoursesToDepartment :institution-department-id="institutionDepartmentId" />
        <LinkApplicationStepsToDepartment :institution-department-id="institutionDepartmentId" />
        <StepActions :institution-department-id="institutionDepartmentId" />
        <ClassConfig :institution-department-id="institutionDepartmentId" />
    </PageContainer>
</template>
