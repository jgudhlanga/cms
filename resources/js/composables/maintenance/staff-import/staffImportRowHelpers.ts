import { STAFF_IMPORT_LOOKUP_ERROR_KEYS } from '@/composables/maintenance/staff-import/constants';
import type {
    StaffImportFieldKey,
    StaffImportPreview,
    StaffImportPreviewLookups,
    StaffImportPreviewRow,
    StaffImportRowCorrection,
} from '@/types/staff-import';
import { trans } from 'laravel-vue-i18n';

type StaffImportRowFields = StaffImportPreviewRow['fields'];

export const isValidStaffImportEmail = (email: string): boolean => {
    const trimmed = email.trim();

    return trimmed !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
};

export const isValidStaffImportPhone = (phone: string): boolean => {
    const trimmed = phone.trim();

    return trimmed !== '' && trimmed.length <= 30;
};

export const isValidStaffImportDateOfBirth = (dateOfBirth: string): boolean => {
    const trimmed = dateOfBirth.trim();

    if (trimmed === '') {
        return false;
    }

    const parsed = Date.parse(trimmed);

    return !Number.isNaN(parsed);
};

export const formatStaffImportDateOfBirth = (dateOfBirth: string): string => {
    const trimmed = dateOfBirth.trim();

    if (!isValidStaffImportDateOfBirth(trimmed)) {
        return trimmed;
    }

    return new Date(trimmed).toISOString().split('T')[0] ?? trimmed;
};

export const getEffectiveCorrection = (
    rowCorrections: Record<number, StaffImportRowCorrection>,
    row: StaffImportPreviewRow,
): StaffImportRowCorrection => {
    return rowCorrections[row.rowNumber] ?? {};
};

export const resolvedLookupId = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
    fieldKey: keyof StaffImportRowFields,
    correctionKey: keyof StaffImportRowCorrection,
): number | null => {
    const correction = getEffectiveCorrection(rowCorrections, row);
    const correctionValue = correction[correctionKey];

    if (typeof correctionValue === 'number') {
        return correctionValue;
    }

    if (fieldKey === 'roles') {
        return null;
    }

    return row.fields[fieldKey].resolvedId;
};

export const resolvedEmail = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): string => {
    const correctionEmail = getEffectiveCorrection(rowCorrections, row).email;

    if (correctionEmail !== undefined && correctionEmail.trim() !== '') {
        return correctionEmail.trim();
    }

    return row.email?.trim() ?? '';
};

export const resolvedPhoneNumber = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): string => {
    const correctionPhone = getEffectiveCorrection(rowCorrections, row).phoneNumber;

    if (correctionPhone !== undefined && correctionPhone.trim() !== '') {
        return correctionPhone.trim();
    }

    return row.phoneNumber?.trim() ?? '';
};

export const resolvedDateOfBirth = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): string => {
    const correctionDateOfBirth = getEffectiveCorrection(rowCorrections, row).dateOfBirth;

    if (correctionDateOfBirth !== undefined && correctionDateOfBirth.trim() !== '') {
        return formatStaffImportDateOfBirth(correctionDateOfBirth);
    }

    const rowDateOfBirth = row.dateOfBirth?.trim() ?? '';

    return rowDateOfBirth !== '' ? formatStaffImportDateOfBirth(rowDateOfBirth) : '';
};

export const lookupOptionsKey = (
    fieldKey: StaffImportFieldKey,
): keyof StaffImportPreviewLookups | null => {
    const map: Partial<Record<StaffImportFieldKey, keyof StaffImportPreviewLookups>> = {
        title: 'titles',
        gender: 'genders',
        maritalStatus: 'maritalStatuses',
        employmentType: 'employmentTypes',
        department: 'departments',
        roles: 'roles',
    };

    return map[fieldKey] ?? null;
};

export const rowIsResolvable = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): boolean => {
    if (row.action === 'skip_empty') {
        return true;
    }

    const requiredIds = [
        resolvedLookupId(row, rowCorrections, 'title', 'titleId'),
        resolvedLookupId(row, rowCorrections, 'gender', 'genderId'),
        resolvedLookupId(row, rowCorrections, 'maritalStatus', 'maritalStatusId'),
        resolvedLookupId(row, rowCorrections, 'employmentType', 'employmentTypeId'),
        resolvedLookupId(row, rowCorrections, 'department', 'institutionDepartmentId'),
    ];

    if (requiredIds.some((id) => id === null)) {
        return false;
    }

    const roleCorrection = getEffectiveCorrection(rowCorrections, row).roleIds;
    const roleIds =
        roleCorrection ??
        row.fields.roles.map((role) => role.resolvedId).filter((id): id is number => id !== null);

    if (row.fields.roles.some((role) => role.resolvedId === null) && roleIds.length === 0) {
        return false;
    }

    if (!isValidStaffImportEmail(resolvedEmail(row, rowCorrections))) {
        return false;
    }

    if (!isValidStaffImportPhone(resolvedPhoneNumber(row, rowCorrections))) {
        return false;
    }

    if (!isValidStaffImportDateOfBirth(resolvedDateOfBirth(row, rowCorrections))) {
        return false;
    }

    if (row.errors !== null) {
        const hasBlockingErrors = Object.keys(row.errors).some((key) => {
            if (STAFF_IMPORT_LOOKUP_ERROR_KEYS.includes(key as (typeof STAFF_IMPORT_LOOKUP_ERROR_KEYS)[number])) {
                return false;
            }

            if (key === 'EMAIL' && isValidStaffImportEmail(resolvedEmail(row, rowCorrections))) {
                return false;
            }

            if (key === 'PHONE_NUMBER' && isValidStaffImportPhone(resolvedPhoneNumber(row, rowCorrections))) {
                return false;
            }

            if (key === 'DATE_OF_BIRTH' && isValidStaffImportDateOfBirth(resolvedDateOfBirth(row, rowCorrections))) {
                return false;
            }

            return true;
        });

        if (hasBlockingErrors) {
            return false;
        }
    }

    return true;
};

