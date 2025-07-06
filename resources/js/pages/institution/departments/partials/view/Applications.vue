<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';

interface Props {
    institutionDepartmentId: string;
}

 defineProps<Props>();
const { navigateTo } = useUtils();
</script>

<template>
    <div class="flex flex-col">
        <div class="flex justify-end" v-if="hasAbility('create:department-metadata')">
            <GenericButton
                :icon="IconName.cogs"
                class="cursor-pointer rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => navigateTo(route('department-application-steps.index', institutionDepartmentId))"
                :title="$t('trans.application_step_config')"
            />
        </div>
    </div>
    <div>List Applications here</div>
</template>
