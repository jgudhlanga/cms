<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')
            ->where('title', 'Acl')
            ->update([
                'title' => 'Rbac',
                'slug' => 'rbac',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('title', 'Rbac')
            ->update([
                'title' => 'Acl',
                'slug' => 'acl',
                'updated_at' => now(),
            ]);
    }
};
