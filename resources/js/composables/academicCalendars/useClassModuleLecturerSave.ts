import axios, { AxiosError } from 'axios';
import { trans } from 'laravel-vue-i18n';
import { reactive, ref } from 'vue';

import type { ClassSemesterModule } from '@/types/academic-calendar';

export type ModuleLecturerFeedback = {
    type: 'success' | 'error';
    message: string;
} | null;

type SyncModuleLecturersResponse = {
    message: string;
    moduleId: number;
    staffIds: number[];
};

type CopyDefaultsResponse = {
    message: string;
    semesterModules: ClassSemesterModule[];
};

function extractErrorMessage(error: unknown, fallback: string): string {
    if (error instanceof AxiosError) {
        const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;

        if (data?.errors) {
            const firstKey = Object.keys(data.errors)[0];
            const firstMessage = firstKey ? data.errors[firstKey]?.[0] : undefined;

            if (firstMessage) {
                return firstMessage;
            }
        }

        if (typeof data?.message === 'string' && data.message !== '') {
            return data.message;
        }
    }

    return fallback;
}

export function useClassModuleLecturerSave(
    syncModuleUrl: () => string,
    copyDefaultsUrl: () => string,
    academicYearOptionId: () => number | null,
) {
    const savingModuleId = reactive<Record<number, boolean>>({});
    const copyingDefaults = ref(false);
    const moduleFeedback = reactive<Record<number, ModuleLecturerFeedback>>({});
    const savedStaffIds = reactive<Record<number, number[]>>({});

    const clearFeedbackLater = (moduleId: number): void => {
        window.setTimeout(() => {
            if (moduleFeedback[moduleId]?.type === 'success') {
                moduleFeedback[moduleId] = null;
            }
        }, 4000);
    };

    const saveModuleLecturers = async (module: ClassSemesterModule, staffIds: number[]): Promise<boolean> => {
        const optionId = academicYearOptionId();

        if (optionId == null) {
            return false;
        }

        savingModuleId[module.moduleId] = true;
        moduleFeedback[module.moduleId] = null;

        try {
            const { data } = await axios.put<SyncModuleLecturersResponse>(
                syncModuleUrl(),
                {
                    academic_year_option_id: optionId,
                    course_syllabus_module_id: module.moduleId,
                    staff_ids: staffIds,
                },
                {
                    headers: { Accept: 'application/json' },
                    withCredentials: true,
                },
            );

            savedStaffIds[module.moduleId] = [...(data.staffIds ?? staffIds)];
            moduleFeedback[module.moduleId] = {
                type: 'success',
                message: data.message,
            };
            clearFeedbackLater(module.moduleId);

            return true;
        } catch (error) {
            moduleFeedback[module.moduleId] = {
                type: 'error',
                message: extractErrorMessage(error, trans('academic_calendar.tutor_assign_failed')),
            };

            return false;
        } finally {
            savingModuleId[module.moduleId] = false;
        }
    };

    const copyDefaults = async (): Promise<ClassSemesterModule[] | null> => {
        const optionId = academicYearOptionId();

        if (optionId == null) {
            return null;
        }

        copyingDefaults.value = true;

        try {
            const { data } = await axios.post<CopyDefaultsResponse>(
                copyDefaultsUrl(),
                { academic_year_option_id: optionId },
                {
                    headers: { Accept: 'application/json' },
                    withCredentials: true,
                },
            );

            for (const module of data.semesterModules ?? []) {
                savedStaffIds[module.moduleId] = [...module.staffIds];
                moduleFeedback[module.moduleId] = null;
            }

            return data.semesterModules ?? null;
        } catch (error) {
            moduleFeedback[-1] = {
                type: 'error',
                message: extractErrorMessage(error, trans('academic_calendar.module_lecturers_copy_failed')),
            };

            return null;
        } finally {
            copyingDefaults.value = false;
        }
    };

    const initSavedStaffIds = (modules: ClassSemesterModule[]): void => {
        for (const module of modules) {
            savedStaffIds[module.moduleId] = [...module.staffIds];
        }
    };

    const isModuleDirty = (moduleId: number, staffIds: number[]): boolean => {
        const saved = savedStaffIds[moduleId] ?? [];
        const current = [...staffIds].sort((a, b) => a - b);
        const baseline = [...saved].sort((a, b) => a - b);

        return JSON.stringify(current) !== JSON.stringify(baseline);
    };

    return {
        savingModuleId,
        copyingDefaults,
        moduleFeedback,
        savedStaffIds,
        saveModuleLecturers,
        copyDefaults,
        initSavedStaffIds,
        isModuleDirty,
    };
}
