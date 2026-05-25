<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import ApplicationStudentLookupFeedback from '@/pages/hms/components/forms/application/ApplicationStudentLookupFeedback.vue';
import ApplicationStudentLookupGrid from '@/pages/hms/components/forms/application/ApplicationStudentLookupGrid.vue';
import { TypeVariant } from '@/enums/type-variants';
import { SizeVariant } from '@/enums/sizes';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import type {
    HostelApplicationEligibilityRule,
    HostelApplicationLookupRoomAvailability,
    HostelApplicationLookupSemester,
    HostelApplicationStudentLookup,
} from '@/types/hms';

defineProps<{
    isSearching: boolean;
    lookup: HostelApplicationStudentLookup | null;
    showStudentLookupGrid: boolean;
    showStudentSearchHelper: boolean;
    checkIn: string;
    checkOut: string;
    semesterInfo: HostelApplicationLookupSemester | null;
    roomAvailabilityInfo: HostelApplicationLookupRoomAvailability | null;
    canSubmitFromLookup: boolean;
    lookupBlockers: string[];
    eligibility: HostelApplicationEligibilityRule[];
    eligibilityPassed: boolean;
}>();

const emit = defineEmits<{
    search: [];
}>();

const studentSearch = defineModel<string>({ required: true });
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <BaseInput
                    input-id="application-student-search"
                    v-model="studentSearch"
                    :label="$t('hms.search_student_placeholder')"
                    @keyup.enter="emit('search')"
                />
            </div>
            <BaseButton
                type="button"
                :title="$t('trans.search')"
                :loading="isSearching"
                :size="SizeVariant.md"
                :variant="ColorVariant.success"
                @click="emit('search')"
            >
                <component :is="icons[IconName.search]" />
            </BaseButton>
        </div>

        <BaseAlert
            v-if="showStudentSearchHelper"
            :description="$t('hms.search_student_to_load_fields')"
            :type="TypeVariant.info"
        />

        <ApplicationStudentLookupGrid
            v-if="showStudentLookupGrid && lookup"
            :lookup="lookup"
            :check-in="checkIn"
            :check-out="checkOut"
        />

        <ApplicationStudentLookupFeedback
            v-if="lookup"
            :semester-info="semesterInfo"
            :room-availability-info="roomAvailabilityInfo"
            :can-submit-from-lookup="canSubmitFromLookup"
            :lookup-blockers="lookupBlockers"
            :eligibility="eligibility"
            :eligibility-passed="eligibilityPassed"
        />
    </section>
</template>
