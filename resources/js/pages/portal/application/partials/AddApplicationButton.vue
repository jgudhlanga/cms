<script setup lang="ts">
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { Student } from '@/types/students';

interface Props {
    student: Student;
}

defineProps<Props>();
const { navigateToRegistrationOrMaintenance } = useRegistrationAvailability();
const paymentUrl = route('portal.application.fee-payment');
</script>

<template>
    <GenericButton
        :icon="IconName.add"
        class="w-full rounded-full md:w-50"
        :icon-variant="ColorVariant.white"
        :variant="ColorVariant.primary_outline"
        @click="() => navigateToRegistrationOrMaintenance(paymentUrl)"
        :title="$t('trans.ui_add_new_program')"
    />
</template>
