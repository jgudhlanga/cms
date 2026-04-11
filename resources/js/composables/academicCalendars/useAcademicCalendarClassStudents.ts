import type { AcademicCalendarClassDetail } from '@/types/academic-calendar';
import type { Ref } from 'vue';
import { computed } from 'vue';

export function normalizeGender(gender: string | null | undefined): 'female' | 'male' | 'unknown' {
    const normalized = String(gender ?? '').trim().toLowerCase();

    if (normalized.includes('female')) {
        return 'female';
    }

    if (normalized.includes('male')) {
        return 'male';
    }

    return 'unknown';
}

export function useAcademicCalendarClassStudents(academicCalendarClass: Ref<AcademicCalendarClassDetail>) {
    const sortedStudents = computed(() => {
        return [...academicCalendarClass.value.students].sort((a, b) => {
            const genderPriority: Record<'female' | 'male' | 'unknown', number> = {
                female: 0,
                male: 1,
                unknown: 2,
            };

            const genderSort = genderPriority[normalizeGender(a.gender)] - genderPriority[normalizeGender(b.gender)];

            if (genderSort !== 0) {
                return genderSort;
            }

            return a.name.localeCompare(b.name);
        });
    });

    return { sortedStudents };
}