export const activeRowErrors = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): string[] => {
    if (rowIsResolvable(row, rowCorrections)) {
        return [];
    }

    const errors: string[] = [];

    if (
        resolvedLookupId(row, rowCorrections, 'department', 'institutionDepartmentId') === null
        && row.fields.department.raw
    ) {
        errors.push(
            trans('trans.maintenance_staff_import_lookup_not_found', {
                field: 'DEPARTMENT',
                value: row.fields.department.raw,
            }),
        );
    }

    if (resolvedLookupId(row, rowCorrections, 'title', 'titleId') === null && row.fields.title.raw) {
        errors.push(
            trans('trans.maintenance_staff_import_lookup_not_found', {
                field: 'TITLE',
                value: row.fields.title.raw,
            }),
        );
    }

    if (resolvedLookupId(row, rowCorrections, 'gender', 'genderId') === null && row.fields.gender.raw) {
        errors.push(
            trans('trans.maintenance_staff_import_lookup_not_found', {
                field: 'GENDER',
                value: row.fields.gender.raw,
            }),
        );
    }

    if (
        resolvedLookupId(row, rowCorrections, 'maritalStatus', 'maritalStatusId') === null
        && row.fields.maritalStatus.raw
    ) {
        errors.push(
            trans('trans.maintenance_staff_import_lookup_not_found', {
                field: 'MARITAL_STATUS',
                value: row.fields.maritalStatus.raw,
            }),
        );
    }

    if (
        resolvedLookupId(row, rowCorrections, 'employmentType', 'employmentTypeId') === null
        && row.fields.employmentType.raw
    ) {
        errors.push(
            trans('trans.maintenance_staff_import_lookup_not_found', {
                field: 'EMPLOYMENT_TYPE',
                value: row.fields.employmentType.raw,
            }),
        );
    }

    const roleIds =
        getEffectiveCorrection(rowCorrections, row).roleIds ??
        row.fields.roles.map((role) => role.resolvedId).filter((id): id is number => id !== null);

    if (row.fields.roles.some((role) => role.resolvedId === null) && roleIds.length === 0) {
        const unresolvedRoles = row.fields.roles
            .filter((role) => role.resolvedId === null)
            .map((role) => role.raw)
            .join(', ');

        if (unresolvedRoles !== '') {
            errors.push(
                trans('trans.maintenance_staff_import_role_not_found', {
                    role: unresolvedRoles,
                }),
            );
        }
    }

    const email = resolvedEmail(row, rowCorrections);

    if (!isValidStaffImportEmail(email)) {
        const emailErrors = row.errors?.EMAIL;

        if (emailErrors !== undefined && emailErrors.length > 0) {
            errors.push(...emailErrors);
        } else {
            errors.push(trans('trans.maintenance_staff_import_invalid_email'));
        }
    }

    const phoneNumber = resolvedPhoneNumber(row, rowCorrections);

    if (!isValidStaffImportPhone(phoneNumber)) {
        const phoneErrors = row.errors?.PHONE_NUMBER;

        if (phoneErrors !== undefined && phoneErrors.length > 0) {
            errors.push(...phoneErrors);
        } else {
            errors.push(trans('trans.maintenance_staff_import_invalid_phone'));
        }
    }

    const dateOfBirth = resolvedDateOfBirth(row, rowCorrections);

    if (!isValidStaffImportDateOfBirth(dateOfBirth)) {
        const dateOfBirthErrors = row.errors?.DATE_OF_BIRTH;

        if (dateOfBirthErrors !== undefined && dateOfBirthErrors.length > 0) {
            errors.push(...dateOfBirthErrors);
        } else {
            errors.push(trans('trans.maintenance_staff_import_invalid_date_of_birth'));
        }
    }

    if (row.errors !== null) {
        for (const [key, messages] of Object.entries(row.errors)) {
            if (
                STAFF_IMPORT_LOOKUP_ERROR_KEYS.includes(key as (typeof STAFF_IMPORT_LOOKUP_ERROR_KEYS)[number])
                || key === 'EMAIL'
                || key === 'PHONE_NUMBER'
                || key === 'DATE_OF_BIRTH'
            ) {
                continue;
            }

            errors.push(...messages);
        }
    }

    return errors;
};

