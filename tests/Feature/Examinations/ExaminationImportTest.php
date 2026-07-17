<?php

use App\Enums\Examinations\ExaminationImportStatusEnum;
use App\Jobs\Examinations\ImportExaminationResultsJob;
use App\Models\Examinations\ExaminationImport;
use App\Models\Examinations\ExaminationResult;
use App\Models\Users\User;
use App\Services\Examinations\ExaminationImportService;
use App\Support\Examinations\ExaminationDumpColumns;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Storage::fake('local');
    Mail::fake();
});

function createExaminationUser(array $permissions = ['viewAny:examinations']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function writeExaminationCsvFixture(string $relativePath, bool $blankFirstRow = true, ?string $grade = 'P'): string
{
    $lines = [];

    if ($blankFirstRow) {
        $lines[] = ',,,,,,,,,';
    }

    $lines[] = 'Discipline,Course Code,Candidate_Number,Surname,First_Names,Subject Code,Subject,Grade,Session,Course Comment';
    $lines[] = "Automotive,306/13/CR/0,1117001D00186,Gwatumba,Sihle,306/13/S01,Automobile Electrics,{$grade},44287,AWARD";
    $lines[] = 'Automotive,306/13/CR/0,1117001D00186,Gwatumba,Sihle,306/13/S02,Workshop Practice,D,43770,AWARD';

    Storage::disk('local')->put($relativePath, implode("\n", $lines)."\n");

    return Storage::disk('local')->path($relativePath);
}

it('detects header row after blank leading rows', function (): void {
    $path = writeExaminationCsvFixture('examinations/test-blank-header.csv', blankFirstRow: true);

    $index = app(ExaminationImportService::class)->headerRowIndex($path);

    expect($index)->toBe(1);
});

it('converts excel session serials to dates', function (): void {
    $date = ExaminationDumpColumns::excelSerialToDate('44287');

    expect($date)->not->toBeNull()
        ->and($date->toDateString())->toBe('2021-04-01');
});

it('formats DateTimeImmutable cell values as Y-m-d strings', function (): void {
    $row = [
        ExaminationDumpColumns::SESSION => new DateTimeImmutable('2021-04-01 00:00:00'),
    ];

    $session = ExaminationDumpColumns::cell($row, ExaminationDumpColumns::SESSION);

    expect($session)->toBe('2021-04-01')
        ->and(ExaminationDumpColumns::excelSerialToDate($session)?->toDateString())->toBe('2021-04-01');
});

it('upserts examination results on composite key', function (): void {
    $user = createExaminationUser(['viewAny:examinations', 'import:examinations']);
    $this->actingAs($user);

    $path = writeExaminationCsvFixture('examinations/uploads/first.csv', grade: 'P');

    $import = ExaminationImport::factory()->create([
        'tenant_id' => $user->tenant_id,
        'stored_path' => 'examinations/uploads/first.csv',
        'started_by' => $user->id,
        'status' => ExaminationImportStatusEnum::Pending,
    ]);

    app(ExaminationImportService::class)->processImport($import);

    expect(ExaminationResult::query()->count())->toBe(2);

    $result = ExaminationResult::query()
        ->where('candidate_number', '1117001D00186')
        ->where('subject_code', '306/13/S01')
        ->first();

    expect($result)->not->toBeNull()
        ->and($result->grade)->toBe('P')
        ->and($result->session_date?->toDateString())->toBe('2021-04-01');

    Storage::disk('local')->put(
        'examinations/uploads/second.csv',
        Storage::disk('local')->get('examinations/processed/'.basename($import->fresh()->stored_path))
            ?? ''
    );

    // Re-write fixture with updated grade and process again.
    $secondPath = writeExaminationCsvFixture('examinations/uploads/second.csv', grade: 'A');
    $secondImport = ExaminationImport::factory()->create([
        'tenant_id' => $user->tenant_id,
        'stored_path' => 'examinations/uploads/second.csv',
        'started_by' => $user->id,
        'status' => ExaminationImportStatusEnum::Pending,
    ]);

    app(ExaminationImportService::class)->processImport($secondImport);

    expect(ExaminationResult::query()->count())->toBe(2)
        ->and(
            ExaminationResult::query()
                ->where('candidate_number', '1117001D00186')
                ->where('subject_code', '306/13/S01')
                ->value('grade')
        )->toBe('A');
});

it('authorizes examination index with viewAny permission', function (): void {
    $user = createExaminationUser(['viewAny:examinations']);

    $this->actingAs($user)
        ->get(route('examinations.index'))
        ->assertSuccessful();
});

it('paginates examination results with resource meta and links', function (): void {
    $user = createExaminationUser(['viewAny:examinations']);
    $this->actingAs($user);

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'AAA0001',
        'surname' => 'Alpha',
        'subject_code' => 'AAA-S01',
        'session' => '44287',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'AAA0001',
        'surname' => 'Alpha',
        'subject_code' => 'AAA-S02',
        'session' => '44287',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'BBB0001',
        'surname' => 'Beta',
        'subject_code' => 'BBB-S01',
        'session' => '44287',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => 'BBB0001',
        'surname' => 'Beta',
        'subject_code' => 'BBB-S02',
        'session' => '44287',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.index', ['page_size' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Index')
            ->has('results.data', 2)
            ->where('results.meta.current_page', 1)
            ->where('results.meta.per_page', 2)
            ->where('results.meta.total', 4)
            ->where('results.meta.last_page', 2)
            ->where('results.data.0.candidateNumber', 'AAA0001')
            ->where('results.links.next', fn ($url) => is_string($url) && $url !== '')
            ->where('results.links.prev', null));

    $this->actingAs($user)
        ->get(route('examinations.index', ['page_size' => 2, 'page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Index')
            ->has('results.data', 2)
            ->where('results.meta.current_page', 2)
            ->where('results.data.0.candidateNumber', 'BBB0001')
            ->where('results.links.prev', fn ($url) => is_string($url) && $url !== '')
            ->where('results.links.next', null));
});

