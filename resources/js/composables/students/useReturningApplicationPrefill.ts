import type { Ref } from 'vue';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import type { SelectOption } from '@/types/utils';

export type ReturningPrefill = Record<string, unknown>;

type ComboField =
    | 'title'
    | 'gender'
    | 'maritalStatus'
    | 'country'
    | 'idType'
    | 'relationship'
    | 'department'
    | 'level'
    | 'course'
    | 'modeOfStudy';

const comboFields: ComboField[] = [
    'title',
    'gender',
    'maritalStatus',
    'country',
    'idType',
    'relationship',
    'department',
    'level',
    'course',
    'modeOfStudy',
];

const oLevelFields = [
    'o_level_subject_ids',
    'o_level_years',
    'o_level_sittings',
    'o_level_other_subject_ids',
    'o_level_other_grade_ids',
    'o_level_other_years',
    'o_level_other_sittings',
] as const;

function toComboOption(value: unknown): SelectOption | null {
    if (!value || typeof value !== 'object') {
        return null;
    }

    const option = value as { value?: unknown; label?: unknown };
    if (option.value === null || option.value === undefined) {
        return null;
    }

    return {
        value: String(option.value),
        label: String(option.label ?? ''),
    };
}

export function useReturningApplicationPrefill(
    prefill: ReturningPrefill,
    storeRefs: ReturnType<typeof useCreateApplicationFormStore> extends never ? never : Record<string, Ref<unknown>>,
) {
    const applyPrefill = () => {
        const scalarFields = [
            'email',
            'first_name',
            'middle_name',
            'last_name',
            'gender_id',
            'title_id',
            'marital_status_id',
            'race_id',
            'id_type_id',
            'id_number',
            'passport_number',
            'country_id',
            'study_permit_number',
            'date_of_birth',
            'disability_status',
            'phone_number',
            'alt_phone_number',
            'address_1',
            'address_2',
            'address_3',
            'address_4',
            'next_of_kin_name',
            'relationship_id',
            'next_of_kin_phone_number',
            'next_of_kin_address_1',
            'next_of_kin_address_2',
            'next_of_kin_address_3',
            'next_of_kin_address_4',
            'mode_of_study_id',
            'department_id',
            'level_id',
            'course_id',
            'required_level_completed',
            'read_write_acknowledged',
        ] as const;

        scalarFields.forEach((field) => {
            const value = prefill[field];
            if (value !== null && value !== undefined && storeRefs[field]) {
                (storeRefs[field] as Ref<unknown>).value = value;
            }
        });

        comboFields.forEach((field) => {
            const combo = toComboOption(prefill[field]);
            if (combo && storeRefs[field]) {
                (storeRefs[field] as Ref<unknown>).value = combo;
            }
        });

        oLevelFields.forEach((field) => {
            const value = prefill[field];
            if (value !== null && value !== undefined && storeRefs[field]) {
                (storeRefs[field] as Ref<unknown>).value = value;
            }
        });
    };

    return { applyPrefill };
}
