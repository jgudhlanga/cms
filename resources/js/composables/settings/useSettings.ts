import { Link } from '@/types/ui';

export function useSettings() {
	const tabs: Array<Link> = [
		{
			transChoiceKey: 'address_type',
			url: route('address-types.index'),
		},
		{
			transChoiceKey: 'communication_mode',
			url: route('communication-methods.index'),
		},
		{
			transChoiceKey: 'country',
			url: route('countries.index'),
		},
		{
			transChoiceKey: 'gender',
			url: route('genders.index'),
		},
		{
			transChoiceKey: 'language',
			url: route('languages.index'),
		},
		{
			transChoiceKey: 'payment',
			url: route('payments-index'),
		},
		{
			transChoiceKey: 'province',
			url: route('provinces.index'),
		},
		{
			transChoiceKey: 'race',
			url: route('races.index'),
		},
        {
            transChoiceKey: 'relationship',
            url: route('relationships.index'),
        },
		{
			transChoiceKey: 'status',
			url: route('statuses.index'),
		},
		{
			transChoiceKey: 'title',
			url: route('titles.index'),
		},
	];
	return { tabs };
}
