import { useStores } from '@/composables/core/useStores';
import HttpService from '@/services/http.service';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { z } from 'zod';

export function useAuth() {
    const validationSchema = () =>
        z.object({
            email: z.string().email(trans('trans.enter_valid_field', { field: trans_choice('trans.email', 1) })),
            password: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.password') })),
            remember_me: z.boolean(),
        });

    const login = (form: InertiaForm<any>) => {
        try {
            validationSchema().parse(form);
            HttpService.get('/sanctum/csrf-cookie').then(() => {
                form.post(route('login'), {
                    onFinish: () => form.reset('password'),
                });
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const logout = () => {
        const resetStore = useStores();
        resetStore.all();
    };

    return { validationSchema, login, logout };
}
