<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import CustomSeparator from '@/components/core/util/CustomSeparator.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { AuthObject } from '@/types/data-pagination';

// Props
interface Props {
    auth: AuthObject;
    errors: object;
    message: string;
}
defineProps<Props>();
const { navigateTo } = useUtils();
</script>
<template>
    <StudentPageHeader />
    <div class="mt-20 flex w-full flex-col bg-white px-10 md:p-0">
        <div class="flex w-full flex-col md:mx-auto md:w-7/8">
            <BaseAlert :description="message" v-if="message" class="mb-5" :type="TypeVariant.danger" />
            <CustomSeparator classes="h-1 my-5" />
            <div class="mb-10 flex items-center justify-center space-x-3">
                <BaseButton
                    @click="navigateTo(route('portal.applications'))"
                    type="button"
                    :variant="ColorVariant.shade"
                    class="w-1/2 md:w-[200px]"
                    :size="ButtonSize.xl"
                >
                    {{ $t('trans.back') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
