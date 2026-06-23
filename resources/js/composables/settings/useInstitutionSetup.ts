import { Link } from '@/types/ui';

export function useInstitutionSetup() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'course',
            url: route('courses.index'),
        },
        {
            transChoiceKey: 'department',
            url: route('departments.index'),
        },
        {
            transChoiceKey: 'division',
            url: route('divisions.index'),
        },
        {
            transChoiceKey: 'grade',
            url: route('grades.index'),
        },
        {
            transChoiceKey: 'level',
            url: route('levels.index'),
        },
        {
            transChoiceKey: 'mode_of_study',
            url: route('mode-of-studies.index'),
        },
        { 
            transChoiceKey: 'assessment_type',
            url: route('assessment-types.index'),
        },
        {
            transChoiceKey: 'subject',
            url: route('subjects.index'),
        },
        {
            transChoiceKey: 'students.enrolment_status',
            url: route('student-enrolment-statuses.index'),
        },
        {
            transChoiceKey: 'academic_years.calendar_year_option',
            url: route('academic-year-options.index'),
        },
    ];
    return { tabs };
}
