import { Link } from '@/types/ui';

export function useFinanceSettings() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'finance.finance',
            url: route('finance.index'),
        },
        {
            transChoiceKey: 'finance.setting',
            url: route('finance.settings'),
        },
    ];

    return { tabs };
}
