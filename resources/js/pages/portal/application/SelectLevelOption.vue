<script setup lang="ts">
import PortalApplicationStepper from '@/components/portal/PortalApplicationStepper.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import StudentPageHeader from '@/components/shared/students/StudentPageHeader.vue';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod, Level } from '@/types/institution';
import { ref } from 'vue';

interface Props {
    levels: Level[];
    intakePeriods?: IntakePeriod[];
    requiresIntakeSelection?: boolean;
    applicationStep?: 'level' | 'fee' | 'apply';
    auth: AuthObject;
    errors: object;
}

const props = withDefaults(defineProps<Props>(), {
    requiresIntakeSelection: false,
    applicationStep: 'level',
    intakePeriods: () => [],
});

const selectedIntakePeriodId = ref<number | null>(
    props.intakePeriods.length === 1 ? Number(props.intakePeriods[0].id) : null,
);

const { selectLevel } = useStudentPortal();

const onApply = (levelId: string, hasApplicationFeePayment: boolean) => {
    selectLevel(levelId, selectedIntakePeriodId.value, props.requiresIntakeSelection);
};
</script>
<template>
    <StudentPageHeader />
    <PortalApplicationStepper :current-step="applicationStep" />
    <div class="mt-8 flex w-full flex-col items-center justify-center bg-background px-5 pb-12 text-foreground">
        <div v-if="requiresIntakeSelection && intakePeriods.length > 1" class="mb-8 w-full max-w-md">
            <IntakePeriodComboSelect
                v-model="selectedIntakePeriodId"
                :data="intakePeriods"
                :is-required="true"
            />
        </div>

        <div class="mb-10 grid w-full max-w-6xl grid-cols-1 gap-6 md:grid-cols-4">
            <div
                v-for="(level, index) in levels"
                :key="level.id"
                class="card-hover fade-in overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-xl dark:shadow-sm"
                :style="{ 'animation-delay': Number(level.id) * 0.1 + 's' }"
            >
                <div class="relative">
                    <div class="absolute top-4 right-4 z-10 flex flex-col items-end gap-2">
                        <span
                            class="rounded-full bg-card/95 px-3 py-1 text-sm font-bold text-foreground shadow backdrop-blur-sm dark:bg-card/90"
                        >
                            #{{ Number(index) + 1 }}
                        </span>
                        <span
                            v-if="level.attributes.hasApplicationFeePayment"
                            class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ $t('trans.application_fee_required_badge') }}
                        </span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-foreground">{{ level.attributes.name }}</h3>
                        <p class="mt-1 text-xs text-muted-foreground">{{ level.attributes.description }}</p>
                    </div>
                </div>

                <div class="border-t border-border bg-muted px-6 py-4">
                    <div class="flex items-center justify-center">
                        <button
                            type="button"
                            @click="() => onApply(String(level.id), !!level.attributes.hasApplicationFeePayment)"
                            class="apply-button"
                            :disabled="!level.attributes.showOnCurrentApplicationPeriod"
                            :class="!level.attributes.showOnCurrentApplicationPeriod ? 'cursor-not-allowed opacity-50' : ''"
                        >
                            {{ $t('general.apply_now') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.apply-button {
    display: inline-flex;
    width: 100%;
    padding: 10px;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.dark .apply-button {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
}
</style>
