<script setup lang="ts">
import HostelEligibilityStatus from '@/components/hms/HostelEligibilityStatus.vue';
import { Label } from '@/components/ui/label';
import { hostelApplicationBlockerMessage } from '@/lib/hms/applicationBlockers';
import type {
    HostelApplicationEligibilityRule,
    HostelApplicationLookupRoomAvailability,
    HostelApplicationLookupSemester,
} from '@/types/hms';
import { computed } from 'vue';

const props = defineProps<{
    semesterInfo: HostelApplicationLookupSemester | null;
    roomAvailabilityInfo: HostelApplicationLookupRoomAvailability | null;
    canSubmitFromLookup: boolean;
    lookupBlockers: string[];
    eligibility: HostelApplicationEligibilityRule[];
    eligibilityPassed: boolean;
}>();

const hasFeedback = computed(
    () =>
        !!props.semesterInfo
        || (!!props.roomAvailabilityInfo && props.canSubmitFromLookup)
        || props.lookupBlockers.length > 0
        || props.eligibility.length > 0
        || !props.eligibilityPassed,
);
</script>

<template>
    <div v-if="hasFeedback" class="space-y-1.5">
        <Label v-if="eligibility.length">{{ $t('hms.eligibility_rules_heading') }}</Label>

        <div class="grid grid-cols-1 gap-x-4 gap-y-1.5 md:grid-cols-3">
            <p
                v-if="semesterInfo"
                class="text-sm leading-snug text-sky-600 dark:text-sky-400"
            >
                {{
                    $t('hms.semester_dates_applied', {
                        label: semesterInfo.label,
                        check_in: semesterInfo.checkIn,
                        check_out: semesterInfo.checkOut,
                    })
                }}
            </p>

            <p
                v-if="roomAvailabilityInfo && canSubmitFromLookup"
                class="text-sm leading-snug text-emerald-600 dark:text-emerald-400"
            >
                {{
                    $t('hms.hostel_capacity_available', {
                        count: roomAvailabilityInfo.availableBeds,
                        hostels: roomAvailabilityInfo.hostels.join(', '),
                    })
                }}
            </p>

            <p
                v-for="blocker in lookupBlockers"
                :key="blocker"
                class="text-sm leading-snug text-destructive"
            >
                {{ hostelApplicationBlockerMessage(blocker) }}
            </p>

            <HostelEligibilityStatus
                v-if="eligibility.length"
                :rules="eligibility"
                :show-heading="false"
                grid
            />

            <p
                v-if="!eligibilityPassed"
                class="text-sm leading-snug text-amber-600 dark:text-amber-400 md:col-span-3"
            >
                {{ $t('hms.eligibility_manual_review_notice') }}
            </p>
        </div>
    </div>
</template>
