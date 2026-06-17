<?php

use App\Models\Acl\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('status')->default(true)->after('description');
            $table->json('settings')->nullable()->after('status');
        });

        $defaultDashboardSettings = [
            'tabs' => [
                'overview' => true,
                'academic' => true,
                'enrolments' => true,
                'attendance' => true,
                'staff' => true,
                'finance' => true,
                'hostel' => true,
            ],
        ];

        Module::query()
            ->where('slug', 'dashboards')
            ->update(['settings' => $defaultDashboardSettings]);
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['status', 'settings']);
        });
    }
};
