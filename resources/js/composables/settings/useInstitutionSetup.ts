import { Link } from '@/types/ui';

export function useInstitutionSetup() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'application_step',
            url: route('application-steps.index'),
        },
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
            transChoiceKey: 'subject',
            url: route('subjects.index'),
        },
    ];
    return { tabs };
}
