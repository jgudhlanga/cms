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
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card px-4 py-4 shadow-sm sm:px-6 sm:py-5">
        <div class="flex w-full min-w-0 items-start gap-4">
            <Avatar class="size-14 shrink-0 sm:size-16">
                <AvatarImage :src="avatarSrc" :alt="headerData.studentName" />
                <AvatarFallback class="bg-violet-600 text-base font-bold text-white sm:text-lg">
                    {{ getInitials(headerData.studentName) }}
                </AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-500 dark:text-emerald-400">
                    <span class="inline-block size-1.5 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                    {{ $t('students.dashboard_live') }} · {{ liveDateLabel }}
                </p>
                <h1 class="mt-0.5 wrap-break-word text-xl font-bold leading-tight tracking-tight text-foreground sm:text-2xl">
                    {{ greeting }}, {{ headerData.studentName }}
                </h1>
                <p
                    v-if="levelCourseDisplay"
                    class="mt-0.5 wrap-break-word text-sm text-muted-foreground"
                >
                    {{ levelCourseDisplay }}
                </p>

                <div class="mt-3 flex min-w-0 flex-wrap items-center gap-2">
                    <span
                        v-if="headerData.studentNumber"
                        class="rounded-md bg-muted px-2 py-1 font-mono text-xs text-foreground"
                    >
                        {{ headerData.studentNumber }}
                    </span>
                    <span
                        v-if="headerData.enrolmentStatus"
                        class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/30 bg-emerald-500/15 px-2 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <span class="inline-block size-1.5 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400" />
                        {{ headerData.enrolmentStatus }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>
