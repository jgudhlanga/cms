<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseSectionNav from '@/components/core/tabs/BaseSectionNav.vue';
import PageHeaderAvatar from '@/components/users/PageHeaderAvatar.vue';
import { useStaff } from '@/composables/institution/useStaff';
import { useStaffTabsStore } from '@/store/institution/useStaffTabsStore';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { Staff } from '@/types/staff';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

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

const visibleTabs = computed(() => staffTabs(staff, institutionDepartmentId));
const activeSection = computed(() => visibleTabs.value.find((tab) => tab.value === activeTab.value));
</script>

<template>
    <Head :title="`${$t('trans.staff')} ${$tChoice('trans.profile', 1)}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PageHeaderAvatar :line-one="user?.attributes?.name" :line-two="staff.attributes?.employeeNumber" :show-user-profile-link="true" />
        <BaseSectionNav v-model:active-tab="activeTab" :tabs="visibleTabs" />
        <div class="py-4">
            <component :is="activeSection?.component" v-if="activeSection" />
        </div>
    </PageContainer>
</template>
