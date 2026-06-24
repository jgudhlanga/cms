<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_MODEL = 'App\Models\Students\StudentProgram';

    private const NEW_MODEL = 'App\Models\Students\StudentApplication';

    public function up(): void
    {
        if (Schema::hasTable('student_programs')) {
            $this->renameStudentProgramSchema();
        }

        $this->updatePolymorphicTypes(self::OLD_MODEL, self::NEW_MODEL);

        $this->renamePermissions('student-programs', 'student-applications');
        $this->renamePermission('manageOwnStudentProgramDetails:students', 'manageOwnStudentApplicationDetails:students');
    }

    private function renameStudentProgramSchema(): void
    {
        Schema::table('class_lists', function (Blueprint $table): void {
            $table->dropForeign(['student_program_id']);
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->dropForeign(['student_program_id']);
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_program_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->dropForeign(['student_program_id']);
            });
        }

        Schema::table('class_lists', function (Blueprint $table): void {
            $table->renameColumn('student_program_id', 'student_application_id');
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->renameColumn('student_program_id', 'student_application_id');
        });

        Schema::table('ledgers', function (Blueprint $table): void {
            $table->renameColumn('student_program_id', 'student_application_id');
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_program_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->renameColumn('student_program_id', 'student_application_id');
            });
        }

        Schema::rename('student_programs', 'student_applications');

        Schema::table('class_lists', function (Blueprint $table): void {
            $table->foreign('student_application_id')
                ->references('id')
                ->on('student_applications');
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->foreign('student_application_id')
                ->references('id')
                ->on('student_applications');
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_application_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->foreign('student_application_id')
                    ->references('id')
                    ->on('student_applications')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_applications')) {
            return;
        }

        Schema::table('class_lists', function (Blueprint $table): void {
            $table->dropForeign(['student_application_id']);
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->dropForeign(['student_application_id']);
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_application_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->dropForeign(['student_application_id']);
            });
        }

        Schema::table('class_lists', function (Blueprint $table): void {
            $table->renameColumn('student_application_id', 'student_program_id');
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->renameColumn('student_application_id', 'student_program_id');
        });

        Schema::table('ledgers', function (Blueprint $table): void {
            $table->renameColumn('student_application_id', 'student_program_id');
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_application_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->renameColumn('student_application_id', 'student_program_id');
            });
        }

        Schema::rename('student_applications', 'student_programs');

        Schema::table('class_lists', function (Blueprint $table): void {
            $table->foreign('student_program_id')
                ->references('id')
                ->on('student_programs');
        });

        Schema::table('student_enrolments', function (Blueprint $table): void {
            $table->foreign('student_program_id')
                ->references('id')
                ->on('student_programs');
        });

        if (Schema::hasTable('application_fees') && Schema::hasColumn('application_fees', 'student_program_id')) {
            Schema::table('application_fees', function (Blueprint $table): void {
                $table->foreign('student_program_id')
                    ->references('id')
                    ->on('student_programs')
                    ->nullOnDelete();
            });
        }

        $this->updatePolymorphicTypes(self::NEW_MODEL, self::OLD_MODEL);

        $this->renamePermissions('student-applications', 'student-programs');
        $this->renamePermission('manageOwnStudentApplicationDetails:students', 'manageOwnStudentProgramDetails:students');
    }

    private function updatePolymorphicTypes(string $from, string $to): void
    {
        if (Schema::hasTable('ledgers')) {
            DB::table('ledgers')
                ->where('ledgerable_type', $from)
                ->update(['ledgerable_type' => $to]);
        }

        if (Schema::hasTable('student_notes')) {
            DB::table('student_notes')
                ->where('noteable_type', $from)
                ->update(['noteable_type' => $to]);
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', $from)
                ->update(['subject_type' => $to]);

            DB::table('activity_log')
                ->where('causer_type', $from)
                ->update(['causer_type' => $to]);
        }
    }

    private function renamePermissions(string $fromSuffix, string $toSuffix): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $actions = [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
            'import',
            'export',
            'crud-settings',
            'viewAuditTrail',
        ];

        foreach ($actions as $action) {
            $this->renamePermission("{$action}:{$fromSuffix}", "{$action}:{$toSuffix}");
        }
    }

    private function renamePermission(string $from, string $to): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $legacyPermission = DB::table('permissions')->where('name', $from)->first();

        if ($legacyPermission === null) {
            return;
        }

        if (DB::table('permissions')->where('name', $to)->exists()) {
            DB::table('permissions')->where('name', $from)->delete();

            return;
        }

        DB::table('permissions')
            ->where('name', $from)
            ->update(['name' => $to]);
    }
};
