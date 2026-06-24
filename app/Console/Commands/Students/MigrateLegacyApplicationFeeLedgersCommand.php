<?php

namespace App\Console\Commands\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Users\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyApplicationFeeLedgersCommand extends Command
{
    protected $signature = 'application-fees:migrate-legacy-ledgers {--dry-run : Report changes without writing}';

    protected $description = 'Migrate legacy User-ledgers for application fees to ApplicationFee records';

    public function handle(): int
    {
        $feeType = FeeType::query()->where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->first();

        if ($feeType === null) {
            $this->error('Application fee type not found.');

            return self::FAILURE;
        }

        $ledgers = Ledger::query()
            ->where('ledgerable_type', User::class)
            ->where('fee_type_id', $feeType->id)
            ->whereNull('student_application_id')
            ->orderBy('id')
            ->get();

        if ($ledgers->isEmpty()) {
            $this->info('No legacy application fee ledgers found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;

        DB::transaction(function () use ($ledgers, $dryRun, &$migrated) {
            foreach ($ledgers->groupBy(fn (Ledger $ledger) => $ledger->ledgerable_id.'-'.$ledger->intake_period_id) as $group) {
                /** @var Ledger $sample */
                $sample = $group->first();
                $user = User::query()->find($sample->ledgerable_id);

                if ($user === null) {
                    continue;
                }

                $intakePeriod = IntakePeriod::query()->find($sample->intake_period_id);

                if ($intakePeriod === null) {
                    continue;
                }

                $applicationFee = ApplicationFee::query()->firstOrNew([
                    'user_id' => $user->id,
                    'intake_period_id' => $intakePeriod->id,
                ]);

                $isPaid = $group->contains(
                    fn (Ledger $ledger) => $ledger->type === 'receipt' && $ledger->payment_status === 'paid'
                );

                $attributes = [
                    'tenant_id' => $user->tenant_id,
                    'status' => $isPaid
                        ? ApplicationFeeStatusEnum::PAID
                        : ApplicationFeeStatusEnum::AWAITING_PAYMENT,
                ];

                if ($dryRun) {
                    $this->line("Would migrate user {$user->id} intake {$intakePeriod->id} ({$group->count()} ledgers)");
                    $migrated++;

                    continue;
                }

                $applicationFee->fill($attributes);
                $applicationFee->save();

                Ledger::query()
                    ->whereIn('id', $group->pluck('id'))
                    ->update([
                        'ledgerable_type' => ApplicationFee::class,
                        'ledgerable_id' => $applicationFee->id,
                    ]);

                $migrated++;
            }
        });

        $this->info(($dryRun ? 'Would migrate' : 'Migrated')." {$migrated} application fee group(s).");

        return self::SUCCESS;
    }
}
