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

const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'application_confirmation' }];
</script>
<template>
    <Head :title="$t('trans.application_confirmation')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        {{ $t('trans.application_confirmation_description') }}
    </PageContainer>
</template>
