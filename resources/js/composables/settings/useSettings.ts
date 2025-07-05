import { Link } from '@/types/ui';

export function useSettings() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'academic_level',
            url: route('academic-levels.index'),
        },
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
            transChoiceKey: 'district',
            url: route('districts.index'),
        },
        {
            transChoiceKey: 'employment_type',
            url: route('employment-types.index'),
        },
        {
            transChoiceKey: 'gender',
            url: route('genders.index'),
        },
        {
            transChoiceKey: 'id_type',
            url: route('id-types.index'),
        },
        {
            transChoiceKey: 'language',
            url: route('languages.index'),
        },
        {
            transChoiceKey: 'marital_status',
            url: route('marital-statuses.index'),
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
            transChoiceKey: 'religion',
            url: route('religions.index'),
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
            transChoiceKey: 'sponsor_type',
            url: route('sponsor-types.index'),
        },
        {
            transChoiceKey: 'title',
            url: route('titles.index'),
        },
    ];
    return { tabs };
}
