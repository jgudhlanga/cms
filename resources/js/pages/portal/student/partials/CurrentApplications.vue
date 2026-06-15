<script setup lang="ts">
import CustomCard from '@/components/core/card/CustomCard.vue';
import GridLabelValue from '@/components/core/util/GridLabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudents } from '@/composables/students/useStudents';
import ComponentHeader from '@/pages/dashboard/components/ComponentHeader.vue';
import OfferLetterAnchor from '@/pages/portal/student/partials/OfferLetterAnchor.vue';
import { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    applications: Enrolment[];
    compact?: boolean;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    showHeader: true,
});

const applications = computed(() => props.applications);
const { formatDate } = useUtils();
const { getApplicationStatus, hasOfferLetter, statusMessage } = useStudents();
</script>

<template>
    <div class="flex w-full flex-col" v-if="applications && applications.length > 0">
        <ComponentHeader
            v-if="showHeader"
            header-title="Current applications"
            :description="$t('trans.ui_overview_of_your_applications')"
            class="mb-3"
        />
        <div :class="compact ? 'space-y-2' : 'space-y-3'">
            <CustomCard v-for="(application, index) in applications" :key="application.id">
                <template #title>{{ `${String(index + 1)}. ${application.attributes?.level}` }}</template>
                <template #body>
                    <div
                        :class="
                            compact
                                ? 'grid grid-cols-1 gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4'
                                : 'grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4'
                        "
                    >
                        <GridLabelValue :label="$tChoice('trans.department', 1)" :value="application.attributes?.department ?? ''" />
                        <GridLabelValue :label="$tChoice('trans.course', 1)" :value="application.attributes?.course ?? ''" />
                        <GridLabelValue :label="$t('trans.ui_application_status')" :value="getApplicationStatus(application) ?? ''" />
                        <GridLabelValue
                            v-if="compact && application.attributes?.createdAt"
                            :label="$tChoice('trans.application_date', 1)"
                            :value="formatDate(application.attributes.createdAt, 'L')"
                        />
                    </div>
                    <div :class="compact ? 'flex flex-col pt-2' : 'flex flex-col pt-4'">
                        <div class="text-primary text-xs">{{ statusMessage(application) }}</div>
                        <div class="flex justify-end gap-2">
                            <OfferLetterAnchor v-if="hasOfferLetter(application)" :student-program-id="String(application.id)" />
                        </div>
                    </div>
                </template>
            </CustomCard>
        </div>
    </div>
</template>
