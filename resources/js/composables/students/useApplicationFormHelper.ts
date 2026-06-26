import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import {
    collectDistinctExamYears,
    MAX_DISTINCT_EXAM_YEARS,
    validateExamYear,
} from '@/lib/examYear';
import { CreateApplicationParams, ProgramParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';

type SittingOption = { value: string; label: string };
export const useApplicationFormHelper = (isEditing?: boolean) => {
    const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
    const storeRefs = storeToRefs(store);

    const updateProgramForm = (form: InertiaForm<ProgramParams>) => {
        Object.assign(form, {
            modeOfStudy: storeRefs.modeOfStudy.value,
            mode_of_study_id: storeRefs.modeOfStudy.value?.value ?? null,
            department: storeRefs.department.value,
            department_id: storeRefs.department.value?.value ?? null,
            course: storeRefs.course.value,
            course_id: storeRefs.course.value?.value ?? null,
            level: storeRefs.level.value,
            level_id: storeRefs.level.value?.value ?? null,
            required_level_completed: storeRefs.required_level_completed?.value ?? null,
            required_level_upload: storeRefs.required_level_upload?.value ?? null,
            read_write_acknowledged: storeRefs.read_write_acknowledged?.value ?? null,
            o_level_subject_ids: storeRefs.o_level_subject_ids?.value ?? null,
            o_level_years: storeRefs.o_level_years?.value ?? null,
            o_level_sittings: storeRefs.o_level_sittings?.value ?? null,
            o_level_other_subject_ids: storeRefs.o_level_other_subject_ids?.value ?? null,
            o_level_other_grade_ids: storeRefs.o_level_other_grade_ids?.value ?? null,
            o_level_other_years: storeRefs.o_level_other_years?.value ?? null,
            o_level_other_sittings: storeRefs.o_level_other_sittings?.value ?? null,
        });
    };
    const updateCreateForm = (form: InertiaForm<CreateApplicationParams>) => {
        const storeRefs = storeToRefs(useCreateApplicationFormStore());
        Object.assign(form, {
            email: storeRefs.email.value,
            first_name: storeRefs.first_name.value,
            gender: storeRefs.gender.value,
            gender_id: storeRefs.gender.value?.value ?? '',
            last_name: storeRefs.last_name.value,
            middle_name: storeRefs.middle_name.value ?? '',
            title: storeRefs.title.value,
            title_id: storeRefs.title.value?.value ?? '',
            address_1: storeRefs.address_1.value,
            address_2: storeRefs.address_2.value,
            address_3: storeRefs.address_3.value,
            address_4: storeRefs.address_4.value,
            alt_phone_number: storeRefs.alt_phone_number?.value ?? '',
            country: storeRefs.country?.value,
            country_id: storeRefs.country?.value?.value ?? null,
            date_of_birth: storeRefs.date_of_birth.value ?? '',
            id_number: storeRefs.id_number?.value ?? '',
            id_type_id: storeRefs.idType.value?.value ?? '',
            disability_status: storeRefs.disability_status?.value ?? null,
            idType: storeRefs.idType.value ?? '',
            maritalStatus: storeRefs.maritalStatus?.value,
            marital_status_id: storeRefs.maritalStatus?.value?.value ?? null,
            next_of_kin_address_1: storeRefs.next_of_kin_address_1.value ?? '',
            next_of_kin_address_2: storeRefs.next_of_kin_address_2.value ?? '',
            next_of_kin_address_3: storeRefs.next_of_kin_address_3.value ?? '',
            next_of_kin_address_4: storeRefs.next_of_kin_address_4.value ?? '',
            next_of_kin_name: storeRefs.next_of_kin_name.value ?? '',
            next_of_kin_phone_number: storeRefs.next_of_kin_phone_number.value ?? '',
            passport_number: storeRefs.passport_number?.value ?? '',
            phone_number: storeRefs.phone_number?.value ?? '',
            relationship: storeRefs.relationship.value,
            relationship_id: storeRefs.relationship.value?.value ?? null,
            study_permit_number: storeRefs.study_permit_number?.value ?? '',
            modeOfStudy: storeRefs.modeOfStudy.value,
            mode_of_study_id: storeRefs.modeOfStudy.value?.value ?? null,
            department: storeRefs.department.value,
            department_id: storeRefs.department.value?.value ?? null,
            course: storeRefs.course.value,
            course_id: storeRefs.course.value?.value ?? null,
            level: storeRefs.level.value,
            level_id: storeRefs.level.value?.value ?? null,
            required_level_completed: storeRefs.required_level_completed?.value ?? null,
            required_level_upload: storeRefs.required_level_upload?.value ?? null,
            read_write_acknowledged: storeRefs.read_write_acknowledged?.value ?? null,
            o_level_subject_ids: storeRefs.o_level_subject_ids?.value ?? null,
            o_level_years: storeRefs.o_level_years?.value ?? null,
            o_level_sittings: storeRefs.o_level_sittings?.value ?? null,
            o_level_other_subject_ids: storeRefs.o_level_other_subject_ids?.value ?? null,
            o_level_other_grade_ids: storeRefs.o_level_other_grade_ids?.value ?? null,
            o_level_other_years: storeRefs.o_level_other_years?.value ?? null,
            o_level_other_sittings: storeRefs.o_level_other_sittings?.value ?? null,
            proof_of_payment: storeRefs.proof_of_payment?.value ?? null,
            payment_reference: storeRefs.payment_reference?.value ?? null,
            payment_date: storeRefs.payment_date?.value ?? null,
        });
    };

    const dateOfBirth = () => storeRefs.date_of_birth?.value ?? null;

    const validateDistinctExamYears = (): string | null => {
        const distinctYears = collectDistinctExamYears(storeRefs.o_level_years?.value, storeRefs.o_level_other_years?.value);
        if (distinctYears.length > MAX_DISTINCT_EXAM_YEARS) {
            return trans('trans.portal_o_level_max_exam_years_exceeded', { max: String(MAX_DISTINCT_EXAM_YEARS) });
        }
        return null;
    };

    const validateMainSubjects = (mainSubjectCount: number): string[] => {
        const selectedSubjects = storeRefs.o_level_subject_ids?.value ?? {};
        const selectedCount = Object.keys(selectedSubjects).length;
        const errors: string[] = [];

        const primaryYearError = validateExamYear(storeRefs.o_level_primary_year?.value, dateOfBirth());
        if (primaryYearError) {
            errors.push(`Primary examination: ${primaryYearError}`);
        }

        const primarySitting = storeRefs.o_level_primary_sitting?.value?.value;
        if (!primarySitting || String(primarySitting).trim() === '') {
            errors.push('Primary examination: Sitting is required.');
        }

        if (selectedCount < mainSubjectCount) {
            errors.push(`You must provide exactly ${mainSubjectCount} main subjects grades. Currently: ${selectedCount}`);
        }

        Object.keys(selectedSubjects).forEach((subjectId, index) => {
            const subjectLabel = `Main subject #${index + 1} (ID: ${subjectId})`;
            const year = storeRefs.o_level_years?.value?.[subjectId];
            const sittingObj = storeRefs.o_level_sittings?.value?.[subjectId] as SittingOption | undefined;
            const sitting = sittingObj?.value;

            const yearError = validateExamYear(year, dateOfBirth());
            if (yearError) {
                errors.push(`${subjectLabel}: ${yearError}`);
            }
            if (!sitting || String(sitting).trim() === '') {
                errors.push(`${subjectLabel}: Sitting is required.`);
            }
        });

        const distinctYearsError = validateDistinctExamYears();
        if (distinctYearsError) {
            errors.push(distinctYearsError);
        }

        return errors;
    };

    const extractSubjectId = (subject: unknown): number | null => {
        if (!subject) return null;
        if (typeof subject === 'number') return subject;
        if (typeof subject === 'object' && 'value' in (subject as any)) {
            return Number((subject as any).value);
        }
        return null;
    };

    const validateOtherSubjects = (otherSubjectCount: number): string[] => {
        const errors: string[] = [];

        const selectedSubjectIds = storeRefs.o_level_other_subject_ids?.value || {};
        const gradeIds = storeRefs.o_level_other_grade_ids?.value || {};
        const years = storeRefs.o_level_other_years?.value || {};
        const sittings = storeRefs.o_level_other_sittings?.value || {};

        const keys = Object.keys(selectedSubjectIds);

        if (keys.length < otherSubjectCount) {
            errors.push(`You must provide exactly ${otherSubjectCount} other subjects. Provided: ${keys.length}`);
        }

        const seenSubjects = new Set<number>();

        keys.forEach((key, index) => {
            const label = `Other subject #${index + 1}`;
            const subjectId = extractSubjectId(selectedSubjectIds[key]);
            const gradeId = gradeIds[key];
            const year = years[key];
            const sittingObj = sittings[key] as SittingOption | undefined;
            const sitting = sittingObj?.value ?? sittingObj;

            if (!subjectId || isNaN(subjectId)) {
                errors.push(`${label}: Subject is required.`);
            } else {
                if (seenSubjects.has(subjectId)) {
                    errors.push(`${label}: Duplicate subject selected.`);
                }
                seenSubjects.add(subjectId);
            }

            const yearError = validateExamYear(year, dateOfBirth());
            if (yearError) {
                errors.push(`${label}: ${yearError}`);
            }

            if (!sitting || String(sitting).trim() === '') {
                errors.push(`${label}: Sitting is required.`);
            }

            if (!gradeId || gradeId.toString().trim() === '') {
                errors.push(`${label}: Grade is required.`);
            }
        });

        const distinctYearsError = validateDistinctExamYears();
        if (distinctYearsError && !errors.includes(distinctYearsError)) {
            errors.push(distinctYearsError);
        }

        return errors;
    };

    return { validateMainSubjects, validateOtherSubjects, updateCreateForm, updateProgramForm };
};
