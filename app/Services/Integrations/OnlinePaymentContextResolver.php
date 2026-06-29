<?php

namespace App\Services\Integrations;

use App\DTO\Integrations\OnlinePaymentContext;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\IntakePeriod;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\ApplicationFeeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class OnlinePaymentContextResolver
{
    public const SESSION_ORDER_REFERENCE_KEY = 'payment.order_reference';

    public function __construct(
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function resolveForInitiate(Request $request): OnlinePaymentContext
    {
        $feeType = FeeType::query()->findOrFail($request->integer('feeTypeId'));
        $feeTypeEnum = FeeTypeEnum::fromFeeType($feeType);

        if ($feeTypeEnum === null) {
            throw ValidationException::withMessages([
                'feeTypeId' => [__('trans.invalid_fee_type')],
            ]);
        }

        $user = $request->user();
        $ledgerable = $this->resolveLedgerable($feeTypeEnum, $user, $request);
        $intakePeriod = $this->resolveIntakePeriod($feeTypeEnum, $ledgerable);
        $studentApplicationId = $this->resolveStudentApplicationId($feeTypeEnum, $ledgerable);

        return new OnlinePaymentContext(
            feeType: $feeType,
            feeTypeEnum: $feeTypeEnum,
            ledgerable: $ledgerable,
            intakePeriod: $intakePeriod,
            studentApplicationId: $studentApplicationId,
        );
    }

    /**
     * @return array{invoice: ?Ledger, receipt: ?Ledger}
     */
    public function resolveLedgerPair(?string $orderReference = null): array
    {
        $orderReference ??= request()->query('orderReference')
            ?? request()->input('orderReference')
            ?? Session::get(self::SESSION_ORDER_REFERENCE_KEY);

        if (blank($orderReference)) {
            return ['invoice' => null, 'receipt' => null];
        }

        return PaymentHelper::getLedgerPairByOrderReference($orderReference);
    }

    public function appendOrderReferenceToUrl(string $baseUrl, string $orderReference): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.'orderReference='.urlencode($orderReference);
    }

    public function storeOrderReferenceInSession(string $orderReference): void
    {
        Session::put(self::SESSION_ORDER_REFERENCE_KEY, $orderReference);
    }

    public function postPaymentRouteForLedger(?Ledger $ledger): string
    {
        if ($ledger === null || $ledger->feeType === null) {
            return route('portal.dashboard');
        }

        $feeTypeEnum = FeeTypeEnum::fromFeeType($ledger->feeType);

        if ($feeTypeEnum === FeeTypeEnum::APPLICATION_FEE) {
            $user = auth()->user();
            if ($user?->has_student_profile) {
                return route('portal.profile.applications', ['fee_paid' => 1]);
            }
        }

        return route($feeTypeEnum?->postPaymentRoute() ?? 'portal.dashboard');
    }

    public function postFailurePaymentRouteForLedger(?Ledger $ledger): string
    {
        if ($ledger === null || $ledger->feeType === null) {
            return route('portal.dashboard');
        }

        $feeTypeEnum = FeeTypeEnum::fromFeeType($ledger->feeType);

        return route($feeTypeEnum?->postFailurePaymentRoute() ?? 'portal.dashboard');
    }

    private function resolveLedgerable(FeeTypeEnum $feeTypeEnum, User $user, Request $request): Model
    {
        return match ($feeTypeEnum->ledgerableClass()) {
            ApplicationFee::class => $this->resolveApplicationFee($user, $request),
            HostelApplication::class => $this->resolveHostelApplication($user, $request),
            StudentApplication::class => $this->resolveStudentApplication($user, $request),
            default => $user,
        };
    }

    private function resolveApplicationFee(User $user, Request $request): ApplicationFee
    {
        $applicationFeeId = $request->input('ledgerableId');

        $applicationFee = $applicationFeeId
            ? ApplicationFee::query()->find($applicationFeeId)
            : $this->applicationFeeService->forUserAndIntake($user);

        if ($applicationFee === null) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('trans.application_fee_record_required')],
            ]);
        }

        if ((int) $applicationFee->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('trans.application_fee_record_required')],
            ]);
        }

        if ($applicationFee->isPaid() && $applicationFee->student_application_id !== null) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('trans.application_fee_already_applied')],
            ]);
        }

        return $applicationFee;
    }

    private function resolveHostelApplication(User $user, Request $request): HostelApplication
    {
        $student = Student::query()
            ->where('user_id', $user->id)
            ->first();

        if ($student === null) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('hms.student_required')],
            ]);
        }

        $applicationId = $request->input('ledgerableId');

        $application = $applicationId
            ? HostelApplication::query()->find($applicationId)
            : HostelApplication::query()
                ->where('student_id', $student->id)
                ->where('status', HostelApplicationStatusEnum::AWAITING_PAYMENT)
                ->latest()
                ->first();

        if ($application === null) {
            $application = HostelApplication::query()
                ->where('student_id', $student->id)
                ->where('status', HostelApplicationStatusEnum::PARTIALLY_PAID)
                ->latest()
                ->first();
        }

        if ($application === null) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('students.accommodation_payment_application_required')],
            ]);
        }

        if ((int) $application->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('students.accommodation_payment_application_required')],
            ]);
        }

        if (! in_array($application->status, [
            HostelApplicationStatusEnum::AWAITING_PAYMENT,
            HostelApplicationStatusEnum::PARTIALLY_PAID,
        ], true)) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('students.accommodation_payment_application_required')],
            ]);
        }

        return $application;
    }

    private function resolveStudentApplication(User $user, Request $request): StudentApplication
    {
        $studentApplicationId = $request->input('ledgerableId');

        if (blank($studentApplicationId)) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('trans.ledgerable_required')],
            ]);
        }

        $studentApplication = StudentApplication::query()
            ->with('student')
            ->find($studentApplicationId);

        if ($studentApplication === null || (int) $studentApplication->student?->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'ledgerableId' => [__('trans.ledgerable_required')],
            ]);
        }

        return $studentApplication;
    }

    private function resolveIntakePeriod(FeeTypeEnum $feeTypeEnum, Model $ledgerable): IntakePeriod
    {
        if ($ledgerable instanceof ApplicationFee) {
            $ledgerable->loadMissing('intakePeriod');

            if ($ledgerable->intakePeriod !== null) {
                return $ledgerable->intakePeriod;
            }
        }

        if ($ledgerable instanceof HostelApplication) {
            $ledgerable->loadMissing('studentEnrolment.studentApplication.intakePeriod');

            if ($ledgerable->studentEnrolment?->studentApplication?->intakePeriod !== null) {
                return $ledgerable->studentEnrolment->studentApplication->intakePeriod;
            }
        }

        if ($ledgerable instanceof StudentApplication) {
            $ledgerable->loadMissing('intakePeriod');

            if ($ledgerable->intakePeriod !== null) {
                return $ledgerable->intakePeriod;
            }
        }

        return Helper::resolveIntakePeriod();
    }

    private function resolveStudentApplicationId(FeeTypeEnum $feeTypeEnum, Model $ledgerable): ?int
    {
        if ($ledgerable instanceof StudentApplication) {
            return $ledgerable->id;
        }

        if ($ledgerable instanceof HostelApplication) {
            $ledgerable->loadMissing('studentEnrolment');

            return $ledgerable->studentEnrolment?->student_application_id;
        }

        return null;
    }
}
