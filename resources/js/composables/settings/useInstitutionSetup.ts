import { hasAbility } from '@/lib/permissions';
import { Link } from '@/types/ui';

type InstitutionSetupTab = Link & { show: boolean };

export function useInstitutionSetup() {
    const configTabs: Array<InstitutionSetupTab> = [
        {
            transChoiceKey: 'intake_period',
            url: route('intake-periods.index'),
            show: hasAbility(['viewAny:intake-periods', 'view:intake-periods']),
        },
        {
            transChoiceKey: 'document_template',
            url: route('document-templates.index'),
            show: hasAbility('viewAny:document-templates'),
        },
        {
            transChoiceKey: 'fee_levy_structure',
            url: route('fee-structures.index'),
            show: hasAbility('viewAny:fee-structures'),
        },
        {
            transChoiceKey: 'application_offerings.menu',
            url: route('application-offerings.index'),
            show: hasAbility('manage:online-application-catalogue'),
        },
        {
            transKey: 'institution_features',
            url: route('institution-features.index'),
            show: hasAbility('manage:institution-features'),
        },
        {
            transChoiceKey: 'academic_calendar.academic_calendar',
            url: route('academic-calendars.index'),
            show: hasAbility('viewAny:academic-calendars'),
        },
    ];

    const dropdownTabs: Array<InstitutionSetupTab> = [
        {
            transChoiceKey: 'course',
            url: route('courses.index'),
            show: hasAbility(['viewAny:courses', 'view:courses']),
        },
        {
            transChoiceKey: 'department',
            url: route('departments.index'),
            show: hasAbility(['viewAny:departments', 'view:departments']),
        },
        {
            transChoiceKey: 'division',
            url: route('divisions.index'),
            show: hasAbility(['viewAny:divisions', 'view:divisions']),
        },
        {
            transChoiceKey: 'grade',
            url: route('grades.index'),
            show: hasAbility(['viewAny:grades', 'view:grades']),
        },
        {
            transChoiceKey: 'level',
            url: route('levels.index'),
            show: hasAbility(['viewAny:levels', 'view:levels']),
        },
        {
            transChoiceKey: 'mode_of_study',
            url: route('mode-of-studies.index'),
            show: hasAbility(['viewAny:mode-of-studies', 'view:mode-of-studies']),
        },
        {
            transChoiceKey: 'assessment_type',
            url: route('assessment-types.index'),
            show: hasAbility(['viewAny:assessment-types', 'view:assessment-types']),
        },
        {
            transChoiceKey: 'subject',
            url: route('subjects.index'),
            show: hasAbility(['viewAny:subjects', 'view:subjects']),
        },
        {
            transChoiceKey: 'students.enrolment_status',
            url: route('student-enrolment-statuses.index'),
            show: hasAbility(['viewAny:student-enrolment-statuses', 'view:student-enrolment-statuses']),
        },
        {
            transChoiceKey: 'academic_years.semester',
            url: route('semesters.index'),
            show: hasAbility(['viewAny:semesters', 'view:semesters']),
        },
    ];

    const visibleTabs = [...configTabs, ...dropdownTabs].filter((tab) => tab.show);

    return { configTabs, dropdownTabs, visibleTabs };
}
