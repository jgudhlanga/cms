import {
    displayValue,
    formatDurationHours,
    gradeBadgeClass,
    scoreBarColor,
    scoreLabel,
    semesterDurationHours,
    statusBadgeClass,
} from '@/composables/students/studentProgrammeDisplay';
import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import type { StudentProgramme, StudentProgrammesApiResponse } from '@/types/students';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export function useStudentProgrammes() {
    const programmes = ref<StudentProgramme[]>([]);
    const isLoading = ref(false);
    const loadError = ref(false);

    const fetchProgrammes = async (studentId: string | number) => {
        try {
            isLoading.value = true;
            loadError.value = false;
            const response = await HttpService.get(
                route('v1.students.student-enrolements', { student: studentId }),
            ) as StudentProgrammesApiResponse;

            programmes.value = Array.isArray(response?.result) ? response.result : [];
        } catch {
            loadError.value = true;
            programmes.value = [];
            errorAlert(trans('students.programmes_load_failure'));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        programmes,
        isLoading,
        loadError,
        fetchProgrammes,
        semesterDurationHours,
        formatDurationHours,
        displayValue,
        statusBadgeClass,
        gradeBadgeClass,
        scoreBarColor,
        scoreLabel,
    };
}
