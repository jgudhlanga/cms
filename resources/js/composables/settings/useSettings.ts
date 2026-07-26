import { hasAbility } from '@/lib/permissions';
import { Link } from '@/types/ui';

type SettingsTab = Link & { show: boolean };

export function useSettings() {
    const tabs: Array<SettingsTab> = [
        {
            transChoiceKey: 'academic_level',
            url: route('academic-levels.index'),
            show: hasAbility(['viewAny:academic-levels', 'view:academic-levels']),
        },
        {
            transChoiceKey: 'address_type',
            url: route('address-types.index'),
            show: hasAbility(['viewAny:address-types', 'view:address-types']),
        },
        {
            transChoiceKey: 'communication_mode',
            url: route('communication-methods.index'),
            show: hasAbility(['viewAny:communication-methods', 'view:communication-methods']),
        },
        {
            transChoiceKey: 'country',
            url: route('countries.index'),
            show: hasAbility(['viewAny:countries', 'view:countries']),
        },
        {
            transChoiceKey: 'district',
            url: route('districts.index'),
            show: hasAbility(['viewAny:districts', 'view:districts']),
        },
        {
            transChoiceKey: 'document_type',
            url: route('document-types.index'),
            show: hasAbility(['viewAny:document-types', 'view:document-types']),
        },
        {
            transChoiceKey: 'employment_type',
            url: route('employment-types.index'),
            show: hasAbility(['viewAny:employment-types', 'view:employment-types']),
        },
        {
            transChoiceKey: 'fee_type',
            url: route('fee-types.index'),
            show: hasAbility(['viewAny:fee-types', 'view:fee-types']),
        },
        {
            transChoiceKey: 'gender',
            url: route('genders.index'),
            show: hasAbility(['viewAny:genders', 'view:genders']),
        },
        {
            transChoiceKey: 'id_type',
            url: route('id-types.index'),
            show: hasAbility(['viewAny:id-types', 'view:id-types']),
        },
        {
            transChoiceKey: 'language',
            url: route('languages.index'),
            show: hasAbility(['viewAny:languages', 'view:languages']),
        },
        {
            transChoiceKey: 'marital_status',
            url: route('marital-statuses.index'),
            show: hasAbility(['viewAny:marital-statuses', 'view:marital-statuses']),
        },
        {
            transChoiceKey: 'payment',
            url: route('payments-index'),
            show: hasAbility([
                'viewAny:payment-methods',
                'view:payment-methods',
                'viewAny:payment-days',
                'view:payment-days',
                'viewAny:payment-frequencies',
                'view:payment-frequencies',
            ]),
        },
        {
            transChoiceKey: 'province',
            url: route('provinces.index'),
            show: hasAbility(['viewAny:provinces', 'view:provinces']),
        },
        {
            transChoiceKey: 'race',
            url: route('races.index'),
            show: hasAbility(['viewAny:races', 'view:races']),
        },
        {
            transChoiceKey: 'religion',
            url: route('religions.index'),
            show: hasAbility(['viewAny:religions', 'view:religions']),
        },
        {
            transChoiceKey: 'relationship',
            url: route('relationships.index'),
            show: hasAbility(['viewAny:relationships', 'view:relationships']),
        },
        {
            transChoiceKey: 'status',
            url: route('statuses.index'),
            show: hasAbility(['viewAny:statuses', 'view:statuses']),
        },
        {
            transChoiceKey: 'sponsor_type',
            url: route('sponsor-types.index'),
            show: hasAbility(['viewAny:sponsor-types', 'view:sponsor-types']),
        },
        {
            transChoiceKey: 'title',
            url: route('titles.index'),
            show: hasAbility(['viewAny:titles', 'view:titles']),
        },
        {
            transChoiceKey: 'workflow_step',
            url: route('workflow-steps.index'),
            show: hasAbility(['viewAny:workflow-steps', 'view:workflow-steps']),
        },
        {
            transChoiceKey: 'workflow_step_action',
            url: route('workflow-step-actions.index'),
            show: hasAbility(['viewAny:workflow-step-actions', 'view:workflow-step-actions']),
        },
    ];

    const visibleTabs = tabs.filter((tab) => tab.show);

    return { tabs: visibleTabs };
}
