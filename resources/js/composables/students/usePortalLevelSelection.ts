import { router } from '@inertiajs/vue3';

export function usePortalLevelSelection() {
    const selectLevel = (levelId: string, intakePeriodId?: number | null, requiresIntakeSelection = false) => {
        const payload: Record<string, string | number> = {
            level_id: levelId,
        };

        if (intakePeriodId) {
            payload.intake_period_id = intakePeriodId;
        }

        router.post(route('portal.application.select-level'), payload, {
            onError: () => {
                if (requiresIntakeSelection && !intakePeriodId) {
                    return;
                }
            },
        });
    };

    return { selectLevel };
}
