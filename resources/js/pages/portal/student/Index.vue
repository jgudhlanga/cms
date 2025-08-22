<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';
import { BaseButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { navigateTo } = useUtils();

const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transChoiceKey: 'dashboard' }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col justify-center items-center">
            <BaseButton @click="() => navigateTo(route('generate-pdf', 1))">Redirect With Flush Message</BaseButton>
        </div>
        <ComingSoonAnimated />
    </PageContainer>
</template>
