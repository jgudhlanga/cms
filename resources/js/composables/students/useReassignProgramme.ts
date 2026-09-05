import { errorAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { hasAbility } from '@/lib/permissions';
import { useModalStore } from '@/store/core/useModalStore';
import type { ProgrammeUsageRecord, ReassignProgrammeSource } from '@/types/programme-reassign';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import { computed, nextTick, ref } from 'vue';

export function canReassignProgramme(): boolean {
    return hasAbility(['update:student-applications', 'manage:data-maintenance', 'root:manage']);
}

const unanimousOption = (
    rows: ProgrammeUsageRecord[],
    idKey:
        | 'institution_department_id'
        | 'department_level_id'
        | 'department_course_id'
        | 'mode_of_study_id',
    labelKey: 'department' | 'level' | 'course' | 'mode_of_study',
): SelectOption | null => {
    const ids = rows
        .map((row) => Number(row[idKey] ?? 0))
        .filter((id) => Number.isFinite(id) && id > 0);

    if (ids.length === 0 || ids.length !== rows.length) {
        return null;
    }

    const first = ids[0];

    if (!ids.every((id) => id === first)) {
        return null;
    }

    const label = rows.find((row) => Number(row[idKey] ?? 0) === first)?.[labelKey];

    return {
        value: first,
        label: label && label.trim() !== '' ? label : `#${first}`,
    };
};

export function useReassignProgramme() {
    const { openModal, closeModal } = useModalStore();
    const records = ref<ProgrammeUsageRecord[]>([]);
    const loadingRecords = ref(false);
    const selectedApplicationIds = ref<number[]>([]);
    const hydratingDefaults = ref(false);

    const form = useForm({
        application_ids: [] as number[],
        student_enrolment_ids: [] as number[],
        institution_department_id: null as number | null,
        department_level_id: null as number | null,
        department_course_id: null as number | null,
        mode_of_study_id: null as number | null,
        department: null as SelectOption | null,
        level: null as SelectOption | null,
        course: null as SelectOption | null,
        modeOfStudy: null as SelectOption | null,
    });

    const selectedCount = computed(() => selectedApplicationIds.value.length);

    const selectedRecords = (): ProgrammeUsageRecord[] => {
        if (selectedApplicationIds.value.length === 0) {
            return records.value;
        }

        const selected = new Set(selectedApplicationIds.value);
        const matched = records.value.filter((row) => selected.has(row.application_id));

        return matched.length > 0 ? matched : records.value;
    };

    const applyOfferingDefaults = (): void => {
        const rows = selectedRecords();

        form.department = unanimousOption(rows, 'institution_department_id', 'department');
        form.level = unanimousOption(rows, 'department_level_id', 'level');
        form.course = unanimousOption(rows, 'department_course_id', 'course');
        form.modeOfStudy = unanimousOption(rows, 'mode_of_study_id', 'mode_of_study');
    };

    const loadOfferingRecords = async (options: {
        applicationIds?: number[];
        studentEnrolmentIds?: number[];
        source?: ReassignProgrammeSource;
    }): Promise<void> => {
        const params: Record<string, unknown> = {};
        const applicationIds = (options.applicationIds ?? []).map(Number).filter((id) => id > 0);
        const studentEnrolmentIds = (options.studentEnrolmentIds ?? []).map(Number).filter((id) => id > 0);

        if (applicationIds.length > 0) {
            params.application_ids = applicationIds;
        }

        if (studentEnrolmentIds.length > 0) {
            params.student_enrolment_ids = studentEnrolmentIds;
        }

        if (params.application_ids === undefined && params.student_enrolment_ids === undefined) {
            const departmentCourseId = Number(options.source?.departmentCourseId ?? 0);
            const departmentLevelId = Number(options.source?.departmentLevelId ?? 0);

            if (departmentCourseId < 1 || departmentLevelId < 1) {
                records.value = [];
                return;
            }

            params.department_course_id = departmentCourseId;
            params.department_level_id = departmentLevelId;
            params.mode_of_study_ids = (options.source?.modeOfStudyIds ?? []).map(Number).filter((id) => id > 0);
        }

        loadingRecords.value = true;

        try {
            const response = await axios.get(route('students.programmes.usage'), { params });
            records.value = Array.isArray(response.data?.data) ? response.data.data : [];
            selectedApplicationIds.value =
                applicationIds.length > 0
                    ? records.value
                          .map((row) => row.application_id)
                          .filter((id) => applicationIds.includes(id))
                    : records.value.map((row) => row.application_id);

            if (selectedApplicationIds.value.length === 0) {
                selectedApplicationIds.value = records.value.map((row) => row.application_id);
            }
        } catch {
            records.value = [];
            selectedApplicationIds.value = [];
            errorAlert(trans('students.reassign_programme_load_failure'));
        } finally {
            loadingRecords.value = false;
        }
    };

    const openReassignProgrammeDialog = async (options: {
        applicationIds?: number[];
        studentEnrolmentIds?: number[];
        source?: ReassignProgrammeSource;
        records?: ProgrammeUsageRecord[];
    }): Promise<void> => {
        hydratingDefaults.value = true;
        form.reset();
        form.clearErrors();
        form.student_enrolment_ids = options.studentEnrolmentIds ?? [];

        if (options.records?.length) {
            records.value = options.records;
            selectedApplicationIds.value = options.applicationIds ?? options.records.map((row) => row.application_id);
        } else if (
            options.source ||
            (options.applicationIds ?? []).length > 0 ||
            (options.studentEnrolmentIds ?? []).length > 0
        ) {
            await loadOfferingRecords(options);
        } else {
            records.value = [];
            selectedApplicationIds.value = [];
        }

        applyOfferingDefaults();
        await nextTick();
        hydratingDefaults.value = false;
        openModal(APP_MODULE_KEYS.reassign_programme);
    };

    const submitReassignProgramme = (): void => {
        if (selectedApplicationIds.value.length === 0 && form.student_enrolment_ids.length === 0) {
            errorAlert(trans('students.reassign_programme_none_selected'));
            return;
        }

        form.institution_department_id = Number(form.department?.value ?? 0) || null;
        form.department_level_id = Number(form.level?.value ?? 0) || null;
        form.department_course_id = Number(form.course?.value ?? 0) || null;
        form.mode_of_study_id = Number(form.modeOfStudy?.value ?? 0) || null;
        form.application_ids = selectedApplicationIds.value;
        form.student_enrolment_ids = records.value
            .filter(
                (row) =>
                    selectedApplicationIds.value.includes(row.application_id) &&
                    row.student_enrolment_id !== null &&
                    row.student_enrolment_id > 0,
            )
            .map((row) => Number(row.student_enrolment_id));

        form.post(route('students.programmes.reassign'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal(APP_MODULE_KEYS.reassign_programme);
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('students.reassign_programme_failed')));
            },
        });
    };

    return {
        form,
        records,
        loadingRecords,
        selectedApplicationIds,
        selectedCount,
        hydratingDefaults,
        openReassignProgrammeDialog,
        submitReassignProgramme,
        loadOfferingRecords,
    };
}
