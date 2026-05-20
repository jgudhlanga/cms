<script setup lang="ts">
import {
    displayValue,
    scoreBarColor,
    scoreLabel,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeDetailField from '@/pages/students/components/profile/programs/ProgrammeDetailField.vue';
import type { StudentProgrammeModule } from '@/types/students';

interface Props {
    module: StudentProgrammeModule;
}

defineProps<Props>();
</script>

<template>
    <div class="overflow-hidden border-t border-slate-100 bg-slate-50/70">
        <div class="grid grid-cols-2 gap-4 px-5 pb-4 pt-3 sm:grid-cols-4">
            <ProgrammeDetailField
                label-key="students.lecturer"
                :value="displayValue(module.lecturer)"
            />
            <ProgrammeDetailField
                label-key="students.module_type"
                :value="displayValue(module.type)"
            />
            <ProgrammeDetailField
                label-key="students.assessment"
                :value="displayValue(module.assessment)"
            />
            <ProgrammeDetailField
                label-key="students.score"
                value=""
            >
                {{
                    module.score !== null
                        ? `${module.score}%`
                        : $t('students.pending')
                }}
            </ProgrammeDetailField>
        </div>
        <div
            v-if="module.score !== null"
            class="px-5 pb-4"
        >
            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="scoreBarColor(module.score)"
                    :style="{ width: `${module.score}%` }"
                />
            </div>
            <p class="mt-1 text-[0.65rem] text-slate-400">
                {{ scoreLabel(module.score) }}
            </p>
        </div>
    </div>
</template>
