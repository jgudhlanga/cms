<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';

interface Props {
    user: User;
    errors: object;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard' }
];


</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">

    </PageContainer>
</template>
