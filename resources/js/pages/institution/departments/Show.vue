<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
import AboutUs from '@/pages/institution/departments/partials/view/AboutUs.vue';
import Announcements from '@/pages/institution/departments/partials/view/Announcements.vue';
import Calendar from '@/pages/institution/departments/partials/view/Calendar.vue';
import Classes from '@/pages/institution/departments/partials/view/Classes.vue';
import Courses from '@/pages/institution/departments/partials/view/Courses.vue';
import Levels from '@/pages/institution/departments/partials/view/Levels.vue';
import ProvisionalClasses from '@/pages/institution/departments/partials/view/ProvisionalClasses.vue';
import Staff from '@/pages/institution/departments/partials/view/Staff.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { CustomTab } from '@/types/utils';
import { Head } from '@inertiajs/vue3';
import { ref, h } from 'vue';

interface Props {
    department: InstitutionDepartment;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { department } = props;
const breadcrumbs: Array<Link> = [
    { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
    { transChoiceKey: 'department', href: route('institution-departments.index') },
    { title: department.attributes.department },
];

const defaultValue = ref('about_us');
const tabs: Array<CustomTab> = [
    { label: 'About Us', value: 'about_us', component: h(AboutUs) },
    { label: 'Courses', value: 'courses', component: Courses },
    { label: 'Levels', value: 'levels', component: Levels },
    { label: 'Staff', value: 'staff', component: Staff },
    { label: 'Provisional Classes', value: 'provisional_classes', component: ProvisionalClasses },
    { label: 'Classes', value: 'classes', component: Classes },
    { label: 'Calendar', value: 'calendar', component: Calendar },
    { label: 'Announcements', value: 'announcements', component: Announcements },
];
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseTabs :tabs="tabs" :default-value="defaultValue" />
    </PageContainer>
</template>
