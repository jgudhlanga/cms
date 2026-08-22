export const ENROLMENT_STATUS_LIST_PERMISSIONS = {
    provisional: 'verify:class-lists',
    verified: 'confirm:class-lists',
    final: 'manage-final:class-lists',
} as const;

export type EnrolmentStatusListType = keyof typeof ENROLMENT_STATUS_LIST_PERMISSIONS;

export function enrolmentStatusListPermission(type: string): string | null {
    if (!(type in ENROLMENT_STATUS_LIST_PERMISSIONS)) {
        return null;
    }

    return ENROLMENT_STATUS_LIST_PERMISSIONS[type as EnrolmentStatusListType];
}

export function canOpenEnrolmentStatusList(
    type: string,
    showActionsColumn: boolean,
    institutionDepartmentId: number,
    userAbilities: readonly string[],
): boolean {
    if (!showActionsColumn || institutionDepartmentId <= 0) {
        return false;
    }

    const permission = enrolmentStatusListPermission(type);
    if (permission === null) {
        return false;
    }

    return userAbilities.includes(permission);
}
