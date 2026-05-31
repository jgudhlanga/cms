<script setup lang="ts">
import {
    displayValue,
    formatDurationHours,
    isMissingValue,
    missingDisplayTextClass,
    moduleGradeBadgeClass,
    moduleGradeDisplay,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeModuleDetails from '@/pages/students/components/profile/programs/ProgrammeModuleDetails.vue';
import type { StudentProgrammeModule } from '@/types/students';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    module: StudentProgrammeModule;
    open: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    toggle: [];
}>();

const code = computed(() => displayValue(props.module.code));
const name = computed(() => displayValue(props.module.name));
const duration = computed(() => formatDurationHours(props.module.durationInHours));
const grade = computed(() => moduleGradeDisplay(props.module));
const durationMissing = computed(() => !props.module.durationInHours || Number(props.module.durationInHours) <= 0);
</script>

<template>
    <div>
        <button
            type="button"
            class="group flex w-full min-w-0 items-center gap-1.5 px-2 py-1.5 text-left text-xs transition-colors hover:bg-muted/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring sm:gap-2 sm:px-3"
            :class="{ 'bg-muted/25': open }"
            @click="emit('toggle')"
        >
            <span
                class="inline-flex shrink-0 items-center rounded-full border px-1.5 py-0.5 font-mono text-[10px] font-medium shadow-sm sm:px-2"
                :class="isMissingValue(module.code)
                    ? 'border-amber-500/30 bg-amber-500/15 text-amber-500'
                    : 'border-sky-500/30 bg-sky-500/15 text-sky-400'"
            >
                {{ code }}
            </span>
            <span class="hidden shrink-0 text-muted-foreground sm:inline">·</span>
            <span
                class="min-w-0 flex-1 wrap-break-word font-medium leading-snug sm:truncate"
                :class="isMissingValue(module.name) ? 'text-amber-500' : 'text-foreground'"
            >
                {{ name }}
            </span>
            <span
                class="hidden shrink-0 tabular-nums sm:inline"
                :class="durationMissing ? 'text-amber-500' : 'text-muted-foreground'"
            >
                {{ duration }}
            </span>
            <span
                :class="[moduleGradeBadgeClass(module), missingDisplayTextClass(grade)]"
                class="hidden shrink-0 min-w-7 text-right font-semibold tabular-nums sm:inline"
                :title="module.courseWork?.aggregation.courseWorkTotal60 != null ? $t('academic_calendar.course_work_total_60') : undefined"
            >
                {{ grade }}
            </span>
            <span
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-muted-foreground shadow-sm transition-all duration-200 group-hover:border-border group-hover:bg-accent group-hover:text-accent-foreground group-hover:shadow"
                aria-hidden="true"
            >
                <ChevronDown
                    class="h-3 w-3 transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    stroke-width="2.25"
                />
            </span>
        </button>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <ProgrammeModuleDetails
                v-if="open"
                :module="module"
            />
        </Transition>
    </div>
</template>
