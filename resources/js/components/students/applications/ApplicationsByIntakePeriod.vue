<script setup lang="ts">
import { useApplicationsByIntakePeriod } from '@/composables/students/useApplicationsByIntakePeriod';
import CurrentApplications from '@/pages/portal/student/partials/CurrentApplications.vue';
import { ColorVariant } from '@/enums/colors';
import type { Enrolment } from '@/types/enrolments';
import { toRef } from 'vue';

interface Props {
    applications: Enrolment[];
    activeIntakePeriodIds?: Array<string | number>;
    canEdit?: boolean;
    compact?: boolean;
    editable?: boolean;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    activeIntakePeriodIds: () => [],
    canEdit: false,
    compact: false,
    editable: false,
    showHeader: false,
});

const { groups, defaultOpenIntakeIds, intakeGroupDescription } = useApplicationsByIntakePeriod(
    toRef(props, 'applications'),
    toRef(props, 'activeIntakePeriodIds'),
);

const isActiveIntakeGroup = (intakePeriodId: string): boolean =>
    props.activeIntakePeriodIds.some((id) => String(id) === intakePeriodId);
</script>

<template>
    <BaseAccordion
        v-if="applications.length > 0"
        class="w-full"
        :default-value="defaultOpenIntakeIds"
    >
        <BaseAccordionItem
            v-for="group in groups"
            :key="group.intakePeriodId"
            :value="group.intakePeriodId"
            :title="group.label"
            :description="intakeGroupDescription(group)"
        >
            <template #trigger-extra>
                <BaseTag
                    v-if="isActiveIntakeGroup(group.intakePeriodId)"
                    :title="$t('students.current_intake')"
                    :variant="ColorVariant.success"
                    classes="cursor-default"
                />
            </template>
            <CurrentApplications
                :applications="group.applications"
                :active-intake-period-ids="activeIntakePeriodIds"
                :can-edit="canEdit"
                :compact="compact"
                :editable="editable"
                :show-header="showHeader"
            />
        </BaseAccordionItem>
    </BaseAccordion>
</template>
