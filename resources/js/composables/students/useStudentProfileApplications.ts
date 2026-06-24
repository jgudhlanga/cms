import { errorAlert } from '@/lib/alerts';
import { buildJsonApiIndexParams, jsonApiRequestConfig, parseJsonApiStudentApplications } from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type { Enrolment } from '@/types/enrolments';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useStudentProfileApplications = () => {
    const applications = ref<Enrolment[]>([]);
    const isLoading = ref(false);
    const loadError = ref(false);

    const fetchStudentApplications = async (studentId: string) => {
        if (!studentId) {
            applications.value = [];
            return;
        }

        try {
            isLoading.value = true;
            loadError.value = false;

            const document = await HttpService.get(route('v1.json.students.student-applications.index'), {
                ...jsonApiRequestConfig(),
                params: buildJsonApiIndexParams({ student: studentId }, { size: 50 }),
            });

            applications.value = parseJsonApiStudentApplications(document);
        } catch {
            loadError.value = true;
            applications.value = [];
            errorAlert(trans('students.applications_load_failure'));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        applications,
        isLoading,
        loadError,
        fetchStudentApplications,
    };
};
