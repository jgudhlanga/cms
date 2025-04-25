import { Link } from '@/types/ui';

export function useInstitutionSetup() {
	const tabs: Array<Link> = [
        {
            transChoiceKey: 'course',
            url: route('institution-setup.index'),
        },
		{
			transChoiceKey: 'department',
			url: route('institution-setup.index'),
		},
        {
            transChoiceKey: 'division',
            url: route('institution-setup.index'),
        },
        {
            transChoiceKey: 'grade',
            url: route('institution-setup.index'),
        },
        {
            transChoiceKey: 'level',
            url: route('institution-setup.index'),
        },
        {
            transChoiceKey: 'relationship',
            url: route('institution-setup.index'),
        },
        {
            transChoiceKey: 'subject',
            url: route('institution-setup.index'),
        },

	];
	return { tabs };
}
