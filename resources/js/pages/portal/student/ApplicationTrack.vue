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

const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'track_application' }];
</script>
<template>
    <Head :title="$tChoice('trans.application', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
       My Application
    </PageContainer>
</template>
