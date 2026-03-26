<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { useUtils } from '@/composables/core/useUtils';
import { IconName, icons } from '@/lib/icons';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

const props = defineProps<{
    auth: AuthObject;
    errors: object;
}>();

const breadcrumbs: Array<Link> = [{ transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1 }];
const can = props?.auth?.can;

const { navigateTo } = useUtils();
</script>

<template>
    <Head :title="$tChoice('finance.finance', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full justify-between">
            <HeadingSmall :title="$tChoice('finance.finance', 1)" />
            <BaseButton
                v-if="can['view:finance-settings']"
                @click="() => navigateTo(route('finance.settings'))"
                :title="$tChoice('finance.setting', 2)"
                :size="ButtonSize.sm"
                :variant="ColorVariant.primary_outline"
                classes="rounded-full"
            >
                <component :is="icons[IconName.cogs]" />
            </BaseButton>
        </div>
    </PageContainer>
</template>