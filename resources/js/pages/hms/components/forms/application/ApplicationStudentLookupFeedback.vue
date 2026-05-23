<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { Label } from '@/components/ui/label';
import { TypeVariant } from '@/enums/type-variants';
import { hostelApplicationBlockerMessage } from '@/lib/hms/applicationBlockers';
import type {
    HostelApplicationEligibilityRule,
    HostelApplicationLookupRoomAvailability,
    HostelApplicationLookupSemester,
} from '@/types/hms';

defineProps<{
    semesterInfo: HostelApplicationLookupSemester | null;
    roomAvailabilityInfo: HostelApplicationLookupRoomAvailability | null;
    canSubmitFromLookup: boolean;
    lookupBlockers: string[];
    eligibility: HostelApplicationEligibilityRule[];
    eligibilityPassed: boolean;
}>();
</script>

<template>
    <BaseAlert
        v-if="semesterInfo"
        :description="
            $t('hms.semester_dates_applied', {
                label: semesterInfo.label,
                check_in: semesterInfo.checkIn,
                check_out: semesterInfo.checkOut,
            })
        "
        :type="TypeVariant.info"
    />

    <BaseAlert
        v-if="roomAvailabilityInfo && canSubmitFromLookup"
        :description="
            $t('hms.hostel_capacity_available', {
                count: roomAvailabilityInfo.availableBeds,
                hostels: roomAvailabilityInfo.hostels.join(', '),
            })
        "
        :type="TypeVariant.success"
    />

    <BaseAlert
        v-for="blocker in lookupBlockers"
        :key="blocker"
        :description="hostelApplicationBlockerMessage(blocker)"
        :type="TypeVariant.danger"
    />

    <div v-if="eligibility.length" class="space-y-2">
        <Label>{{ $t('hms.eligibility_rules_heading') }}</Label>
        <BaseAlert
            v-for="rule in eligibility"
            :key="rule.key"
            :description="rule.message"
            :type="rule.passed ? TypeVariant.success : TypeVariant.danger"
        />
        <BaseAlert
            v-if="!eligibilityPassed"
            :description="$t('hms.eligibility_manual_review_notice')"
            :type="TypeVariant.warning"
        />
    </div>
</template>
