import { Link } from '@/types/ui';

export function usePaymentSettings() {

	const tabs: Array<Link> = [
		{
			transChoiceKey: 'payment_day',
			url: route('payment-days.index')
		},
		{
			transChoiceKey: 'payment_frequency',
			url: route('payment-frequencies.index')
		},
		{
			transChoiceKey: 'payment_method',
			url: route('payment-methods.index')
		}
	];
	return { tabs };
}