it('forbids examination index without permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('examinations.index'))
        ->assertForbidden();
});

it('queues import job on upload', function (): void {
    Bus::fake();

    $user = createExaminationUser(['import:examinations', 'viewAny:examinations']);
    $this->actingAs($user);

    $csv = "Discipline,Course Code,Candidate_Number,Surname,First_Names,Subject Code,Subject,Grade,Session,Course Comment\n";
    $csv .= "Automotive,306/13/CR/0,1117001D00186,Gwatumba,Sihle,306/13/S01,Automobile Electrics,P,44287,AWARD\n";

    $file = UploadedFile::fake()->createWithContent('exam-dump.csv', $csv);

    $this->postJson(route('examinations.import.store'), [
        'file' => $file,
    ])->assertSuccessful()
        ->assertJsonPath('import.status', 'pending');

    Bus::assertDispatched(ImportExaminationResultsJob::class, function (ImportExaminationResultsJob $job): bool {
        return $job->queue === config('examinations.queue')
            && $job->connection === config('examinations.queue_connection');
    });
    expect(ExaminationImport::query()->count())->toBe(1);
});

it('notifies both the importing user and configured recipients', function (): void {
    config()->set('examinations.notify', ['exams@example.com']);
    $user = User::factory()->create(['email' => 'lecturer@example.com']);

    $recipients = app(ExaminationImportService::class)->notifyRecipients($user);

    expect($recipients)->toBe([
        'lecturer@example.com',
        'exams@example.com',
    ]);
});

it('notifies an email only once when the importer and configured recipient match', function (): void {
    config()->set('examinations.notify', ['LECTURER@example.com']);
    $user = User::factory()->create(['email' => 'lecturer@example.com']);

    $recipients = app(ExaminationImportService::class)->notifyRecipients($user);

    expect($recipients)->toHaveCount(1)
        ->and(strtolower($recipients[0]))->toBe('lecturer@example.com');
});

it('returns json import progress with starter full name', function (): void {
    $user = createExaminationUser(['import:examinations', 'viewAny:examinations']);
    $this->actingAs($user);

    $import = ExaminationImport::factory()->create([
        'tenant_id' => $user->tenant_id,
        'started_by' => $user->id,
        'status' => ExaminationImportStatusEnum::Processing,
        'rows_total' => 100,
        'rows_processed' => 40,
        'rows_upserted' => 38,
        'rows_failed' => 2,
    ]);

    $this->getJson(route('examinations.imports.show', ['examinationImport' => $import, 'json' => 1]))
        ->assertSuccessful()
        ->assertJsonPath('import.id', $import->id)
        ->assertJsonPath('import.status', 'processing')
        ->assertJsonPath('import.rowsProcessed', 40)
        ->assertJsonPath('import.progressPercent', 40)
        ->assertJsonPath('import.startedBy.id', $user->id)
        ->assertJsonPath('import.startedBy.email', $user->email)
        ->assertJsonPath('import.startedBy.name', $user->full_name);
});

it('groups results by candidate on show page', function (): void {
    $user = createExaminationUser(['viewAny:examinations']);
    $this->actingAs($user);

    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => '1117001D00186',
        'surname' => 'Gwatumba',
        'first_names' => 'Sihle',
        'subject_code' => '306/13/S01',
        'session' => '44287',
    ]);
    ExaminationResult::factory()->create([
        'tenant_id' => $user->tenant_id,
        'candidate_number' => '1117001D00186',
        'surname' => 'Gwatumba',
        'first_names' => 'Sihle',
        'subject_code' => '306/13/S02',
        'session' => '43770',
    ]);

    $this->actingAs($user)
        ->get(route('examinations.candidates.show', '1117001D00186'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('examinations/Show')
            ->has('results', 2)
            ->where('candidate.candidateNumber', '1117001D00186'));
});