export const effectiveAction = (
    row: StaffImportPreviewRow,
    rowCorrections: Record<number, StaffImportRowCorrection>,
): StaffImportPreviewRow['action'] => {
    if (row.action === 'skip_empty') {
        return 'skip_empty';
    }

    if (!rowIsResolvable(row, rowCorrections)) {
        return 'fail';
    }

    if (row.action === 'update') {
        return 'update';
    }

    return 'create';
};

export const staffImportActionLabel = (action: string): string => {
    const keys: Record<string, string> = {
        create: 'trans.maintenance_staff_import_preview_action_create',
        update: 'trans.maintenance_staff_import_preview_action_update',
        skip_empty: 'trans.maintenance_staff_import_preview_action_skip_empty',
        fail: 'trans.maintenance_staff_import_preview_action_fail',
    };

    return trans(keys[action] ?? keys.fail);
};

export const isRowExcluded = (rowNumber: number, excludedRowNumbers: ReadonlySet<number>): boolean => {
    return excludedRowNumbers.has(rowNumber);
};

export const computeEffectiveSummary = (
    preview: StaffImportPreview,
    rowCorrections: Record<number, StaffImportRowCorrection>,
    excludedRowNumbers: ReadonlySet<number> = new Set(),
) => {
    let creates = 0;
    let updates = 0;
    let failed = 0;
    let skipped = 0;

    for (const row of preview.rows) {
        if (row.action === 'skip_empty') {
            skipped++;
            continue;
        }

        if (isRowExcluded(row.rowNumber, excludedRowNumbers)) {
            skipped++;
            continue;
        }

        if (!rowIsResolvable(row, rowCorrections)) {
            failed++;
            continue;
        }

        if (row.action === 'update') {
            updates++;
        } else if (row.action === 'create' || row.action === 'fail') {
            creates++;
        } else {
            failed++;
        }
    }

    return {
        total: preview.rows.length,
        succeeded: creates + updates,
        failed,
        skipped,
        creates,
        updates,
    };
};

export const buildRowCorrectionsPayload = (
    preview: StaffImportPreview,
    rowCorrections: Record<number, StaffImportRowCorrection>,
    excludedRowNumbers: ReadonlySet<number> = new Set(),
): Record<number, StaffImportRowCorrection> => {
    const payload: Record<number, StaffImportRowCorrection> = {};

    for (const row of preview.rows) {
        if (row.action === 'skip_empty' || isRowExcluded(row.rowNumber, excludedRowNumbers)) {
            continue;
        }

        const correction: StaffImportRowCorrection = {};

        const titleId = resolvedLookupId(row, rowCorrections, 'title', 'titleId');
        const genderId = resolvedLookupId(row, rowCorrections, 'gender', 'genderId');
        const maritalStatusId = resolvedLookupId(row, rowCorrections, 'maritalStatus', 'maritalStatusId');
        const employmentTypeId = resolvedLookupId(row, rowCorrections, 'employmentType', 'employmentTypeId');
        const institutionDepartmentId = resolvedLookupId(row, rowCorrections, 'department', 'institutionDepartmentId');

        if (titleId !== null) {
            correction.titleId = titleId;
        }
        if (genderId !== null) {
            correction.genderId = genderId;
        }
        if (maritalStatusId !== null) {
            correction.maritalStatusId = maritalStatusId;
        }
        if (employmentTypeId !== null) {
            correction.employmentTypeId = employmentTypeId;
        }
        if (institutionDepartmentId !== null) {
            correction.institutionDepartmentId = institutionDepartmentId;
        }

        const roleIds =
            getEffectiveCorrection(rowCorrections, row).roleIds ??
            row.fields.roles.map((role) => role.resolvedId).filter((id): id is number => id !== null);

        if (roleIds.length > 0) {
            correction.roleIds = roleIds;
        }

        const email = resolvedEmail(row, rowCorrections);
        const originalEmail = row.email?.trim() ?? '';

        if (email !== '' && email !== originalEmail && isValidStaffImportEmail(email)) {
            correction.email = email;
        }

        const phoneNumber = resolvedPhoneNumber(row, rowCorrections);
        const originalPhoneNumber = row.phoneNumber?.trim() ?? '';

        if (phoneNumber !== '' && phoneNumber !== originalPhoneNumber && isValidStaffImportPhone(phoneNumber)) {
            correction.phoneNumber = phoneNumber;
        }

        const dateOfBirth = resolvedDateOfBirth(row, rowCorrections);
        const originalDateOfBirth = row.dateOfBirth?.trim() !== '' ? formatStaffImportDateOfBirth(row.dateOfBirth ?? '') : '';

        if (
            dateOfBirth !== ''
            && dateOfBirth !== originalDateOfBirth
            && isValidStaffImportDateOfBirth(dateOfBirth)
        ) {
            correction.dateOfBirth = dateOfBirth;
        }

        if (Object.keys(correction).length > 0) {
            payload[row.rowNumber] = correction;
        }
    }

    return payload;
};
