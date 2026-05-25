import { Link } from '@/types/ui';

export function useFinanceSettings() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'finance.exchange_rate',
            url: route('finance.exchange-rates.index'),
        },
    ];

    return { tabs };
}
