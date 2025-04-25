import { Link } from '@/types/ui';

export function useInstitutionSetup() {
	const tabs: Array<Link> = [
		{
			transChoiceKey: 'acl',
			url: route('acl.index'),
		},

	];
	return { tabs };
}
