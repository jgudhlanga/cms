import { router } from '@inertiajs/vue3';

export function usePortalLevelSelection(selectLevelRoute = 'portal.application.select-level') {
    const selectLevel = (levelId: string, intakePeriodId?: number | null, requiresIntakeSelection = false) => {
        const payload: Record<string, string | number> = {
            level_id: levelId,
        };

        if (intakePeriodId) {
            payload.intake_period_id = intakePeriodId;
        }

        router.post(route(selectLevelRoute), payload, {
            onError: () => {
                if (requiresIntakeSelection && !intakePeriodId) {
                    return;
                }
            },
        });
    };

    return { selectLevel };
}
