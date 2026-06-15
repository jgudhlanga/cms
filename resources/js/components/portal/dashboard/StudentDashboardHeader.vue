<script setup lang="ts">
import { useInjectedStudentPortalDashboard } from '@/composables/students/useStudentPortalDashboard';
import { useStudentProfileHeader } from '@/composables/students/useStudentProfileHeader';
import type { Student } from '@/types/students';
import { computed } from 'vue';

interface Props {
    student: Student;
}

const props = defineProps<Props>();

const { headerData } = useStudentProfileHeader(() => props.student);
const { greeting, userTimeZone } = useInjectedStudentPortalDashboard();

const liveDateLabel = computed(() => {
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        timeZone: userTimeZone,
    }).format(new Date());
});

const levelCourseDisplay = computed(() => {
    const level = headerData.value.level;
    const course = headerData.value.course;

    if (level && course) {
        return `${level} · ${course}`;
    }

    return level || course || '';
});
</script>

<template>
    <section class="w-full min-w-0 rounded-lg border border-border bg-card px-3 py-2.5 shadow-sm">
        <div class="flex w-full min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-500 dark:text-emerald-400">
                    {{ $t('students.dashboard_live') }} · {{ liveDateLabel }}
                </p>
                <h1 class="wrap-break-word text-lg font-bold leading-tight tracking-tight text-foreground">
                    {{ greeting }},
                    <span class="text-primary">{{ headerData.studentName }}</span>
                </h1>
                <p
                    v-if="levelCourseDisplay"
                    class="wrap-break-word text-xs text-muted-foreground"
                >
                    {{ levelCourseDisplay }}
                </p>
            </div>
            <div class="flex w-full min-w-0 flex-wrap items-center gap-1.5 sm:w-auto sm:justify-end">
                <span
                    v-if="headerData.studentNumber"
                    class="rounded bg-muted px-1.5 py-0.5 font-mono text-[10px] text-foreground"
                >
                    {{ headerData.studentNumber }}
                </span>
                <span
                    v-if="headerData.enrolmentStatus"
                    class="rounded border border-emerald-500/30 bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    {{ headerData.enrolmentStatus }}
                </span>
            </div>
        </div>
    </section>
</template>
