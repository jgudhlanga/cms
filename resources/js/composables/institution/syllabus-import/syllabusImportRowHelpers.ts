import type {
    SyllabusImportPreview,
    SyllabusImportPreviewAction,
    SyllabusImportPreviewLookups,
    SyllabusImportPreviewRow,
    SyllabusImportRowCorrection,
} from '@/types/syllabus-import';
import { trans } from 'laravel-vue-i18n';

export type SyllabusImportFieldKey = keyof SyllabusImportRowCorrection;

export const getEffectiveCorrection = (
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    row: SyllabusImportPreviewRow,
): SyllabusImportRowCorrection => {
    return rowCorrections[row.rowNumber] ?? {};
};

export const resolvedField = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    field: SyllabusImportFieldKey,
): string => {
    const correction = getEffectiveCorrection(rowCorrections, row)[field];

    if (correction !== undefined) {
        return correction.trim();
    }

    return row[field].trim();
};

export const resolvedAllSemesters = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
): boolean => {
    const correction = getEffectiveCorrection(rowCorrections, row).allSemesters;

    if (correction !== undefined) {
        return correction;
    }

    return row.allSemesters;
};

const matchesLookup = (value: string, options: string[]): boolean => {
    const normalized = value.trim().toLowerCase();

    return options.some((option) => option.trim().toLowerCase() === normalized);
};

const levelCourseLinked = (level: string, course: string, levelCourses: string[]): boolean => {
    const expected = `${level.trim()} - ${course.trim()}`.toLowerCase();

    return levelCourses.some((entry) => entry.trim().toLowerCase() === expected);
};

const syllabusFieldsChanged = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
): boolean => {
    const correction = getEffectiveCorrection(rowCorrections, row);

    return (
        correction.level !== undefined
        || correction.courseTitle !== undefined
        || correction.courseCode !== undefined
    );
};

const moduleFieldsChanged = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
): boolean => {
    const correction = getEffectiveCorrection(rowCorrections, row);

    return (
        correction.semester !== undefined
        || correction.moduleTitle !== undefined
        || correction.moduleCode !== undefined
        || correction.allSemesters !== undefined
    );
};

export const syllabusRowIsValid = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): boolean => {
    const level = resolvedField(row, rowCorrections, 'level');
    const courseTitle = resolvedField(row, rowCorrections, 'courseTitle');
    const courseCode = resolvedField(row, rowCorrections, 'courseCode');

    if (level === '' || courseTitle === '' || courseCode === '') {
        return false;
    }

    if (!matchesLookup(level, lookups.levels)) {
        return false;
    }

    if (!matchesLookup(courseTitle, lookups.courses)) {
        return false;
    }

    return levelCourseLinked(level, courseTitle, lookups.levelCourses);
};

export const moduleRowIsValid = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): boolean => {
    const moduleCode = resolvedField(row, rowCorrections, 'moduleCode');
    const moduleTitle = resolvedField(row, rowCorrections, 'moduleTitle');
    const semester = resolvedField(row, rowCorrections, 'semester');
    const courseCode = resolvedField(row, rowCorrections, 'courseCode');

    if (moduleCode === '' && moduleTitle === '') {
        return true;
    }

    if (moduleCode === '' || moduleTitle === '' || courseCode === '' || semester === '') {
        return false;
    }

    return matchesLookup(semester, lookups.semesters);
};

export const activeSyllabusErrors = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): string[] => {
    const level = resolvedField(row, rowCorrections, 'level');
    const courseTitle = resolvedField(row, rowCorrections, 'courseTitle');
    const courseCode = resolvedField(row, rowCorrections, 'courseCode');
    const errors: string[] = [];

    if (level === '' || courseTitle === '' || courseCode === '') {
        errors.push(trans('syllabus.import_syllabus_required_fields'));
    }

    if (level !== '' && !matchesLookup(level, lookups.levels)) {
        errors.push(trans('syllabus.import_level_not_found', { level }));
    }

    if (courseTitle !== '' && !matchesLookup(courseTitle, lookups.courses)) {
        errors.push(trans('syllabus.import_course_not_found', { course: courseTitle }));
    }

    if (
        level !== ''
        && courseTitle !== ''
        && matchesLookup(level, lookups.levels)
        && matchesLookup(courseTitle, lookups.courses)
        && !levelCourseLinked(level, courseTitle, lookups.levelCourses)
    ) {
        errors.push(trans('syllabus.import_level_course_mismatch', { level, course: courseTitle }));
    }

    if (errors.length === 0 && row.syllabusAction === 'fail' && !syllabusFieldsChanged(row, rowCorrections)) {
        return row.syllabusErrors;
    }

    return errors;
};

