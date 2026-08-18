<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import StudentCourseWorkModuleRow from '@/components/students/course-work/StudentCourseWorkModuleRow.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import {
    mapProgrammeModuleToListItem,
    semesterHeaderMeta,
    statusBadgeClass,
} from '@/composables/students/studentProgrammeDisplay';
import { errorAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { hasAbility } from '@/lib/permissions';
import ProgrammeModuleDetails from '@/pages/students/components/profile/programs/ProgrammeModuleDetails.vue';
import type { StudentProgrammeSemester } from '@/types/students';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { CalendarDays } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Props {
    semester: StudentProgrammeSemester;
    studentId: string | number;
    expandModulesWithMarks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    expandModulesWithMarks: false,
});

const emit = defineEmits<{
    statusUpdated: [];
}>();

const header = computed(() => semesterHeaderMeta(props.semester));
const canUpdateStatus = computed(() => hasAbility(['update:students']) && Boolean(props.semester.studentEnrolmentId));
const { open: openConfirmDialog } = useCustomConfirmDialog();

const openMap = ref<Record<number, boolean>>({});

const toggleModule = (index: number) => {
    openMap.value[index] = !openMap.value[index];
};

const isModuleOpen = (index: number) => !!openMap.value[index];

const moduleHasMarks = (index: number): boolean => {
    const module = props.semester.module[index];

    return module?.courseWork?.assessments?.some((assessment) => assessment.mark !== null) ?? false;
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

const updateStatus = async (status: string, message: string): Promise<void> => {
    if (!props.semester.studentEnrolmentId) {
        return;
    }

    const confirmed = await openConfirmDialog({
        title: trans('students.enrolment_status_confirm_title'),
        message,
        confirmText: trans('trans.save'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('students.enrolments.status.update', {
            student: String(props.studentId),
            student_enrolment: String(props.semester.studentEnrolmentId),
        }),
        { status },
        {
            preserveScroll: true,
            onSuccess: () => emit('statusUpdated'),
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('students.enrolment_status_invalid')));
            },
        },
    );
};
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
            <div class="flex shrink-0 flex-col items-end gap-1">
                <span
                    v-if="semester.status"
                    :class="statusBadgeClass(semester.status)"
                    class="text-[0.65rem] uppercase tracking-wide"
                >
                    {{ semester.status }}
                </span>
                <div v-if="canUpdateStatus" class="flex flex-wrap justify-end gap-1">
                    <button
                        type="button"
                        class="text-[0.65rem] text-primary underline-offset-2 hover:underline"
                        @click="updateStatus('repeatre-write', $t('students.enrolment_status_confirm_repeat'))"
                    >
                        {{ $t('students.enrolment_repeat') }}
                    </button>
                    <button
                        type="button"
                        class="text-[0.65rem] text-primary underline-offset-2 hover:underline"
                        @click="updateStatus('deferredpostponed', $t('students.enrolment_status_confirm_deferred'))"
                    >
                        {{ $t('students.enrolment_deferred') }}
                    </button>
                    <button
                        v-if="semester.canCompleteLevel"
                        type="button"
                        class="text-[0.65rem] text-primary underline-offset-2 hover:underline"
                        @click="updateStatus('completed', $t('students.enrolment_status_confirm_completed'))"
                    >
                        {{ $t('students.enrolment_mark_completed') }}
                    </button>
                </div>
            </div>
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
            <div
                v-for="(module, moduleIndex) in semester.module"
                :key="`${semester.id}-${module.code ?? moduleIndex}`"
                class="min-w-0"
            >
                <StudentCourseWorkModuleRow
                    :module="mapProgrammeModuleToListItem(module, `${semester.id}-${moduleIndex}`)"
                    :accent-index="moduleIndex"
                    mode="expandable"
                    :expanded="isModuleOpen(moduleIndex)"
                    @activate="toggleModule(moduleIndex)"
                />
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-[2000px] opacity-100"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="max-h-[2000px] opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <ProgrammeModuleDetails
                        v-if="isModuleOpen(moduleIndex)"
                        :module="module"
                    />
                </Transition>
            </div>
        </div>
    </div>
</template>
