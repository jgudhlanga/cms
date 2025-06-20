<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'sponsor' }];
</script>
<template>
    <Head :title="$tChoice('trans.sponsor', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs"></PageContainer>
</template>
