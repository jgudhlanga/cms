<?php

declare(strict_types=1);

use App\Support\Documents\MigrateDocumentTemplatesToOfferLetterTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow a clean retry after a partial run (MySQL 64-char FK name limit).
        Schema::dropIfExists('offer_letter_template_level');
        Schema::dropIfExists('offer_letter_template_institution_department');
        Schema::dropIfExists('offer_letter_templates');

        Schema::create('offer_letter_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intake_period_id')->constrained('intake_periods')->cascadeOnDelete();
            $table->string('name');
            $table->text('helper_description')->nullable();
            $table->foreignId('mode_of_study_id')->nullable()->constrained('mode_of_studies')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->decimal('tuition_override', 12, 2)->nullable();
            $table->string('header_line_1')->nullable();
            $table->string('header_line_2')->nullable();
            $table->string('header_address_line_1')->nullable();
            $table->string('header_address_line_2')->nullable();
            $table->string('header_telephone')->nullable();
            $table->string('header_email')->nullable();
            $table->string('header_website')->nullable();
            $table->string('header_logo_1')->nullable();
            $table->string('header_logo_2')->nullable();
            $table->longText('body')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'intake_period_id', 'name'],
                'olt_tenant_intake_name_unique',
            );
        });

        Schema::create('offer_letter_template_institution_department', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('offer_letter_template_id');
            $table->unsignedBigInteger('institution_department_id');

            $table->foreign('offer_letter_template_id', 'olt_dept_template_fk')
                ->references('id')
                ->on('offer_letter_templates')
                ->cascadeOnDelete();
            $table->foreign('institution_department_id', 'olt_dept_institution_fk')
                ->references('id')
                ->on('institution_departments')
                ->cascadeOnDelete();

            $table->unique(
                ['offer_letter_template_id', 'institution_department_id'],
                'olt_department_unique',
            );
        });

        Schema::create('offer_letter_template_level', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('offer_letter_template_id');
            $table->unsignedBigInteger('level_id');

            $table->foreign('offer_letter_template_id', 'olt_level_template_fk')
                ->references('id')
                ->on('offer_letter_templates')
                ->cascadeOnDelete();
            $table->foreign('level_id', 'olt_level_level_fk')
                ->references('id')
                ->on('levels')
                ->cascadeOnDelete();

            $table->unique(['offer_letter_template_id', 'level_id'], 'olt_level_unique');
        });

        (new MigrateDocumentTemplatesToOfferLetterTemplates)->execute();

        $scopeColumns = array_values(array_filter([
            'institution_department_id',
            'level_id',
            'course_id',
            'mode_of_study_id',
            'tuition_override',
            'scope_fingerprint',
        ], fn (string $column): bool => Schema::hasColumn('document_templates', $column)));

        if ($scopeColumns === []) {
            return;
        }

        Schema::table('document_templates', function (Blueprint $table) use ($scopeColumns): void {
            foreach (['institution_department_id', 'level_id', 'course_id', 'mode_of_study_id'] as $foreignColumn) {
                if (in_array($foreignColumn, $scopeColumns, true)) {
                    $table->dropForeign([$foreignColumn]);
                }
            }

            if (
                in_array('scope_fingerprint', $scopeColumns, true)
                && Schema::hasIndex('document_templates', 'document_templates_scope_fingerprint_idx')
            ) {
                $table->dropIndex('document_templates_scope_fingerprint_idx');
            }

            $table->dropColumn($scopeColumns);
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->foreignId('institution_department_id')
                ->nullable()
                ->after('intake_period_id')
                ->constrained('institution_departments')
                ->nullOnDelete();
            $table->foreignId('level_id')
                ->nullable()
                ->after('institution_department_id')
                ->constrained('levels')
                ->nullOnDelete();
            $table->foreignId('course_id')
                ->nullable()
                ->after('level_id')
                ->constrained('courses')
                ->nullOnDelete();
            $table->foreignId('mode_of_study_id')
                ->nullable()
                ->after('course_id')
                ->constrained('mode_of_studies')
                ->nullOnDelete();
            $table->decimal('tuition_override', 12, 2)
                ->nullable()
                ->after('mode_of_study_id');
            $table->string('scope_fingerprint', 64)
                ->nullable()
                ->after('tuition_override');
            $table->index('scope_fingerprint', 'document_templates_scope_fingerprint_idx');
        });

        Schema::dropIfExists('offer_letter_template_level');
        Schema::dropIfExists('offer_letter_template_institution_department');
        Schema::dropIfExists('offer_letter_templates');
    }
};
