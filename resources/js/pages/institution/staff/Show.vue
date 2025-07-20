<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useStaff } from '@/composables/institution/useStaff';
import { icons } from '@/lib/icons';
import { useStaffTabsStore } from '@/store/institution/useStaffTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { Staff } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

interface Props {
    department: InstitutionDepartment;
    staff: Staff;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department, staff } = props;
const user = staff.relationships?.user;
const institutionDepartmentId = department.id?.toString() ?? '';
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department, href: route('institution-departments.show', institutionDepartmentId) },
    { title: user?.attributes?.name },
    { transChoiceKey: 'profile', transChoiceKeyIndex: 1 },
];
const { staffTabs } = useStaff();
const { activeTab } = storeToRefs(useStaffTabsStore());
</script>

<template>
    <Head :title="`${$t('trans.staff')} ${$tChoice('trans.profile', 1)}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user?.attributes?.name" :line-two="staff.attributes?.employeeNumber" :show-user-profile-link="true" />
        <Tabs :default-value="activeTab" v-model="activeTab">
            <TabsList class="w-full">
                <TabsTrigger
                    v-for="tab in staffTabs(staff)"
                    :key="'tab_' + tab.value"
                    :value="tab.value"
                    class="flex items-center text-xs font-light uppercase"
                >
                    <component :is="icons[tab?.icon!]" />
                    <span>{{ tab?.transLabel!() }}</span>
                </TabsTrigger>
            </TabsList>
            <TabsContent v-for="tab in staffTabs(staff)" :value="tab.value" :key="'content_' + tab.value" class="py-4">
                <component :is="tab.component" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
