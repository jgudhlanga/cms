<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useInstitution } from '@/composables/institution/useInstitution';
import LinkCoursesToDepartment from '@/pages/institution/departments/partials/LinkCoursesToDepartment.vue';
import LinkLevelsToDepartment from '@/pages/institution/departments/partials/LinkLevelsToDepartment.vue';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import LinkApplicationStepsToDepartment
    from '@/pages/institution/departments/partials/LinkApplicationStepsToDepartment.vue';
import StepActions from '@/pages/institution/departments/partials/StepActions.vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index', {is_academic: department.attributes?.isAcademic}) },
    { title: department.attributes.department },
];

const { departmentTabs } = useInstitution();
const { activeTab } = storeToRefs(useDepartmentMetaStore());
const visibleTabs = computed(() => {
    return departmentTabs(props.department).filter(tab => tab.show);
});
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in visibleTabs"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="text-sm font-light uppercase"
                >
                    {{ tab?.transLabel!() }}
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in visibleTabs" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
        <LinkLevelsToDepartment :institution-department-id="department.id?.toString() ?? ''" />
        <LinkCoursesToDepartment :institution-department-id="department.id?.toString() ?? ''" />
        <LinkApplicationStepsToDepartment :institution-department-id="department.id?.toString() ?? ''" />
        <StepActions :institution-department-id="department.id?.toString() ?? ''" />
    </PageContainer>
</template>
