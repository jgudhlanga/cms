import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { CreateApplicationParams } from '@/types/portal';
import { InertiaForm } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';

type SittingOption = { value: string; label: string };
export const useApplicationFormHelper = () => {
    const storeRefs = storeToRefs(useCreateApplicationFormStore());

    const updateForm = (form: InertiaForm<CreateApplicationParams>) => {
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
        });
    };
    const validateMainSubjects = (): string[] => {
        const selectedSubjects = storeRefs.o_level_subject_ids?.value ?? {};
        const selectedCount = Object.keys(selectedSubjects).length;
        const errors: string[] = [];
        if (selectedCount !== 3) {
            errors.push(`You must provide exactly 3 main subjects grades. Currently: ${selectedCount}`);
        }
        Object.keys(selectedSubjects).forEach((subjectId, index) => {
            const subjectLabel = `Main subject #${index + 1} (ID: ${subjectId})`;
            const year = storeRefs.o_level_years?.value?.[subjectId];

            const sittingObj = storeRefs.o_level_sittings?.value?.[subjectId] as SittingOption | undefined;
            const sitting = sittingObj?.value;

            if (!year || isNaN(Number(year))) {
                errors.push(`${subjectLabel}: Exam year is required.`);
            }
            if (!sitting || sitting.trim() === '') {
                errors.push(`${subjectLabel}: Sitting is required.`);
            }
        });
        return errors;
    };

    const extractSubjectId = (subject: unknown): number | null => {
        if (!subject) return null;
        if (typeof subject === "number") return subject;
        if (typeof subject === "object" && "value" in (subject as any)) {
            return Number((subject as any).value);
        }
        return null;
    };


    const validateOtherSubjects = (): string[] => {
        const errors: string[] = [];

        const selectedSubjectIds = storeRefs.o_level_other_subject_ids?.value || {};
        const gradeIds = storeRefs.o_level_other_grade_ids?.value || {};
        const years = storeRefs.o_level_other_years?.value || {};
        const sittings = storeRefs.o_level_other_sittings?.value || {};

        const keys = Object.keys(selectedSubjectIds);

        // Must have exactly 2 subjects
        if (keys.length !== 2) {
            errors.push(`You must provide exactly 2 other subjects. Provided: ${keys.length}`);
        }

        const seenSubjects = new Set<number>();

        keys.forEach((key, index) => {
            const label = `Other subject #${index + 1}`;
            const subjectId = extractSubjectId(selectedSubjectIds[key]); // 👈 safe extraction
            const gradeId = gradeIds[key];
            const year = years[key];
            const sitting = sittings[key];

            if (!subjectId || isNaN(subjectId)) {
                errors.push(`${label}: Subject is required.`);
            } else {
                if (seenSubjects.has(subjectId)) {
                    errors.push(`${label}: Duplicate subject selected.`);
                }
                seenSubjects.add(subjectId);
            }

            if (!year || isNaN(Number(year))) {
                errors.push(`${label}: Year is required.`);
            }

            if (!sitting || sitting.toString().trim() === '') {
                errors.push(`${label}: Sitting is required.`);
            }

            if (!gradeId || gradeId.toString().trim() === '') {
                errors.push(`${label}: Grade is required.`);
            }
        });
        return errors;
    };

    return { validateMainSubjects, validateOtherSubjects, updateForm };
};
