<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

interface Props {
    user: User;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'personal_details' }];
</script>
<template>
    <Head :title="$t('trans.create_new_application')" />
    <PageContainer :breadcrumbs="breadcrumbs">

    </PageContainer>
</template>
