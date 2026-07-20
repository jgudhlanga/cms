import {
    buildCourseWorkMarkPayload,
    courseWorkClassConfigFilterParams,
    courseWorkClassFilterParams,
    jsonApiRequestConfig,
    jsonApiWriteConfig,
    parseJsonApiCourseWorkTree,
} from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type {
    CourseWorkAssessment,
    CourseWorkClassModuleOption,
    CourseWorkMarksheetSummaryItem,
    CourseWorkStudent,
    CourseWorkTree,
} from '@/types/course-work';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

type SaveMarkInput = {
    markId: number | null;
    studentEnrolmentId: number;
    courseSyllabusModuleId: number;
    assessmentTypeId: number | null;
    mark: number | null;
    remark: string | null;
};

type UseCourseWorkClassMarksheetOptions = (
    | { academicCalendarClassId: number; classConfigId?: never }
    | { classConfigId: number; academicCalendarClassId?: never }
) & {
    initialModuleId?: number | null;
    allowedModuleIds?: number[] | null;
};

export function useCourseWorkClassMarksheet(options: UseCourseWorkClassMarksheetOptions) {
    const tree = ref<CourseWorkTree | null>(null);
    const selectedModuleId = ref<number | null>(null);
    const loading = ref(false);
    const savingKey = ref<string | null>(null);
    const error = ref<string | null>(null);

    const scopeFilter = () =>
        options.classConfigId != null
            ? courseWorkClassConfigFilterParams(options.classConfigId)
            : courseWorkClassFilterParams(options.academicCalendarClassId!);

    const moduleOptions = computed((): CourseWorkClassModuleOption[] => {
        if (!tree.value) {
            return [];
        }

        const allowed = options.allowedModuleIds;
        const moduleOptionsList: CourseWorkClassModuleOption[] = [];

        for (const syllabus of tree.value.syllabi) {
            for (const courseModule of syllabus.modules) {
                if (allowed != null && allowed.length > 0 && !allowed.includes(courseModule.id)) {
                    continue;
                }

                const code = courseModule.code?.trim() ?? '';
                const title = courseModule.title?.trim() ?? '';
                const baseLabel = [code, title].filter(Boolean).join(' — ') || String(courseModule.id);
                const label = courseModule.captureMarkOnly
                    ? `${baseLabel} (${trans('academic_calendar.course_work_mark_only_badge')})`
                    : baseLabel;

                moduleOptionsList.push({
                    moduleId: courseModule.id,
                    syllabusId: syllabus.id,
                    code: courseModule.code,
                    title: courseModule.title,
                    label,
                    captureMarkOnly: courseModule.captureMarkOnly,
                });
            }
        }

        return moduleOptionsList;
    });

    const selectedModuleSummary = computed((): CourseWorkMarksheetSummaryItem | null => {
        if (!tree.value?.marksheetSummary || selectedModuleId.value === null) {
            return null;
        }

        return tree.value.marksheetSummary.find((item) => item.moduleId === selectedModuleId.value) ?? null;
    });

    const moduleStudents = computed((): CourseWorkStudent[] => {
        if (!tree.value || selectedModuleId.value === null) {
            return [];
        }

        for (const syllabus of tree.value.syllabi) {
            const courseModule = syllabus.modules.find((item) => item.id === selectedModuleId.value);
            if (courseModule) {
                return courseModule.students;
            }
        }

        return [];
    });

    const selectedModuleCaptureMarkOnly = computed((): boolean => {
        if (selectedModuleId.value === null || !tree.value) {
            return false;
        }

        for (const syllabus of tree.value.syllabi) {
            const courseModule = syllabus.modules.find((item) => item.id === selectedModuleId.value);
            if (courseModule) {
                return Boolean(courseModule.captureMarkOnly);
            }
        }

        return false;
    });

    const assessmentTypes = computed(() => tree.value?.assessmentTypes ?? []);

    const loadTree = async (loadOptions: { silent?: boolean } = {}): Promise<void> => {
        if (!loadOptions.silent) {
            loading.value = true;
        }
        error.value = null;

        try {
            const document = await HttpService.get(route('v1.json.course-work-marks.tree'), {
                ...jsonApiRequestConfig(),
                params: scopeFilter(),
            });

            tree.value = parseJsonApiCourseWorkTree(document);

            if (tree.value && selectedModuleId.value === null && moduleOptions.value.length > 0) {
                const initialModuleId = options.initialModuleId ?? null;
                const matchingOption = initialModuleId != null
                    ? moduleOptions.value.find((option) => option.moduleId === initialModuleId)
                    : undefined;

                selectedModuleId.value = matchingOption?.moduleId ?? moduleOptions.value[0].moduleId;
            }
        } catch {
            error.value = 'academic_calendar.course_work_load_failed';
            tree.value = null;
        } finally {
            if (!loadOptions.silent) {
                loading.value = false;
            }
        }
    };

    const saveMark = async (input: SaveMarkInput): Promise<boolean> => {
        const assessmentKey = input.assessmentTypeId ?? 'mark-only';
        const key = `${input.studentEnrolmentId}:${input.courseSyllabusModuleId}:${assessmentKey}`;
        savingKey.value = key;

        const attributes: Record<string, unknown> = {
            studentEnrolmentId: input.studentEnrolmentId,
            courseSyllabusModuleId: input.courseSyllabusModuleId,
            mark: input.mark,
            remark: input.remark,
        };

        if (input.assessmentTypeId !== null) {
            attributes.assessmentTypeId = input.assessmentTypeId;
        }

        try {
            const config = {
                ...jsonApiWriteConfig(),
                params: scopeFilter(),
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

            await loadTree({ silent: true });
            return true;
        } catch {
            return false;
        } finally {
            savingKey.value = null;
        }
    };

    const isSaving = (
        studentEnrolmentId: number,
        moduleId: number,
        assessmentTypeId: number | null = null,
    ): boolean =>
        savingKey.value === `${studentEnrolmentId}:${moduleId}:${assessmentTypeId ?? 'mark-only'}`;

    const findAssessment = (student: CourseWorkStudent, assessmentTypeId: number): CourseWorkAssessment | undefined =>
        student.assessments.find((item) => item.assessmentTypeId === assessmentTypeId);

    return {
        tree,
        selectedModuleId,
        selectedModuleCaptureMarkOnly,
        moduleOptions,
        selectedModuleSummary,
        moduleStudents,
        assessmentTypes,
        loading,
        savingKey,
        error,
        loadTree,
        saveMark,
        isSaving,
        findAssessment,
    };
}
