<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }, { title: user.attributes?.name }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">

    </PageContainer>
</template>
