<script setup lang="ts">
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import type { HostelApplicationStudentLookup } from '@/types/hms';

defineProps<{
    lookup: HostelApplicationStudentLookup;
    checkIn: string;
    checkOut: string;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <BaseInput
            input-id="application-student-name"
            :model-value="lookup.name ?? ''"
            :label="$tChoice('trans.name', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-number"
            :model-value="lookup.studentNumber ?? ''"
            :label="$tChoice('trans.student_number', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-gender"
            :model-value="lookup.gender ?? ''"
            :label="$tChoice('trans.gender', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-course"
            :model-value="lookup.course ?? ''"
            :label="$tChoice('trans.course', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-level"
            :model-value="lookup.level ?? ''"
            :label="$tChoice('trans.level', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-phone"
            :model-value="lookup.phoneNumber ?? ''"
            :label="$tChoice('trans.phone', 1)"
            disabled
        />
        <BaseInput
            input-id="application-student-email"
            class="md:col-span-1"
            :model-value="lookup.emailAddress ?? ''"
            :label="$t('trans.email')"
            disabled
        />
        <BaseInput
            input-id="application-student-physical-address"
            class="md:col-span-2"
            :model-value="lookup.physicalAddress ?? ''"
            :label="$t('hms.physical_address')"
            disabled
        />
        <BaseInput
            input-id="application-student-next-of-kin-name"
            :model-value="lookup.nextOfKinName ?? ''"
            :label="$t('hms.next_of_kin_name')"
            disabled
        />
        <BaseInput
            input-id="application-student-next-of-kin-contact"
            :model-value="lookup.nextOfKinContact ?? ''"
            :label="$t('hms.next_of_kin_contact')"
            disabled
        />
        <BaseInput
            input-id="application-student-check-in"
            :model-value="checkIn"
            :label="$t('hms.check_in')"
            disabled
        />
        <BaseInput
            input-id="application-student-check-out"
            :model-value="checkOut"
            :label="$t('hms.check_out')"
            disabled
        />
    </div>
</template>
