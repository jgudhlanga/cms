<script setup lang="ts">
import {
    displayValue,
    formatDurationHours,
    gradeBadgeClass,
    moduleGradeDisplay,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeModuleDetails from '@/pages/students/components/profile/programs/ProgrammeModuleDetails.vue';
import type { StudentProgrammeModule } from '@/types/students';
import { ChevronDown } from 'lucide-vue-next';

interface Props {
    module: StudentProgrammeModule;
    open: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <div>
        <button
            type="button"
            class="group flex w-full items-center gap-4 px-5 py-4 text-left transition-colors duration-150 hover:bg-muted/50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
            :class="{ 'bg-muted/40 ring-2 ring-inset ring-border': open }"
            @click="emit('toggle')"
        >
            <span class="w-16 shrink-0 font-mono text-[0.72rem] font-semibold tracking-wide text-muted-foreground">
                {{ displayValue(module.code) }}
            </span>
            <span class="flex-1 text-[0.93rem] font-bold tracking-tight text-foreground">
                {{ displayValue(module.name) }}
            </span>
            <span class="shrink-0 text-[0.8rem] font-medium text-muted-foreground">
                {{ formatDurationHours(module.durationInHours) }}
            </span>
            <span
                :class="gradeBadgeClass(module.grade)"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[0.8rem] font-bold"
            >
                {{ moduleGradeDisplay(module) }}
            </span>
            <ChevronDown
                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <ProgrammeModuleDetails
                v-if="open"
                :module="module"
            />
        </Transition>
    </div>
</template>
