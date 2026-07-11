import type { StudentPortalDashboardTerm } from '@/types/students';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';

const MS_PER_DAY = 1000 * 60 * 60 * 24;

function parseTermDate(value: string | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const parsed = new Date(`${value}T00:00:00`);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function startOfDay(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

export function useStudentPortalTermProgress(
    term: MaybeRefOrGetter<StudentPortalDashboardTerm | null | undefined>,
) {
    const opening = computed(() => parseTermDate(toValue(term)?.openingDate));
    const closing = computed(() => parseTermDate(toValue(term)?.closingDate));

    const today = computed(() => startOfDay(new Date()));

    const daysRemaining = computed<number | null>(() => {
        const close = closing.value;

        if (close === null) {
            return null;
        }

        const diff = Math.ceil((close.getTime() - today.value.getTime()) / MS_PER_DAY);

        return Math.max(diff, 0);
    });

    const elapsedPercent = computed<number>(() => {
        const open = opening.value;
        const close = closing.value;

        if (open === null || close === null || close.getTime() <= open.getTime()) {
            return 0;
        }

        const elapsed = today.value.getTime() - open.getTime();
        const total = close.getTime() - open.getTime();
        const percent = (elapsed / total) * 100;

        return Math.min(Math.max(Math.round(percent), 0), 100);
    });

    const isTodayWithinTerm = computed<boolean>(() => {
        const open = opening.value;
        const close = closing.value;

        if (open === null || close === null) {
            return false;
        }

        return today.value.getTime() >= open.getTime() && today.value.getTime() <= close.getTime();
    });

    return {
        opening,
        closing,
        daysRemaining,
        elapsedPercent,
        isTodayWithinTerm,
    };
}
