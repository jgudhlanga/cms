<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { hasAbility } from '@/lib/permissions';
import IntakePeriodClassSizeConfig from '@/pages/institution/departments/partials/view/IntakePeriodClassSizeConfig.vue';
import { InstitutionDepartment } from '@/types/institution';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';

interface Props {
    department: InstitutionDepartment;
}

defineProps<Props>();
const { navigateTo } = useUtils();
</script>

<template>
    <div class="flex flex-col">
        <div class="mt-6  flex justify-end" v-if="hasAbility('create:department-metadata')">
            <GenericButton
                :icon="IconName.cogs"
                class="cursor-pointer rounded-full"
                :icon-variant="ColorVariant.primary"
                :variant="ColorVariant.primary_outline"
                @click="() => navigateTo(route('department-application-steps.config', department?.id?.toString() ?? ''))"
                :title="$t('trans.application_step_config')"
            />
        </div>
        <CustomSeparator classes="my-4 h-2"/>
        <IntakePeriodClassSizeConfig :department="department" />
        <CustomSeparator classes="my-4 h-2"/>
    </div>
</template>
