<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import { useInstitution } from '@/composables/institution/useInstitution';
import { hasAbility } from '@/lib/permissions';
import ClassConfig from '@/pages/institution/academicCalendars/partials/ClassConfig.vue';
import DepartmentContextBar from '@/pages/institution/departments/partials/DepartmentContextBar.vue';
import DepartmentHero from '@/pages/institution/departments/partials/DepartmentHero.vue';
import LinkCoursesToDepartment from '@/pages/institution/departments/partials/LinkCoursesToDepartment.vue';
import LinkLevelsToDepartment from '@/pages/institution/departments/partials/LinkLevelsToDepartment.vue';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;

const institutionDepartmentId = String(department.id);

const departmentTitle = () => {
    let title = department.attributes.department;
    const code = department.attributes.departmentCode?.trim();

    if (code) {
        title += ` (${code})`;
    }

    return title;
};

const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', { is_academic: department.attributes?.isAcademic }) },
    { title: departmentTitle() },
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

const visibleTabs = computed(() => {
    return departmentTabs(props.department).filter((tab) => tab.show);
});

onMounted(() => {
    const tabParam = new URL(usePage().url, window.location.origin).searchParams.get('tab');
    if (tabParam && visibleTabs.value.some((tab) => tab.value === tabParam)) {
        activeTab.value = tabParam;
    }
});

watch(
    visibleTabs,
    (tabs) => {
        if (tabs.length === 0) {
            return;
        }

        if (!tabs.some((tab) => tab.value === activeTab.value)) {
            activeTab.value = tabs[0].value;
        }
    },
    { immediate: true },
);

watch(selectedDepartment, (nextDepartment) => {
    const selectedDepartmentId = Number(nextDepartment?.value ?? 0);
    const currentDepartmentId = Number(props.department.id ?? 0);

    if (selectedDepartmentId <= 0 || selectedDepartmentId === currentDepartmentId) {
        return;
    }

    router.get(route('institution-departments.show', selectedDepartmentId));
});

const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));

const activeTabDescription = computed(() => activeSection.value?.transDescription?.() ?? '');
</script>

<template>
    <Head :title="departmentTitle()" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('institution.index')">
        <template v-if="canViewAnyDepartmentMetaData" #backNavigationLeading>
            <DepartmentContextBar
                :department="department"
                :form="switchDepartmentForm"
                v-model="selectedDepartment"
                :is-academic="Number(department.attributes?.isAcademic) === 1"
            />
        </template>

        <div class="space-y-4">
            <DepartmentHero :department="department" />

            <div>
                <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" :description="activeTabDescription" />

                <div class="mt-3">
                    <component :is="activeSection?.component" v-if="activeSection" />
                </div>
            </div>
        </div>

        <LinkLevelsToDepartment :institution-department-id="institutionDepartmentId" />
        <LinkCoursesToDepartment :institution-department-id="institutionDepartmentId" />
        <ClassConfig :institution-department-id="institutionDepartmentId" />
    </PageContainer>
</template>
