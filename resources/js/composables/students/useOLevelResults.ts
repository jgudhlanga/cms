import { useGrades } from '@/composables/institution/useGrades';
import { errorAlert, openModal, successAlert, warningDialog } from '@/lib/alerts';
import { APP_MODULE_KEYS, EXAM_SITTINGS } from '@/lib/constants';
import { validateSelectOption } from '@/lib/forms';
import HttpService from '@/services/http.service';
import type { OLevelSubjectResult } from '@/types/enrolments';
import type { RadioGroupOption } from '@/types/forms';
import { Grade } from '@/types/institution';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';
import { z } from 'zod';

export const SubjectResultSchema = z.object({
    exam_year: z
        .union([z.string(), z.number()])
        .refine((v) => v != null && v !== '' && !isNaN(Number(v)) && Number(v) > 1900 && Number(v) <= new Date().getFullYear(), {
            message: 'Year is required',
        }),
    exam_sitting: z.any().refine(validateSelectOption, { message: 'Exam sitting is required' }),
    grade_id: z.string({ required_error: 'Grade is required' }).min(1, 'Grade is required'),
});

export type SubjectResult = z.infer<typeof SubjectResultSchema>;

export function useOLevelResults(oLevelSubjectResults?: OLevelSubjectResult[]) {
    const subjectForms = ref<Record<string, SubjectResult>>({});
    const subjectErrors = ref<Record<string, Record<string, string>>>({});
    const examSittings = ref(EXAM_SITTINGS);

    const { listGrades, grades, isLoading } = useGrades();

    const initForms = async () => {
        await listGrades();

        oLevelSubjectResults?.forEach((subject: OLevelSubjectResult) => {
            subjectForms.value[subject.id as string] = {
                exam_year: '',
                exam_sitting: { value: '', label: '' },
                grade_id: '',
            };
            subjectErrors.value[subject.id as string] = {}; // blank error bag
        });

        oLevelSubjectResults?.forEach((result) => {
            const subjectId = result.id;
            if (subjectForms.value[subjectId]) {
                subjectForms.value[subjectId].exam_year = String(result.attributes.examYear);
                subjectForms.value[subjectId].exam_sitting = {
                    value: String(result.attributes.examSitting),
                    label: getExamSitting(String(subjectId)),
                };
                subjectForms.value[subjectId].grade_id = String(result.attributes.gradeId);
            }
        });
    };

    const findResultBySubjectId = (subjectId: string) => oLevelSubjectResults?.find((r) => String(r.id) === subjectId);

    const getExamSitting = (subjectId: string) => {
        const result = findResultBySubjectId(subjectId);
        const sitting = examSittings.value.find((s) => String(s.value) === String(result?.attributes?.examSitting));
        return sitting ? String(sitting.label) : '---';
    };

    const getOptionsForSubject = (subject: OLevelSubjectResult): RadioGroupOption[] => {
        if (!subject || !grades.value) return [];
        return grades.value
            .filter((grade: Grade) => Number(grade.attributes.position) < 4)
            .map((grade: Grade) => ({
                value: `${subject.id}|${grade.id}`,
                label: grade.attributes?.name,
                inputId: `radio_${subject.id}_${grade.id}`,
            }));
    };

    onMounted(initForms);

    const onCreateOrEdit = (oLevelSubjectResult?: OLevelSubjectResult) => {
        openModal({ name: APP_MODULE_KEYS.o_level_subjects, edit: oLevelSubjectResult });
    };

    const onDeleteResult = async (resultId: string, onReload?: () => Promise<void>) => {
        warningDialog(() => {
            router.delete(route('portal.delete-o-level-results', resultId), {
                preserveScroll: true,
                onSuccess: async () => {
                    successAlert(trans('trans.item_archived', { item: 'O level result' }));
                    if (onReload) await onReload();
                },
            });
            return true;
        });
    };

    const loadStudentOLevelResults = async (studentId: string) => {
        try {
            isLoading.value = true;
            return  HttpService.get(route('portal.get-o-level-results', studentId));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: 'O level results' }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        subjectForms,
        subjectErrors,
        examSittings,
        isLoading,
        getOptionsForSubject,
        SubjectResultSchema,
        onCreateOrEdit,
        grades,
        onDeleteResult,
        loadStudentOLevelResults,
    };
}
