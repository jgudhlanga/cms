<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import {
    semesterHeaderMeta,
    statusBadgeClass,
} from '@/composables/students/studentProgrammeDisplay';
import ProgrammeModuleRow from '@/pages/students/components/profile/programs/ProgrammeModuleRow.vue';
import type { StudentProgrammeSemester } from '@/types/students';
import { CalendarDays } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    semester: StudentProgrammeSemester;
    expandModulesWithMarks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    expandModulesWithMarks: false,
});

const header = computed(() => semesterHeaderMeta(props.semester));

const openMap = ref<Record<number, boolean>>({});

const toggleModule = (index: number) => {
    openMap.value[index] = !openMap.value[index];
};

const isModuleOpen = (index: number) => !!openMap.value[index];

const moduleHasMarks = (index: number): boolean => {
    const module = props.semester.module[index];

    return module?.courseWork?.assessments.some((assessment) => assessment.mark !== null) ?? false;
};

const initializeOpenModules = (): void => {
    if (!props.expandModulesWithMarks) {
        return;
    }

    props.semester.module.forEach((_, index) => {
        if (moduleHasMarks(index)) {
            openMap.value[index] = true;
        }
    });
};

onMounted(initializeOpenModules);

watch(
    () => props.semester,
    () => {
        openMap.value = {};
        initializeOpenModules();
    },
    { deep: true },
);
</script>

<template>
    <div class="overflow-hidden rounded border border-border bg-card">
        <div class="flex min-w-0 items-start justify-between gap-2 border-b border-border px-2 py-1.5 sm:items-center sm:px-3">
            <div class="flex min-w-0 flex-1 items-start gap-1.5 sm:items-center sm:gap-2">
                <CalendarDays
                    class="mt-0.5 h-3 w-3 shrink-0 text-muted-foreground sm:mt-0"
                    stroke-width="1.75"
                />
                <p class="min-w-0 wrap-break-word text-xs leading-snug text-foreground sm:truncate sm:leading-tight">
                    <span
                        class="font-semibold"
                        :class="{ 'text-amber-500': header.labelMissing }"
                    >
                        {{ header.label }}
                    </span>
                    <span class="text-muted-foreground"> {{ $t('students.semester_title_separator') }} </span>
                    <span :class="{ 'text-amber-500': header.yearMissing }">
                        {{ header.year }}
                    </span>
                    <span class="text-muted-foreground">
                        (
                        <span :class="{ 'text-amber-500': header.moduleCount === 0 }">
                            {{ header.moduleCount }} {{ $t('students.modules_count') }}
                        </span>
                        <template v-if="!header.durationMissing">
                            <span> · </span>
                            <span>{{ header.duration }}</span>
                        </template>
                        )
                    </span>
                </p>
            </div>
            <span
                v-if="semester.status"
                :class="statusBadgeClass(semester.status)"
                class="shrink-0 text-[0.65rem] uppercase tracking-wide"
            >
                {{ semester.status }}
            </span>
        </div>

        <div
            v-if="semester.module.length === 0"
            class="px-3 py-4"
        >
            <Empty :message="$t('students.no_modules')" />
            <p class="mt-1 text-center text-xs text-muted-foreground">
                {{ $t('students.no_modules_hint') }}
            </p>
        </div>

        <div
            v-else
            class="divide-y divide-border"
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
