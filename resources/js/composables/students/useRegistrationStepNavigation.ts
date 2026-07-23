import { router } from '@inertiajs/vue3';

export type RegistrationStepId =
    | 'read-instructions'
    | 'choose-track'
    | 'choose-level'
    | 'choose-programme'
    | 'verify-identity'
    | 'create-account'
    | 'pay-fee'
    | 'complete-application'
    | 'lookup'
    | 'login'
    | 'verify-passport'
    | 'track';

export function useRegistrationStepNavigation() {
    const navigateToRegistrationStep = (stepId: string) => {
        switch (stepId) {
            case 'read-instructions':
                router.visit(route('portal.create'));
                return;
            case 'choose-track':
                router.visit(route('portal.register.track'));
                return;
            case 'choose-level':
                router.visit(route('portal.register.level'));
                return;
            case 'choose-programme':
                router.visit(route('portal.register.programme'));
                return;
            case 'verify-identity':
            case 'verify-passport':
            case 'create-account':
                router.visit(route('portal.register.account'));
                return;
            case 'pay-fee':
                router.visit(route('portal.application.fee-payment'));
                return;
            case 'complete-application':
                router.visit(route('portal.application.create'));
                return;
            case 'lookup':
            case 'login':
                router.visit(route('login'));
                return;
            default:
                return;
        }
    };

    return { navigateToRegistrationStep };
}
