<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/core/useInitials';
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
const { getInitials } = useInitials();

const avatarSrc = computed(() => {
    const avatarUrl = headerData.value.avatarUrl;

    if (typeof avatarUrl === 'object' && avatarUrl !== null && 'thumb' in avatarUrl) {
        return (avatarUrl as { thumb?: string }).thumb ?? '';
    }

    return typeof avatarUrl === 'string' ? avatarUrl : '';
});

const liveDateLabel = computed(() => {
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        timeZone: userTimeZone,
    })
        .format(new Date())
        .toUpperCase();
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
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card px-4 py-3 shadow-sm sm:px-5">
        <div class="flex w-full min-w-0 items-start gap-3">
            <Avatar class="size-9 shrink-0">
                <AvatarImage :src="avatarSrc" :alt="headerData.studentName" />
                <AvatarFallback class="bg-violet-600 text-xs font-bold text-white">
                    {{ getInitials(headerData.studentName) }}
                </AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 basis-0">
                <div class="flex min-w-0 items-center justify-between gap-3">
                    <p class="flex min-w-0 flex-1 items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-500 dark:text-emerald-400">
                        <span class="inline-block size-1.5 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                        <span class="truncate">{{ $t('students.dashboard_live') }} · {{ liveDateLabel }}</span>
                    </p>
                    <span
                        v-if="headerData.studentNumber"
                        class="shrink-0 rounded-md bg-muted px-2 py-0.5 font-mono text-[11px] text-foreground"
                    >
                        {{ headerData.studentNumber }}
                    </span>
                </div>

                <h1 class="mt-0.5 wrap-break-word text-lg font-bold leading-tight tracking-tight text-foreground sm:text-xl">
                    {{ greeting }}, {{ headerData.studentName }}
                </h1>

                <div
                    v-if="levelCourseDisplay || headerData.enrolmentStatus"
                    class="mt-0.5 flex min-w-0 flex-wrap items-center gap-2"
                >
                    <p
                        v-if="levelCourseDisplay"
                        class="wrap-break-word text-sm text-muted-foreground"
                    >
                        {{ levelCourseDisplay }}
                    </p>
                    <span
                        v-if="headerData.enrolmentStatus"
                        class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-500/30 bg-emerald-500/15 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <span class="inline-block size-1.5 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                        {{ headerData.enrolmentStatus }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>
