<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseTabs from '@/components/core/tabs/BaseTabs.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useInstitution } from '@/composables/institution/useInstitution';

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
const { constDepartmentTabs } = useInstitution();
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseTabs :tabs="constDepartmentTabs" :default-value="defaultValue" />
    </PageContainer>
</template>
