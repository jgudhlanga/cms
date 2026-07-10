import type { HostelApplicationStatus } from '@/types/hms';

export type HostelApplicationProgressStepState = 'pending' | 'active' | 'completed';

export type HostelApplicationProgressStep = {
    key: 'submitted' | 'payment' | 'allocation';
    labelKey: string;
    state: HostelApplicationProgressStepState;
};

export function hostelApplicationProgressSteps(
    status: HostelApplicationStatus,
    isFullyPaid = false,
): HostelApplicationProgressStep[] {
    const submittedState = status === 'pending' ? 'active' : 'completed';

    let paymentState: HostelApplicationProgressStepState = 'pending';

    if (status === 'paid' || isFullyPaid) {
        paymentState = 'completed';
    } else if (status === 'awaiting-payment' || status === 'partially-paid') {
        paymentState = 'active';
    } else if (status !== 'pending') {
        paymentState = 'completed';
    }

    let allocationState: HostelApplicationProgressStepState = 'pending';

    if (status === 'paid' || (isFullyPaid && ['awaiting-payment', 'partially-paid', 'paid'].includes(status))) {
        allocationState = 'active';
    }

    return [
        {
            key: 'submitted',
            labelKey: 'students.accommodation_progress_submitted',
            state: submittedState,
        },
        {
            key: 'payment',
            labelKey: 'students.accommodation_progress_payment',
            state: paymentState,
        },
        {
            key: 'allocation',
            labelKey: 'students.accommodation_progress_allocation',
            state: allocationState,
        },
    ];
}
