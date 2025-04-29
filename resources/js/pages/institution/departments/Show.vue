<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

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
</script>

<template>
    <Head :title="$tChoice('trans.department', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">

    </PageContainer>
</template>
