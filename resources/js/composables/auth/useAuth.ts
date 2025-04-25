import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { z } from 'zod';

export function useAuth() {
	const validationSchema = () =>
		z.object({
			email: z.string().email(trans('trans.enter_valid_field', { field: trans_choice('trans.email', 1) })),
			password: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.password') })),
			remember: z.boolean(),
		});

	const login = (form: InertiaForm<any>) => {
		try {
			validationSchema().parse(form);
			form.post(route('login'), {
				onFinish: () => form.reset('password'),
			});
		} catch (error: any) {
			form.setError(error.format());
		}
	};

	return { validationSchema, login };
}
