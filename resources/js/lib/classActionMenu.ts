import { IconName } from '@/enums/icons';
import type { ActionMenuGroup, ActionMenuItem } from '@/types/buttons';
import { trans } from 'laravel-vue-i18n';

/**
 * Builders for the "Actions" dropdowns on the department classes screens.
 *
 * The hide/disable split lives here, on purpose, so it can be asserted in a
 * unit test rather than only in the browser:
 *
 * - A permission the viewer does not hold removes the item from the array.
 *   Rendering it greyed out would still disclose that the capability exists.
 * - A state that is merely not reached yet keeps the item and sets
 *   `disabled` + `disabledReason`, so the menu does not silently change shape
 *   as classes get generated or students get placed.
 *
 * Empty groups are dropped here; `DropdownButton` renders no trigger at all
 * when every group is gone.
 */

const withoutEmptyGroups = (groups: ActionMenuGroup[]): ActionMenuGroup[] =>
    groups.filter((group) => group.items.length > 0);

export type DepartmentClassesActionInput = {
    /** Permission: may run enrolment progression imports. */
    canManageProgressionImport: boolean;
    /** Permission: may export class lists. */
    canExportClassList: boolean;
    /** Permission: may view course work. */
    canViewCourseWork: boolean;
    /** State: at least one class has been generated for this configuration. */
    hasGeneratedClasses: boolean;
    advancePhaseUrl: string;
    completeLevelUrl: string;
    courseWorkMarksheetUrl: string;
    onExportClassLists: () => void;
};

export function buildDepartmentClassesActionGroups(input: DepartmentClassesActionInput): ActionMenuGroup[] {
    const requiresClasses = input.hasGeneratedClasses
        ? {}
        : { disabled: true, disabledReason: trans('academic_calendar.action_requires_classes') };

    // No template download here: the import screen each of these opens offers the
    // template next to its upload field, built for the action you actually chose.
    const progression: ActionMenuItem[] = input.canManageProgressionImport
        ? [
              {
                  key: 'advance-phase',
                  label: trans('academic_calendar.progression_import_action_advance_phase'),
                  description: trans('academic_calendar.action_hint_advance_phase'),
                  icon: IconName.arrow_up_circle,
                  href: input.advancePhaseUrl,
              },
              {
                  key: 'complete-level',
                  label: trans('academic_calendar.progression_import_action_complete_level'),
                  description: trans('academic_calendar.action_hint_complete_level'),
                  icon: IconName.check_done,
                  href: input.completeLevelUrl,
              },
          ]
        : [];

    const reports: ActionMenuItem[] = [];

    if (input.canExportClassList) {
        reports.push({
            key: 'export-class-lists',
            label: trans('academic_calendar.export_class_lists'),
            description: trans('academic_calendar.action_hint_export_class_lists'),
            icon: IconName.export,
            action: input.onExportClassLists,
            ...requiresClasses,
        });
    }

    if (input.canViewCourseWork) {
        reports.push({
            key: 'course-work-marksheet',
            label: trans('academic_calendar.course_work_open_marksheet'),
            description: trans('academic_calendar.action_hint_course_work_marksheet'),
            icon: IconName.list,
            href: input.courseWorkMarksheetUrl,
            ...requiresClasses,
        });
    }

    return withoutEmptyGroups([
        { key: 'progression', label: trans('academic_calendar.actions_group_progression'), items: progression },
        { key: 'reports', label: trans('academic_calendar.actions_group_reports'), items: reports },
    ]);
}

export type ClassViewActionInput = {
    /** Permission: may move students between classes / run progression. */
    canMoveStudents: boolean;
    /** Permission: may reassign a student's programme. */
    canMoveProgramme: boolean;
    /** Permission: may advance students to the next phase. */
    canAdvancePhase: boolean;
    /** Permission: may export class lists. */
    canExportClassList: boolean;
    /** Null when there is no class config to import against. */
    progressionImportUrl: string | null;
    /** Already-composed label, since it flips between complete-level and advance-phase. */
    progressionImportLabel: string;
    /** State: how many students in view can actually be advanced. */
    eligibleAdvanceCount: number;
    onProgressionImport: () => void;
    onAdvanceAllEligible: () => void;
    onAddStudent: () => void;
    onReassignProgramme: () => void;
    onExportClassList: () => void;
};

export function buildClassViewActionGroups(input: ClassViewActionInput): ActionMenuGroup[] {
    const progression: ActionMenuItem[] = [];

    // No template download here either: the import screen offers it next to the upload field.
    if (input.canMoveStudents && input.progressionImportUrl) {
        progression.push({
            key: 'progression-import',
            label: input.progressionImportLabel,
            description: trans('academic_calendar.action_hint_progression_import'),
            icon: IconName.import,
            action: input.onProgressionImport,
        });
    }

    if (input.canAdvancePhase) {
        const hasEligible = input.eligibleAdvanceCount > 0;
        progression.push({
            key: 'advance-all-eligible',
            label: hasEligible
                ? `${trans('academic_calendar.advance_all_eligible')} (${input.eligibleAdvanceCount})`
                : trans('academic_calendar.advance_all_eligible'),
            description: trans('academic_calendar.action_hint_advance_all_eligible'),
            icon: IconName.arrow_up_circle,
            action: input.onAdvanceAllEligible,
            disabled: !hasEligible,
            disabledReason: hasEligible ? undefined : trans('academic_calendar.advance_all_none_eligible'),
        });
    }

    const students: ActionMenuItem[] = [];

    if (input.canMoveStudents) {
        students.push({
            key: 'add-student',
            label: trans('academic_calendar.add_student'),
            description: trans('academic_calendar.action_hint_add_student'),
            icon: IconName.user_add,
            action: input.onAddStudent,
        });
    }

    if (input.canMoveProgramme) {
        students.push({
            key: 'reassign-programme',
            label: trans('students.reassign_programme'),
            description: trans('academic_calendar.action_hint_reassign_programme'),
            icon: IconName.edit,
            action: input.onReassignProgramme,
        });
    }

    const exports: ActionMenuItem[] = input.canExportClassList
        ? [
              {
                  key: 'export-class-list',
                  label: trans('academic_calendar.export_class_list'),
                  description: trans('academic_calendar.action_hint_export_class_lists'),
                  icon: IconName.export,
                  action: input.onExportClassList,
              },
          ]
        : [];

    return withoutEmptyGroups([
        { key: 'progression', label: trans('academic_calendar.actions_group_progression'), items: progression },
        { key: 'students', label: trans('academic_calendar.actions_group_students'), items: students },
        { key: 'exports', label: trans('academic_calendar.actions_group_reports'), items: exports },
    ]);
}
