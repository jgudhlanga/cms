import { errorAlert } from '@/lib/alerts';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

export const useShared = () => {
    const movePosition = (url: string, position: string | number) => {
        router.put(
            url,
            { position },
            {
                preserveScroll: true,
                onError: () => {
                    errorAlert(trans('trans.reorder_error_description'));
                },
            },
        );
    };

    return {
        movePosition,
    };
};
