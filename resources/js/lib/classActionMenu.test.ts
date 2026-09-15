import { describe, expect, it, vi } from 'vitest';
import {
    buildClassViewActionGroups,
    buildDepartmentClassesActionGroups,
    type ClassViewActionInput,
    type DepartmentClassesActionInput,
} from './classActionMenu';
import type { ActionMenuGroup, ActionMenuItem } from '@/types/buttons';

const flatten = (groups: ActionMenuGroup[]): ActionMenuItem[] => groups.flatMap((group) => group.items);
const keys = (groups: ActionMenuGroup[]): string[] => flatten(groups).map((item) => item.key);
const find = (groups: ActionMenuGroup[], key: string): ActionMenuItem | undefined =>
    flatten(groups).find((item) => item.key === key);

const departmentInput = (overrides: Partial<DepartmentClassesActionInput> = {}): DepartmentClassesActionInput => ({
    canManageProgressionImport: true,
    canExportClassList: true,
    canViewCourseWork: true,
    hasGeneratedClasses: true,
    advancePhaseUrl: '/advance',
    completeLevelUrl: '/complete',
    courseWorkMarksheetUrl: '/marksheet',
    onExportClassLists: vi.fn(),
    ...overrides,
});

const classViewInput = (overrides: Partial<ClassViewActionInput> = {}): ClassViewActionInput => ({
    canMoveStudents: true,
    canMoveProgramme: true,
    canAdvancePhase: true,
    canExportClassList: true,
    progressionImportUrl: '/import',
    progressionImportLabel: 'Continue to next phase Import',
    eligibleAdvanceCount: 12,
    onProgressionImport: vi.fn(),
    onAdvanceAllEligible: vi.fn(),
    onAddStudent: vi.fn(),
    onReassignProgramme: vi.fn(),
    onExportClassList: vi.fn(),
    ...overrides,
});

describe('buildDepartmentClassesActionGroups', () => {
    it('offers every action when permissions are held and classes exist', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput());

        expect(keys(groups)).toEqual([
            'advance-phase',
            'complete-level',
            'export-class-lists',
            'course-work-marksheet',
        ]);
        expect(flatten(groups).every((item) => item.disabled !== true)).toBe(true);
    });

    it('offers the department assessment calendar even before classes exist when the viewer may see it', () => {
        const groups = buildDepartmentClassesActionGroups(
            departmentInput({
                hasGeneratedClasses: false,
                canViewAssessmentCalendar: true,
                assessmentCalendarUrl: '/departments/1/assessment-calendars',
            }),
        );
        const item = find(groups, 'department-assessment-calendar');

        expect(item?.href).toBe('/departments/1/assessment-calendars');
        expect(item?.disabled).toBeUndefined();
    });

    it('offers coursework progress once classes exist and disables it before', () => {
        const withClasses = buildDepartmentClassesActionGroups(
            departmentInput({ canViewCourseWorkProgress: true, courseWorkProgressUrl: '/teaching/course-work-progress/5' }),
        );
        const beforeClasses = buildDepartmentClassesActionGroups(
            departmentInput({
                hasGeneratedClasses: false,
                canViewCourseWorkProgress: true,
                courseWorkProgressUrl: '/teaching/course-work-progress/5',
            }),
        );

        expect(find(withClasses, 'course-work-progress')?.href).toBe('/teaching/course-work-progress/5');
        expect(find(beforeClasses, 'course-work-progress')?.disabled).toBe(true);
        expect(keys(buildDepartmentClassesActionGroups(departmentInput()))).not.toContain('course-work-progress');
    });

    it('omits the department assessment calendar without the permission', () => {
        const groups = buildDepartmentClassesActionGroups(
            departmentInput({ canViewAssessmentCalendar: false, assessmentCalendarUrl: '/departments/1/assessment-calendars' }),
        );

        expect(keys(groups)).not.toContain('department-assessment-calendar');
    });

    // The template belongs to the import wizard, next to the upload field and
    // built for the action actually chosen there — not to this list page.
    it('does not offer a template download', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput());

        expect(keys(groups)).not.toContain('download-template');
    });

    // A permission the viewer lacks must remove the item outright. Rendering it
    // disabled would still disclose that the capability exists.
    it('omits progression actions entirely without the progression permission', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput({ canManageProgressionImport: false }));

        expect(keys(groups)).toEqual(['export-class-lists', 'course-work-marksheet']);
    });

    it('omits the export action entirely without the export permission', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput({ canExportClassList: false }));

        expect(keys(groups)).not.toContain('export-class-lists');
    });

    it('omits the marksheet action entirely without the course work permission', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput({ canViewCourseWork: false }));

        expect(keys(groups)).not.toContain('course-work-marksheet');
    });

    it('returns no groups at all when the viewer holds no permissions', () => {
        const groups = buildDepartmentClassesActionGroups(
            departmentInput({
                canManageProgressionImport: false,
                canExportClassList: false,
                canViewCourseWork: false,
            }),
        );

        expect(groups).toEqual([]);
    });

    // State, unlike permission, keeps the item visible so the menu does not
    // change shape the moment classes are generated.
    it('keeps export and marksheet visible but disabled until classes exist', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput({ hasGeneratedClasses: false }));

        expect(keys(groups)).toContain('export-class-lists');
        expect(keys(groups)).toContain('course-work-marksheet');

        for (const key of ['export-class-lists', 'course-work-marksheet']) {
            const item = find(groups, key);
            expect(item?.disabled).toBe(true);
            expect(item?.disabledReason).toBeTruthy();
        }
    });

    it('leaves progression actions enabled before any class is generated', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput({ hasGeneratedClasses: false }));

        expect(find(groups, 'complete-level')?.disabled).toBeUndefined();
        expect(find(groups, 'advance-phase')?.disabled).toBeUndefined();
    });

    it('routes each action to the right destination', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput());

        expect(find(groups, 'advance-phase')?.href).toBe('/advance');
        expect(find(groups, 'complete-level')?.href).toBe('/complete');
        expect(find(groups, 'course-work-marksheet')?.href).toBe('/marksheet');
    });

    it('gives every action a description saying what it does', () => {
        const groups = buildDepartmentClassesActionGroups(departmentInput());

        for (const item of flatten(groups)) {
            expect(item.description, `${item.key} has no description`).toBeTruthy();
            expect(item.description).not.toBe(item.label);
        }
    });

    it('drops a group once its last item is gone', () => {
        const groups = buildDepartmentClassesActionGroups(
            departmentInput({ canExportClassList: false, canViewCourseWork: false }),
        );

        expect(groups).toHaveLength(1);
        expect(groups[0].key).toBe('progression');
    });
});

