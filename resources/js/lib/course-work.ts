export const COURSE_WORK_MARK_MIN = 0;

export const COURSE_WORK_MARK_MAX = 100;

export function parseCourseWorkMark(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined) {
        return null;
    }

    if (typeof value === 'number') {
        if (!Number.isInteger(value) || value < COURSE_WORK_MARK_MIN || value > COURSE_WORK_MARK_MAX) {
            return null;
        }

        return value;
    }

    const trimmed = value.trim();

    if (trimmed === '') {
        return null;
    }

    if (!/^\d+$/.test(trimmed)) {
        return null;
    }

    const parsed = Number.parseInt(trimmed, 10);

    if (parsed < COURSE_WORK_MARK_MIN || parsed > COURSE_WORK_MARK_MAX) {
        return null;
    }

    return parsed;
}

export function isCourseWorkMarkInputInvalid(value: string | number | null | undefined): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    if (typeof value === 'string' && value.trim() === '') {
        return false;
    }

    return parseCourseWorkMark(value) === null;
}

export function formatCourseWorkStudentContext(
    parts: Array<string | null | undefined>,
    separator: string,
    missingValue: string,
): string {
    return parts.map((part) => (part?.trim() ? part.trim() : missingValue)).join(separator);
}

const auditEventTranslationKeys: Record<string, string> = {
    created: 'academic_calendar.course_work_audit_event_created',
    updated: 'academic_calendar.course_work_audit_event_updated',
    deleted: 'academic_calendar.course_work_audit_event_deleted',
    restored: 'academic_calendar.course_work_audit_event_restored',
};

export function courseWorkAuditEventTranslationKey(event: string): string {
    return auditEventTranslationKeys[event.toLowerCase()] ?? 'academic_calendar.course_work_audit_event_updated';
}
