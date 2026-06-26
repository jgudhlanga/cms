<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { usePortalLevelSelection } from '@/composables/students/usePortalLevelSelection';
import { TypeVariant } from '@/enums/type-variants';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod, Level } from '@/types/institution';
import { computed, ref } from 'vue';

type AvailabilityIssue = 'no_open_levels' | 'no_active_intakes' | null;

interface Props {
    levels: Level[] | { data: Level[] };
    intakePeriods?: IntakePeriod[];
    requiresIntakeSelection?: boolean;
    applicationStep?: 'level' | 'fee' | 'apply';
    openLevelCount?: number;
    hasActiveIntakes?: boolean;
    availabilityIssue?: AvailabilityIssue;
    auth: AuthObject;
    errors: object;
}

const props = withDefaults(defineProps<Props>(), {
    requiresIntakeSelection: false,
    applicationStep: 'level',
    intakePeriods: () => [],
    hasActiveIntakes: true,
    availabilityIssue: null,
});

const levelList = computed(() => {
    if (Array.isArray(props.levels)) {
        return props.levels;
    }

    return Array.isArray(props.levels?.data) ? props.levels.data : [];
});

const hasLevels = computed(() => levelList.value.length > 0);
const showNoLevelsAlert = computed(() => props.availabilityIssue === 'no_open_levels' || !hasLevels.value);
const showNoIntakesAlert = computed(() => props.availabilityIssue === 'no_active_intakes');
const canSelectLevel = computed(() => hasLevels.value && props.hasActiveIntakes);

const selectedIntakePeriodId = ref<number | null>(
    props.intakePeriods.length === 1 ? Number(props.intakePeriods[0].id) : null,
);

const { selectLevel } = usePortalLevelSelection();

const displayedIntakeName = computed(() => {
    if (selectedIntakePeriodId.value) {
        const selected = props.intakePeriods.find((period) => Number(period.id) === selectedIntakePeriodId.value);

        return selected?.attributes?.name ?? null;
    }

    if (props.intakePeriods.length === 1) {
        return props.intakePeriods[0].attributes?.name ?? null;
    }

    return null;
});

const onApply = (levelId: string) => {
    selectLevel(levelId, selectedIntakePeriodId.value, props.requiresIntakeSelection);
};
</script>
<template>
    <PortalApplicationShell wide :intake-name="displayedIntakeName">
        <div class="flex w-full flex-col items-center justify-center px-5 pb-12">
        <div class="mb-6 w-full text-center">
            <h1 class="text-xl font-semibold text-foreground">
                {{ $t('trans.portal_application_step_level') }}
            </h1>
            <p v-if="showNoLevelsAlert" class="mt-2 text-sm text-muted-foreground">
                {{ $t('trans.portal_no_levels_available_description') }}
            </p>
            <p v-else-if="showNoIntakesAlert" class="mt-2 text-sm text-muted-foreground">
                {{ $t('trans.portal_no_active_intakes_description') }}
            </p>
        </div>

        <div v-if="requiresIntakeSelection && intakePeriods.length > 1" class="mb-8 w-full max-w-md">
            <IntakePeriodComboSelect
                v-model="selectedIntakePeriodId"
                :data="intakePeriods"
                :is-required="true"
            />
        </div>

        <BaseAlert
            v-if="showNoLevelsAlert"
            class="mb-6 w-full max-w-2xl"
            :title="$t('trans.portal_no_levels_available')"
            :description="$t('trans.portal_no_levels_available_description')"
            :type="TypeVariant.warning"
        />

        <BaseAlert
            v-if="showNoIntakesAlert"
            class="mb-6 w-full max-w-2xl"
            :title="$t('trans.portal_no_active_intakes')"
            :description="$t('trans.portal_no_active_intakes_description')"
            :type="TypeVariant.warning"
        />

        <div v-if="canSelectLevel" class="mb-10 grid w-full grid-cols-1 gap-6 md:grid-cols-3">
            <div
                v-for="(level, index) in levelList"
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
                            @click="() => onApply(String(level.id))"
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
    </PortalApplicationShell>
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
