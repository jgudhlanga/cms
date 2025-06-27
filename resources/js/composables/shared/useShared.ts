import { router } from '@inertiajs/vue3';

export const useShared = () => {
    const movePosition = (url: string, position: string | number) => {
        router.put(
            url,
            {
                position: position,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Position updated!');
                },
                onError: (errors) => {
                    console.error(errors);
                },
            },
        );
    };

    return {
        movePosition,
    };
};