describe('buildClassViewActionGroups', () => {
    // Like the classes list, the template lives on the import screen this menu opens.
    it('does not offer a template download', () => {
        const groups = buildClassViewActionGroups(classViewInput());

        expect(keys(groups)).not.toContain('download-template');
    });

    it('offers every action when permissions are held', () => {
        const groups = buildClassViewActionGroups(classViewInput());

        expect(keys(groups)).toEqual([
            'progression-import',
            'advance-all-eligible',
            'add-student',
            'reassign-programme',
            'export-class-list',
        ]);
    });

    it('interpolates the eligible count into the advance-all label', () => {
        const groups = buildClassViewActionGroups(classViewInput({ eligibleAdvanceCount: 12 }));

        expect(find(groups, 'advance-all-eligible')?.label).toContain('(12)');
        expect(find(groups, 'advance-all-eligible')?.disabled).toBe(false);
    });

    it('keeps advance-all visible but disabled when nobody is eligible', () => {
        const groups = buildClassViewActionGroups(classViewInput({ eligibleAdvanceCount: 0 }));

        const item = find(groups, 'advance-all-eligible');
        expect(item).toBeDefined();
        expect(item?.disabled).toBe(true);
        expect(item?.disabledReason).toBeTruthy();
        expect(item?.label).not.toContain('(0)');
    });

    it('omits advance-all entirely without the advance permission', () => {
        const groups = buildClassViewActionGroups(classViewInput({ canAdvancePhase: false }));

        expect(keys(groups)).not.toContain('advance-all-eligible');
    });

    it('omits reassign and export entirely without their permissions', () => {
        const groups = buildClassViewActionGroups(
            classViewInput({ canMoveProgramme: false, canExportClassList: false }),
        );

        expect(keys(groups)).not.toContain('reassign-programme');
        expect(keys(groups)).not.toContain('export-class-list');
    });

    // The URL is null when there is no class config to import against, which
    // is a missing target rather than a state the user can reach.
    it('omits the progression import when its URL is unavailable', () => {
        const groups = buildClassViewActionGroups(classViewInput({ progressionImportUrl: null }));

        expect(keys(groups)).not.toContain('progression-import');
    });

    it('omits progression actions without the move-students permission', () => {
        const groups = buildClassViewActionGroups(classViewInput({ canMoveStudents: false }));

        expect(keys(groups)).not.toContain('progression-import');
        expect(keys(groups)).not.toContain('add-student');
    });

    // Add student replaced the standalone toolbar button, so it must still open the modal.
    it('opens the add students modal from the students group', () => {
        const onAddStudent = vi.fn();
        const groups = buildClassViewActionGroups(classViewInput({ onAddStudent }));

        expect(groups.find((group) => group.key === 'students')?.items[0].key).toBe('add-student');
        find(groups, 'add-student')?.action?.();
        expect(onAddStudent).toHaveBeenCalledOnce();
    });

    it('uses the caller-composed progression import label', () => {
        const groups = buildClassViewActionGroups(classViewInput({ progressionImportLabel: 'Mark level completed Import' }));

        expect(find(groups, 'progression-import')?.label).toBe('Mark level completed Import');
    });

    it('gives every action a description saying what it does', () => {
        const groups = buildClassViewActionGroups(classViewInput());

        for (const item of flatten(groups)) {
            expect(item.description, `${item.key} has no description`).toBeTruthy();
            expect(item.description).not.toBe(item.label);
        }
    });

    // Both fields are present on a disabled item; the component shows the reason.
    it('keeps the description alongside the reason when disabled', () => {
        const groups = buildClassViewActionGroups(classViewInput({ eligibleAdvanceCount: 0 }));
        const item = find(groups, 'advance-all-eligible');

        expect(item?.description).toBeTruthy();
        expect(item?.disabledReason).toBeTruthy();
        expect(item?.description).not.toBe(item?.disabledReason);
    });

    it('returns no groups at all when the viewer holds no permissions', () => {
        const groups = buildClassViewActionGroups(
            classViewInput({
                canMoveStudents: false,
                canMoveProgramme: false,
                canAdvancePhase: false,
                canExportClassList: false,
            }),
        );

        expect(groups).toEqual([]);
    });
});
