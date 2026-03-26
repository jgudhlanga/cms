<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head, Link as InertiaLink } from '@inertiajs/vue3';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
}>();

const breadcrumbs: Array<Link> = [{ transKey: 'finance.finance' }];
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$tChoice('finance.finance', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ $tChoice('finance.finance', 1) }}</h2>
            <InertiaLink
                v-if="can['view:finance-settings']"
                :href="route('finance.settings')"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
            >
                {{ $tChoice('finance.setting', 2) }}
            </InertiaLink>
        </div>
    </PageContainer>
</template>
