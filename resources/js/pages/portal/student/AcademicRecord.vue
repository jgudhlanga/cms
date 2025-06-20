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
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'academic_record' }];
</script>
<template>
    <Head :title="$t('trans.academic_record')" />
    <PageContainer :breadcrumbs="breadcrumbs"></PageContainer>
</template>
