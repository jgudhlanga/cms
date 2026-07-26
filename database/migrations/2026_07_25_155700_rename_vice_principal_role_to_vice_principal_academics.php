<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'Vice Principal')->first();

        if ($role === null) {
            return;
        }

        DB::table('roles')
            ->where('id', $role->id)
            ->update([
                'name' => 'Vice Principal Academics',
                'slug' => 'vice-principal-academics',
                'description' => 'Oversees academic affairs across the college.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'Vice Principal Academics')->first();

        if ($role === null) {
            return;
        }

        DB::table('roles')
            ->where('id', $role->id)
            ->update([
                'name' => 'Vice Principal',
                'slug' => 'vice-principal',
                'description' => 'Deputy to the Principal.',
                'updated_at' => now(),
            ]);
    }
};
