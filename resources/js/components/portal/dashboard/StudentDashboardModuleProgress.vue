<script setup lang="ts">
import { useStudentPortalDashboard } from '@/composables/students/useStudentPortalDashboard';
import { useUtils } from '@/composables/core/useUtils';
import type { StudentPortalDashboardModule } from '@/types/students';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    modules: StudentPortalDashboardModule[];
}

defineProps<Props>();

const { scoreBarColor } = useStudentPortalDashboard();
const { navigateTo } = useUtils();

const moduleAccent = (index: number): string => {
    const accents = [
        'bg-emerald-400',
        'bg-blue-400',
        'bg-amber-400',
        'bg-red-400',
        'bg-purple-400',
        'bg-cyan-400',
    ];

    return accents[index % accents.length];
};

const dotAccent = (index: number): string => {
    const accents = [
        'bg-emerald-500',
        'bg-blue-500',
        'bg-amber-500',
        'bg-red-500',
        'bg-purple-500',
        'bg-cyan-500',
    ];

    return accents[index % accents.length];
};
</script>

<template>
    <section class="w-full min-w-0 rounded-lg border border-border bg-card px-3 py-2.5 shadow-sm">
        <div class="mb-2 flex min-w-0 flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold leading-none text-foreground">
                    {{ $t('students.dashboard_course_work') }}
                </h2>
                <p class="mt-0.5 wrap-break-word text-[11px] text-muted-foreground">
                    {{ $t('students.dashboard_course_work_description') }}
                </p>
            </div>
            <button
                type="button"
                class="inline-flex shrink-0 items-center gap-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary hover:underline"
                @click="navigateTo(route('portal.profile.programs'))"
            >
                {{ $t('students.dashboard_view_all') }}
                <ArrowRight class="h-3 w-3" />
            </button>
        </div>

        <div
            v-if="modules.length === 0"
            class="rounded-md border border-dashed border-border py-4 text-center text-xs text-muted-foreground"
        >
            {{ $t('students.no_modules') }}
        </div>

        <ul
            v-else
            class="space-y-2.5"
        >
            <li
                v-for="(module, index) in modules"
                :key="module.id"
                class="space-y-1"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-1.5">
                        <span
                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="dotAccent(index)"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="wrap-break-word text-xs font-medium leading-snug text-foreground sm:truncate">
                                {{ module.name }}
                            </p>
                            <p class="text-[10px] text-muted-foreground">
                                {{ module.code }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs font-semibold text-foreground">
                            {{ module.gradeDisplay }}
                        </p>
                        <p
                            v-if="module.score !== null"
                            class="text-[10px] text-muted-foreground"
                        >
                            {{ module.score }}%
                        </p>
                    </div>
                </div>
                <div class="h-1 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="module.score !== null ? scoreBarColor(module.score) : moduleAccent(index)"
                        :style="{ width: `${Math.max(module.progressPercent, module.score !== null ? 4 : 0)}%` }"
                    />
                </div>
            </li>
        </ul>
    </section>
</template>
