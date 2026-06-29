<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseTag from '@/components/core/util/BaseTag.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { IntakePeriod } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export interface ApplicationHubProps {
    openIntakes: IntakePeriod[] | { data: IntakePeriod[] };
    canStartApplication: boolean;
    hasPaidApplicationFee: boolean;
    paidLevelId?: number | null;
    paidLevelName?: string | null;
    hasReapplyAcknowledgement: boolean;
    canContinueInClass: boolean;
    continueInClassUrl: string;
    requiresIntakeSelection: boolean;
}

interface Props {
    applicationHub: ApplicationHubProps;
    existingApplicationIntakeIds?: Array<string | number>;
    highlightFeePaid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    existingApplicationIntakeIds: () => [],
    highlightFeePaid: false,
});

const { navigateTo } = useUtils();

const intakeList = computed(() => {
    const raw = props.applicationHub.openIntakes;
    if (Array.isArray(raw)) {
        return raw;
    }

    return Array.isArray(raw?.data) ? raw.data : [];
});

const intakesWithoutApplication = computed(() => {
    const existingIds = new Set(props.existingApplicationIntakeIds.map((id) => String(id)));

    return intakeList.value.filter((intake) => !existingIds.has(String(intake.id)));
});

const showHub = computed(
    () => props.applicationHub.canStartApplication && intakesWithoutApplication.value.length > 0,
);

const selectedIntakeId = ref<number | null>(
    intakesWithoutApplication.value.length === 1
        ? Number(intakesWithoutApplication.value[0].id)
        : null,
);

const feePaidHighlight = ref(props.highlightFeePaid);

const acknowledgeForm = useForm({
    intake_period_id: null as number | null,
    acknowledged: false as boolean,
});

const submitAcknowledge = () => {
    const intakeId = selectedIntakeId.value ?? Number(intakesWithoutApplication.value[0]?.id ?? 0);
    acknowledgeForm.intake_period_id = intakeId;
    acknowledgeForm.post(route('portal.profile.applications.acknowledge'), {
        preserveScroll: true,
    });
};

const goToLevelSelection = () => {
    navigateTo(route('portal.profile.applications.level'));
};

const continueApplication = () => {
    navigateTo(route('portal.application.returning'));
};

const defaultOpenIds = computed(() =>
    intakesWithoutApplication.value.map((intake) => String(intake.id)),
);

const hubStep = computed(() => {
    if (props.applicationHub.hasPaidApplicationFee && props.applicationHub.hasReapplyAcknowledgement) {
        return 'continue';
    }

    if (props.applicationHub.hasReapplyAcknowledgement) {
        return 'level';
    }

    return 'acknowledge';
});
</script>

<template>
    <div v-if="showHub" class="mb-6">
        <BaseAccordion class="w-full" :default-value="defaultOpenIds">
            <BaseAccordionItem
                v-for="intake in intakesWithoutApplication"
                :key="intake.id"
                :value="String(intake.id)"
                :title="intake.attributes?.name ?? ''"
                :description="$t('trans.returning_student_hub_intake_description')"
            >
                <template #trigger-extra>
                    <BaseTag
                        :title="$t('students.current_intake')"
                        :variant="ColorVariant.success"
                        classes="cursor-default"
                    />
                </template>

                <div class="space-y-4 p-1">
                    <BaseAlert
                        v-if="feePaidHighlight && hubStep === 'continue'"
                        :type="TypeVariant.success"
                        :description="$t('trans.returning_student_hub_fee_paid')"
                    />

                    <template v-if="hubStep === 'acknowledge'">
                        <p class="text-sm text-muted-foreground">
                            {{ $t('trans.returning_student_onboarding_description') }}
                        </p>
                        <div
                            v-if="applicationHub.requiresIntakeSelection && intakesWithoutApplication.length > 1"
                            class="max-w-md"
                        >
                            <IntakePeriodComboSelect
                                v-model="selectedIntakeId"
                                :data="intakesWithoutApplication"
                                :is-required="true"
                            />
                        </div>
                        <label class="flex items-start gap-2 text-sm">
                            <input v-model="acknowledgeForm.acknowledged" type="checkbox" class="mt-1" />
                            <span>{{ $t('trans.returning_student_acknowledge_label') }}</span>
                        </label>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :disabled="!acknowledgeForm.acknowledged || (applicationHub.requiresIntakeSelection && !selectedIntakeId)"
                            :title="$t('trans.returning_student_hub_start')"
                            @click="submitAcknowledge"
                        />
                    </template>

                    <template v-else-if="hubStep === 'level'">
                        <p class="text-sm text-muted-foreground">
                            {{ $t('trans.returning_student_hub_select_level') }}
                        </p>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :title="$t('trans.returning_student_hub_go_select_level')"
                            @click="goToLevelSelection"
                        />
                    </template>

                    <template v-else>
                        <p class="text-sm text-muted-foreground">
                            {{
                                $t('trans.returning_student_reapply_banner', {
                                    intake: intake.attributes?.name ?? '',
                                })
                            }}
                        </p>
                        <p v-if="applicationHub.paidLevelName" class="text-sm text-foreground">
                            {{ $t('trans.returning_student_hub_level') }}: {{ applicationHub.paidLevelName }}
                        </p>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :class="{ 'ring-2 ring-primary ring-offset-2': feePaidHighlight }"
                            :title="$t('trans.returning_student_hub_continue')"
                            @click="continueApplication"
                        />
                    </template>
                </div>
            </BaseAccordionItem>
        </BaseAccordion>
    </div>
</template>
