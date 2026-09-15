<?php

namespace App\Console\Commands\Maintenance;

use App\Support\Media\ProofOfPaymentMedia;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MoveProofsOfPaymentToPrivateDiskCommand extends Command
{
    protected $signature = 'media:secure-proofs-of-payment
        {--dry-run : List what would move without changing anything}';

    protected $description = 'Move proof-of-payment uploads made before the private disk existed off the public disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $target = Storage::disk(ProofOfPaymentMedia::DISK);
        $moved = 0;
        $skipped = 0;

        $this->proofsOutsidePrivateDisk()->chunkById(100, function (Collection $mediaItems) use ($dryRun, $target, &$moved, &$skipped): void {
            foreach ($mediaItems as $media) {
                /** @var Media $media */
                $source = Storage::disk($media->disk);
                // The default path generator keeps a file and its conversions under the media id, so ids never change.
                $directory = (string) $media->id;
                $files = $source->allFiles($directory);

                if ($files === []) {
                    $this->warn("Media {$media->id}: no files found on the {$media->disk} disk, left as is.");
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("Would move media {$media->id} ({$media->collection_name}) from the {$media->disk} disk.");
                    $moved++;

                    continue;
                }

                foreach ($files as $file) {
                    if (! $target->put($file, (string) $source->get($file))) {
                        $this->error("Media {$media->id}: could not write {$file}, left on the {$media->disk} disk.");
                        $skipped++;

                        continue 2;
                    }
                }

                Media::query()->whereKey($media->id)->update([
                    'disk' => ProofOfPaymentMedia::DISK,
                    'conversions_disk' => ProofOfPaymentMedia::DISK,
                ]);
                $source->deleteDirectory($directory);
                $moved++;
            }
        });

        $this->info(($dryRun ? 'Would move' : 'Moved')." {$moved} proof-of-payment upload(s); skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return Builder<Media>
     */
    private function proofsOutsidePrivateDisk(): Builder
    {
        return Media::query()
            ->where('disk', '!=', ProofOfPaymentMedia::DISK)
            ->where(function (Builder $query): void {
                foreach (ProofOfPaymentMedia::COLLECTIONS as $modelClass => $collections) {
                    $query->orWhere(fn (Builder $match) => $match
                        ->whereIn('model_type', array_unique([$modelClass, (new $modelClass)->getMorphClass()]))
                        ->whereIn('collection_name', $collections));
                }
            });
    }
}
