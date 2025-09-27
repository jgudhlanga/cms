<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import AnimatedCheckMark from '@/components/core/util/AnimatedCheckMark.vue';
import BasePaymentStatus from '@/components/shared/integraions/BasePaymentStatus.vue';
import { ColorVariant } from '@/enums/colors';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { IconName } from '@/lib/icons';
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.courses"
        :title="`${course ? $t('trans.create') : $t('trans.cr››eate')} ${$tChoice('trans.course', 1)}`"
        :on-form-action="() => saveCourse(form, course)"
        :form="form"
    >
        <BasePaymentStatus :details="composeDetails" color="green">
            <template #header>
                <div :class="`flex flex-col items-center bg-gradient-to-br from-green-400 to-green-600 px-6 py-8`">
                    <AnimatedCheckMark />
                    <h1 class="text-2xl font-bold text-green-100">{{ composeDetails?.attributes?.paymentStatus }}!</h1>
                    <p :class="`mt-2 text-center text-green-100`">Transaction found</p>
                </div>
            </template>
            <template #status>
                <BaseIcon :name="IconName.check_done" size="18" class="mr-2 text-green-600" />
                {{ composeDetails?.attributes?.paymentStatus }}
            </template>
            <template #action-buttons>
                <BaseButton
                    :processing="processingUpdate"
                    type="submit"
                    classes="rounded-full"
                    title="Update Student Payment Status"
                    @click="() => {}"
                    :variant="ColorVariant.success"
                />
            </template>
        </BasePaymentStatus>
    </BaseModal>
</template>
