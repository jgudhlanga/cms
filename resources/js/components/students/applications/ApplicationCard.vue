<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseTag from '@/components/core/util/BaseTag.vue';
import InfoCard from '@/components/core/util/InfoCard.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudents } from '@/composables/students/useStudents';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { applicationStatusAlertType, applicationStatusVariant } from '@/lib/applicationStatusPresentation';
import OfferLetterAnchor from '@/pages/portal/student/partials/OfferLetterAnchor.vue';
import type { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    application: Enrolment;
    activeIntakePeriodIds?: Array<string | number>;
    compact?: boolean;
    canEdit?: boolean;
    editUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    activeIntakePeriodIds: () => [],
    compact: false,
    canEdit: false,
});

const { formatDate, navigateTo } = useUtils();
const { getApplicationStatus, hasOfferLetter, statusMessage } = useStudents();

const workflowStep = computed(() => getApplicationStatus(props.application) ?? '');
const statusLabel = computed(() => workflowStep.value.trim());
const showStatusBadge = computed(() => statusLabel.value.length > 0);
const statusVariant = computed(() => applicationStatusVariant(workflowStep.value));
const alertType = computed(() => applicationStatusAlertType(workflowStep.value));
const guidanceMessage = computed(() => statusMessage(props.application));

const subtitle = computed(() => {
    const department = props.application.attributes?.department?.trim() ?? '';
    const course = props.application.attributes?.course?.trim() ?? '';

    if (department && course) {
        return `${department} · ${course}`;
    }

    return department || course || '';
});

const applicationDate = computed(() => {
    const createdAt = props.application.attributes?.createdAt;

    return createdAt ? formatDate(createdAt, 'L') : '';
});

const modeOfStudy = computed(() => props.application.attributes?.modeOfStudy?.trim() ?? '');
const showMetadata = computed(() => Boolean(applicationDate.value || modeOfStudy.value));
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm transition-colors hover:border-primary/30"
    >
        <div class="border-b border-border bg-muted/30 px-3 py-3 sm:px-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-tight text-foreground sm:text-sm">
                            {{ application.attributes?.level }}
                        </h3>
                        <BaseTag
                            v-if="showStatusBadge"
                            :title="statusLabel"
                            :variant="statusVariant"
                            classes="cursor-default"
                        />
                    </div>
                    <p
                        v-if="subtitle"
                        class="mt-1 text-[11px] font-medium text-muted-foreground sm:text-xs"
                    >
                        {{ subtitle }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <OfferLetterAnchor
                        v-if="hasOfferLetter(application, activeIntakePeriodIds)"
                        :student-application-id="String(application.id)"
                    />
                    <BaseButton
                        v-if="canEdit && editUrl"
                        :title="$t('trans.edit')"
                        :size="ButtonSize.xs"
                        classes="rounded-full"
                        :variant="ColorVariant.shade"
                        @click="() => navigateTo(editUrl)"
                    >
                        <BaseIcon :name="IconName.edit" :color="ColorVariant.shade" />
                    </BaseButton>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col gap-3 px-3 py-3 sm:px-4"
            :class="compact ? 'text-xs' : 'text-sm'"
        >
            <div
                v-if="showMetadata"
                class="grid grid-cols-1 gap-2 sm:grid-cols-2"
            >
                <InfoCard
                    v-if="applicationDate"
                    :label="$tChoice('trans.application_date', 1)"
                    :value="applicationDate"
                    :icon="IconName.calendar"
                />
                <InfoCard
                    v-if="modeOfStudy"
                    :label="$tChoice('trans.mode_of_study', 1)"
                    :value="modeOfStudy"
                    :icon="IconName.bookmark"
                />
            </div>

            <BaseAlert
                v-if="guidanceMessage"
                :description="guidanceMessage"
                :type="alertType"
            />
        </div>
    </div>
</template>
