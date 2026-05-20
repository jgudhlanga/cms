<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import {
    formatDurationHours,
    semesterDurationHours,
    semesterTitle,
    statusBadgeClass,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeModuleRow from '@/pages/students/components/profile/programs/ProgrammeModuleRow.vue';
import ProgrammeStatCard from '@/pages/students/components/profile/programs/ProgrammeStatCard.vue';
import type { StudentProgrammeSemester } from '@/types/students';
import { CalendarDays } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    semester: StudentProgrammeSemester;
}

defineProps<Props>();

const openMap = ref<Record<number, boolean>>({});

const toggleModule = (index: number) => {
    openMap.value[index] = !openMap.value[index];
};

const isModuleOpen = (index: number) => !!openMap.value[index];
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 pb-4 pt-5">
            <div class="flex min-w-0 items-center gap-3">
                <CalendarDays
                    class="h-4 w-4 shrink-0 text-slate-400"
                    stroke-width="1.75"
                />
                <h2 class="truncate text-base font-bold tracking-tight text-slate-800">
                    {{ semesterTitle(semester.label, semester.year) }}
                </h2>
            </div>
            <span
                v-if="semester.status"
                :class="statusBadgeClass(semester.status)"
                class="shrink-0 rounded-full px-2.5 py-1 text-[0.8rem] font-bold tracking-wide"
            >
                {{ semester.status }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-2">
            <ProgrammeStatCard
                label-key="students.duration_this_semester"
                :value="formatDurationHours(semesterDurationHours(semester))"
            />
            <ProgrammeStatCard
                label-key="students.modules_count"
                :value="semester.module.length"
            />
        </div>

        <div
            v-if="semester.module.length === 0"
            class="border-t border-slate-100 px-5 py-8"
        >
            <Empty :message="$t('students.no_modules')" />
            <p class="mt-2 text-center text-sm text-slate-400">
                {{ $t('students.no_modules_hint') }}
            </p>
        </div>

        <div
            v-else
            class="divide-y divide-slate-100 border-t border-slate-100"
        >
            <ProgrammeModuleRow
                v-for="(module, moduleIndex) in semester.module"
                :key="`${semester.id}-${module.code ?? moduleIndex}`"
                :module="module"
                :open="isModuleOpen(moduleIndex)"
                @toggle="toggleModule(moduleIndex)"
            />
        </div>
    </div>
</template>
