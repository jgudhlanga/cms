<script setup lang="ts">
import type { CoursePathway, CoursePathwayStep, CoursePathwayStage } from '@/types/students';
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
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-border bg-card">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border px-4 py-3">
            <div>
                <p class="text-[0.65rem] font-medium uppercase tracking-wide text-muted-foreground">
                    {{ $t('students.course_pathway') }}
                </p>
                <h3 class="text-base font-semibold tracking-tight text-foreground">
                    {{ pathway.course ?? $t('students.program') }}
                </h3>
            </div>
            <div class="flex flex-wrap gap-4 text-right">
                <div>
                    <p class="text-[0.65rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('students.pathway_years') }}
                    </p>
                    <p class="text-sm font-semibold tabular-nums">{{ yearsLabel }}</p>
                </div>
                <div>
                    <p class="text-[0.65rem] uppercase tracking-wide text-muted-foreground">
                        {{ $t('students.pathway_steps') }}
                    </p>
                    <p class="text-sm font-semibold tabular-nums">{{ stepsLabel }}</p>
                </div>
            </div>
        </div>

        <div class="px-4 pt-3">
            <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-500"
                    :style="{ width: `${percent}%` }"
                />
            </div>
        </div>

        <div class="flex flex-wrap gap-2 px-4 py-3">
            <button
                v-for="stage in pathway.stages"
                :key="stage.departmentLevelId"
                type="button"
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[0.7rem] font-medium uppercase tracking-wide"
                :class="stageChipClass(stage)"
                :disabled="!stage.studentApplicationId"
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
        </div>

        <div class="flex flex-col gap-3 px-4 pb-4">
            <div
                v-for="stage in pathway.stages"
                :key="`steps-${stage.departmentLevelId}`"
                class="min-w-0"
            >
                <p class="mb-1.5 flex items-center gap-2 text-[0.7rem] text-muted-foreground">
                    <Check
                        v-if="stage.status === 'completed'"
                        class="h-3.5 w-3.5 text-emerald-500"
                        stroke-width="3"
                        aria-hidden="true"
                    />
                    <span class="font-medium text-foreground">{{ stage.levelName }}</span>
                    <span v-if="stage.impliedComplete">{{ $t('students.pathway_implied_complete') }}</span>
                    <span v-else-if="stage.structureMissing">{{ $t('students.pathway_structure_missing') }}</span>
                </p>
                <div v-if="stage.steps.length" class="flex flex-wrap items-center gap-x-3 gap-y-2">
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
                            class="text-[0.7rem]"
                            :class="step.state === 'locked' ? 'text-muted-foreground' : 'text-foreground'"
                        >
                            {{ step.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
