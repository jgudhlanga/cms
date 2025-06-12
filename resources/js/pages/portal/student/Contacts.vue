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
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'my_contacts' }];
</script>
<template>
    <Head :title="$t('trans.my_contacts')" />
    <PageContainer :breadcrumbs="breadcrumbs">

    </PageContainer>
</template>
