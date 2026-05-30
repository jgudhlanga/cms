import { errorAlert, successAlert } from '@/lib/alerts';
import {
    buildCourseWorkMarkPayload,
    courseWorkClassFilterParams,
    courseWorkStudentFilterParams,
    jsonApiRequestConfig,
    jsonApiWriteConfig,
    parseJsonApiCourseWorkStudentTree,
} from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type { CourseWorkAuditLogEntry, CourseWorkStudentTree } from '@/types/course-work';
import type { AxiosError } from 'axios';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

const saveErrorMessage = (error: unknown): string => {
    const axiosError = error as AxiosError<{
        message?: string;
        errors?: Array<{ detail?: string; title?: string }>;
    }>;
    const jsonApiDetail = axiosError.response?.data?.errors?.[0]?.detail;

    if (jsonApiDetail) {
        return jsonApiDetail;
    }

    if (axiosError.response?.data?.message) {
        return axiosError.response.data.message;
    }

    return trans('academic_calendar.course_work_save_failed');
};

type SaveMarkInput = {
    markId: number | null;
    courseSyllabusModuleId: number;
    assessmentTypeId: number;
    mark: number | null;
    remark: string | null;
};

export function useAcademicCalendarClassStudentCourseWork(
    academicCalendarClassId: number,
    studentEnrolmentId: number,
) {
    const tree = ref<CourseWorkStudentTree | null>(null);
    const auditLogs = ref<CourseWorkAuditLogEntry[]>([]);
    const loading = ref(false);
    const refreshing = ref(false);
    const savingKey = ref<string | null>(null);
    const error = ref<string | null>(null);
    const auditLoading = ref(false);
    const auditTrailEnabled = ref(false);

    const filters = () => courseWorkStudentFilterParams(academicCalendarClassId, studentEnrolmentId);
    const classFilter = () => courseWorkClassFilterParams(academicCalendarClassId);

    const loadTree = async (silent = false): Promise<void> => {
        if (silent) {
            refreshing.value = true;
        } else {
            loading.value = true;
            error.value = null;
        }

        try {
            const document = await HttpService.get(route('v1.json.course-work-marks.tree'), {
                ...jsonApiRequestConfig(),
                params: filters(),
            });

            tree.value = parseJsonApiCourseWorkStudentTree(document);
        } catch {
            if (silent) {
                errorAlert(trans('academic_calendar.course_work_load_failed'));
            } else {
                error.value = 'academic_calendar.course_work_load_failed';
                tree.value = null;
            }
        } finally {
            loading.value = false;
            refreshing.value = false;
        }
    };

    const loadAuditLogs = async (silent = false): Promise<void> => {
        auditTrailEnabled.value = true;

        if (!silent) {
            auditLoading.value = true;
        }

        try {
            const document = await HttpService.get(route('v1.json.course-work-marks.auditLogs'), {
                ...jsonApiRequestConfig(),
                params: {
                    filter: {
                        academicCalendarClass: String(academicCalendarClassId),
                        studentEnrolment: String(studentEnrolmentId),
                    },
                },
            });

            auditLogs.value = (document.meta?.logs as CourseWorkAuditLogEntry[]) ?? [];
        } catch {
            auditLogs.value = [];
        } finally {
            auditLoading.value = false;
        }
    };

    const saveMark = async (input: SaveMarkInput): Promise<boolean> => {
        const key = `${input.courseSyllabusModuleId}:${input.assessmentTypeId}`;
        savingKey.value = key;

        const attributes: Record<string, unknown> = {
            studentEnrolmentId,
            courseSyllabusModuleId: input.courseSyllabusModuleId,
            assessmentTypeId: input.assessmentTypeId,
            mark: input.mark,
            remark: input.remark,
        };

        try {
            const config = {
                ...jsonApiWriteConfig(),
                params: classFilter(),
            };

            if (input.markId) {
                await HttpService.patch(
                    route('v1.json.course-work-marks.update', input.markId),
                    buildCourseWorkMarkPayload(attributes, input.markId),
                    config,
                );
            } else {
                await HttpService.post(
                    route('v1.json.course-work-marks.store'),
                    buildCourseWorkMarkPayload(attributes),
                    config,
                );
            }

            await loadTree(true);

            if (auditTrailEnabled.value) {
                await loadAuditLogs(true);
            }

            successAlert(trans('academic_calendar.course_work_saved'));

            return true;
        } catch (error) {
            errorAlert(saveErrorMessage(error));

            return false;
        } finally {
            savingKey.value = null;
        }
    };

    const isSaving = (moduleId: number, assessmentTypeId: number): boolean =>
        savingKey.value === `${moduleId}:${assessmentTypeId}`;

    return {
        tree,
        auditLogs,
        loading,
        refreshing,
        savingKey,
        auditLoading,
        error,
        loadTree,
        loadAuditLogs,
        saveMark,
        isSaving,
    };
}
