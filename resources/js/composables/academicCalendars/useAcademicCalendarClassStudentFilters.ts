import {
    normalizeGender,
} from '@/composables/academicCalendars/useAcademicCalendarClassStudents';
import type { AcademicCalendarClassPreviewStudent } from '@/types/academic-calendar';
import type { ComputedRef } from 'vue';
import { computed, ref } from 'vue';

export type AcademicCalendarClassStudentFiltersState = {
    name?: string;
    search?: string;
    gender?: 'male' | 'female' | 'unknown' | '';
};

export function useAcademicCalendarClassStudentFilters(
    sortedStudents: ComputedRef<AcademicCalendarClassPreviewStudent[]>,
) {
    const filters = ref<AcademicCalendarClassStudentFiltersState>({});

    const filteredStudents = computed(() => {
        const nameQuery = (filters.value.name ?? '').trim().toLowerCase();
        const searchQuery = (filters.value.search ?? '').trim().toLowerCase();
        const genderFilter = filters.value.gender ?? '';

        return sortedStudents.value.filter((student) => {
            if (nameQuery && !student.name.toLowerCase().includes(nameQuery)) {
                return false;
            }

            if (searchQuery) {
                const studentNumber = String(student.studentNumber ?? '').toLowerCase();
                const trackingNumber = String(student.applicationTrackingNumber ?? '').toLowerCase();

                if (!studentNumber.includes(searchQuery) && !trackingNumber.includes(searchQuery)) {
                    return false;
                }
            }

            if (genderFilter && normalizeGender(student.gender) !== genderFilter) {
                return false;
            }

            return true;
        });
    });

    const onFiltersChange = (next: AcademicCalendarClassStudentFiltersState): void => {
        filters.value = next;
    };

    const resetFilters = (): void => {
        filters.value = {};
    };

    const hasActiveFilters = computed(() => {
        return Boolean(
            (filters.value.name ?? '').trim()
            || (filters.value.search ?? '').trim()
            || filters.value.gender,
        );
    });

    return {
        filters,
        filteredStudents,
        onFiltersChange,
        resetFilters,
        hasActiveFilters,
    };
}