export const activeModuleErrors = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): string[] => {
    const moduleCode = resolvedField(row, rowCorrections, 'moduleCode');
    const moduleTitle = resolvedField(row, rowCorrections, 'moduleTitle');
    const semester = resolvedField(row, rowCorrections, 'semester');
    const courseCode = resolvedField(row, rowCorrections, 'courseCode');
    const errors: string[] = [];

    if (moduleCode === '' && moduleTitle === '') {
        return [];
    }

    if (moduleCode === '' || moduleTitle === '' || courseCode === '' || semester === '') {
        errors.push(trans('syllabus.import_module_required_fields'));
    }

    if (semester !== '' && !matchesLookup(semester, lookups.semesters)) {
        errors.push(trans('syllabus.import_semester_not_found', { semester }));
    }

    if (errors.length === 0 && row.moduleAction === 'fail' && !moduleFieldsChanged(row, rowCorrections)) {
        return row.moduleErrors;
    }

    return errors;
};

export const effectiveSyllabusAction = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): SyllabusImportPreviewAction => {
    if (!syllabusRowIsValid(row, rowCorrections, lookups)) {
        return 'fail';
    }

    const courseCode = resolvedField(row, rowCorrections, 'courseCode');

    if (row.syllabusExists && courseCode === row.courseCode.trim()) {
        return 'update';
    }

    return 'create';
};

export const effectiveModuleAction = (
    row: SyllabusImportPreviewRow,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    lookups: SyllabusImportPreviewLookups,
): SyllabusImportPreviewAction => {
    const moduleCode = resolvedField(row, rowCorrections, 'moduleCode');
    const moduleTitle = resolvedField(row, rowCorrections, 'moduleTitle');

    if (moduleCode === '' && moduleTitle === '') {
        return 'skip';
    }

    if (row.moduleGroupedSkip && !moduleFieldsChanged(row, rowCorrections)) {
        return 'skip';
    }

    if (!moduleRowIsValid(row, rowCorrections, lookups)) {
        return 'fail';
    }

    if (row.moduleExists && moduleCode === row.moduleCode.trim()) {
        return 'update';
    }

    return 'create';
};

export const isRowExcluded = (rowNumber: number, excludedRowNumbers: ReadonlySet<number>): boolean => {
    return excludedRowNumbers.has(rowNumber);
};

export const computeEffectiveSummary = (
    preview: SyllabusImportPreview,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    excludedRowNumbers: ReadonlySet<number> = new Set(),
) => {
    let syllabusCreates = 0;
    let syllabusUpdates = 0;
    let moduleCreates = 0;
    let moduleUpdates = 0;
    let moduleSkips = 0;
    let failed = 0;
    let skipped = 0;

    for (const row of preview.rows) {
        if (isRowExcluded(row.rowNumber, excludedRowNumbers)) {
            skipped++;
            continue;
        }

        const syllabusAction = effectiveSyllabusAction(row, rowCorrections, preview.lookups);
        const moduleAction = effectiveModuleAction(row, rowCorrections, preview.lookups);

        if (syllabusAction === 'fail' || moduleAction === 'fail') {
            failed++;
        }

        syllabusCreates += syllabusAction === 'create' ? 1 : 0;
        syllabusUpdates += syllabusAction === 'update' ? 1 : 0;
        moduleCreates += moduleAction === 'create' ? 1 : 0;
        moduleUpdates += moduleAction === 'update' ? 1 : 0;
        moduleSkips += moduleAction === 'skip' ? 1 : 0;
    }

    return {
        total: preview.rows.length,
        syllabusCreates,
        syllabusUpdates,
        moduleCreates,
        moduleUpdates,
        moduleSkips,
        skipped,
        failed,
    };
};

export const buildRowCorrectionsPayload = (
    preview: SyllabusImportPreview,
    rowCorrections: Record<number, SyllabusImportRowCorrection>,
    excludedRowNumbers: ReadonlySet<number> = new Set(),
): Record<number, SyllabusImportRowCorrection> => {
    const payload: Record<number, SyllabusImportRowCorrection> = {};

    for (const row of preview.rows) {
        if (isRowExcluded(row.rowNumber, excludedRowNumbers)) {
            continue;
        }

        const correction: SyllabusImportRowCorrection = {};
        const fields: SyllabusImportFieldKey[] = [
            'level',
            'courseTitle',
            'courseCode',
            'semester',
            'moduleTitle',
            'moduleCode',
        ];

        for (const field of fields) {
            const resolved = resolvedField(row, rowCorrections, field);
            const original = row[field].trim();

            if (resolved !== original) {
                correction[field] = resolved;
            }
        }

        if (Object.keys(correction).length > 0) {
            payload[row.rowNumber] = correction;
        }
    }

    for (const row of preview.rows) {
        if (isRowExcluded(row.rowNumber, excludedRowNumbers)) {
            continue;
        }

        const resolved = resolvedAllSemesters(row, rowCorrections);

        if (resolved !== row.allSemesters) {
            payload[row.rowNumber] = {
                ...(payload[row.rowNumber] ?? {}),
                allSemesters: resolved,
            };
        }
    }

    return payload;
};
