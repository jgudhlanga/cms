<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { IconButton } from '@/components/core/button';
import AppLogo from '@/components/core/image/AppLogo.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import BaseTooltip from '@/components/core/util/BaseTooltip.vue';
import Heading from '@/components/core/util/Heading.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useLevels } from '@/composables/institution/useLevels';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { TypeVariant } from '@/enums/type-variants';
import { AuthObject } from '@/types/data-pagination';
import { onMounted } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { isLoading, levels, listLevels } = useLevels();

onMounted(async () => {
    await listLevels();
});
</script>
<template>
    <nav class="fixed top-0 right-0 left-0 z-50 w-full bg-white px-10 shadow">
        <div class="flex w-full items-center justify-between space-x-5 py-3 md:mx-auto md:w-7/8">
            <div class="flex size-8 items-center justify-start rounded-sm border">
                <AppLogo class="shrink-0" />
            </div>
            <Heading :title="user.attributes?.name" />
            <div class="flex">
                <BaseTooltip :content="`${$t('trans.logout')}`">
                    <TextLink :href="route('logout')" method="post" as="button" classes="text-destructive flex items-center">
                        <IconButton :icon="IconName.logout" :variant="ColorVariant.danger_outline" />
                    </TextLink>
                </BaseTooltip>
            </div>
        </div>
    </nav>
    <div class="flex flex-1 items-center py-16">
        <div class="flex w-full flex-col space-y-6 p-6">
            <div class="flex items-center justify-center p-5"></div>
            <DataLoadingSpinner v-if="isLoading" />
            <template v-else>
                <div class="mx-auto space-y-3" v-if="levels.length > 0">
                    <div v-for="level in levels" :key="level.id" class="flex  bg-sidebar rounded-2xl p-3 shadow">
                        <div class="flex justify-between items-center">
                            <div class="text-accent-foreground text-sm font-medium">{{ level.attributes?.name }}</div>
                        </div>
                    </div>
                </div>
                <BaseAlert
                    v-else
                    :type="TypeVariant.warning"
                    :description="$t('trans.no_data_found_description', { data: $tChoice('trans.level', 2) })"
                />
            </template>
        </div>
    </div>
</template>
