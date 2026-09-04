<script setup lang="ts">
import type { CoursePathway, CoursePathwayStep, CoursePathwayStage } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    pathway: CoursePathway;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    selectStage: [applicationId: string | number];
}>();

const percent = computed(() => {
    if (props.pathway.stepsTotal > 0) {
        return Math.min(100, Math.round((props.pathway.stepsCompleted / props.pathway.stepsTotal) * 100));
    }

    if (props.pathway.yearsTotal > 0) {
        return Math.min(100, Math.round((props.pathway.yearsCompleted / props.pathway.yearsTotal) * 100));
    }

    return 0;
});

const yearsLabel = computed(
    () => `${props.pathway.yearsCompleted.toFixed(1)} / ${props.pathway.yearsTotal.toFixed(1)}`,
);

const stepsLabel = computed(() => `${props.pathway.stepsCompleted} / ${props.pathway.stepsTotal}`);

const stepMarkerClass = (step: CoursePathwayStep): string => {
    const attachment = step.kind === 'industrial_attachment';
    const shape = attachment ? 'rounded-sm' : 'rounded-full';

    return {
        completed: `${shape} bg-emerald-500 text-white`,
        current: `${shape} bg-primary ring-2 ring-primary/30 ring-offset-1 ring-offset-card`,
        blocked: `${shape} bg-amber-500`,
        locked: `${shape} bg-muted-foreground/25`,
    }[step.state];
};

const stageChipClass = (stage: CoursePathwayStage): string => {
    if (stage.status === 'completed') {
        return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (stage.status === 'current') {
        return 'border-primary/40 bg-primary/10 text-primary';
    }

    return 'border-border bg-muted/40 text-muted-foreground';
};

const onStageClick = (stage: CoursePathwayStage): void => {
    if (stage.studentApplicationId) {
        emit('selectStage', stage.studentApplicationId);
    }
};

// The prior-level / missing-structure note lives in the chip tooltip so it costs no height.
const stageNote = (stage: CoursePathwayStage): string | undefined => {
    if (stage.impliedComplete) {
        return trans('students.pathway_implied_complete');
    }

    if (stage.structureMissing) {
        return trans('students.pathway_structure_missing');
    }

    return undefined;
};
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-border bg-card px-4 py-3">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <h3 class="min-w-0 text-sm font-semibold tracking-tight text-foreground">
                <span class="mr-2 text-[0.65rem] font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $t('students.course_pathway') }}
                </span>
                {{ pathway.course ?? $t('students.program') }}
            </h3>
            <p class="text-[0.7rem] text-muted-foreground">
                <span class="font-semibold tabular-nums text-foreground">{{ yearsLabel }}</span>
                {{ $t('students.pathway_years') }}
                <span aria-hidden="true" class="px-1">·</span>
                <span class="font-semibold tabular-nums text-foreground">{{ stepsLabel }}</span>
                {{ $t('students.pathway_steps') }}
            </p>
        </div>

        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full rounded-full bg-primary transition-[width] duration-500"
                :style="{ width: `${percent}%` }"
            />
        </div>

        <div class="mt-3 flex flex-col gap-2">
            <div
                v-for="stage in pathway.stages"
                :key="stage.departmentLevelId"
                class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2"
            >
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[0.7rem] font-medium uppercase tracking-wide"
                    :class="stageChipClass(stage)"
                    :disabled="!stage.studentApplicationId"
                    :title="stageNote(stage)"
                    @click="onStageClick(stage)"
                >
                    <Check
                        v-if="stage.status === 'completed'"
                        class="h-3 w-3"
                        stroke-width="3"
                        aria-hidden="true"
                    />
                    {{ stage.levelName }}
                    <span v-if="stage.impliedComplete">*</span>
                </button>

                <div
                    v-for="step in stage.steps"
                    :key="step.programmeSemesterId"
                    class="flex items-center gap-1.5"
                >
                    <span
                        class="flex h-3.5 w-3.5 shrink-0 items-center justify-center"
                        :class="stepMarkerClass(step)"
                    >
                        <Check
                            v-if="step.state === 'completed'"
                            class="h-2.5 w-2.5"
                            stroke-width="3"
                            aria-hidden="true"
                        />
                    </span>
                    <span
                        class="text-[0.7rem] whitespace-nowrap"
                        :class="step.state === 'locked' ? 'text-muted-foreground' : 'text-foreground'"
                        :title="step.levelName ? `${step.levelName} ${step.name}` : step.name"
                    >
                        {{ step.shortName || step.name }}
                    </span>
                </div>

                <span v-if="!stage.steps.length && stage.structureMissing" class="text-[0.7rem] text-amber-600 dark:text-amber-400">
                    {{ $t('students.pathway_structure_missing') }}
                </span>
            </div>
        </div>
    </section>
</template>
