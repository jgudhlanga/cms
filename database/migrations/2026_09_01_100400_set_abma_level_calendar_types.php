<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const ABMA_LEVEL_NAMES = [
        'ABMA Level 3',
        'ABMA Level 4',
        'ABMA Level 5',
        'ABMA Level 6',
    ];

    public function up(): void
    {
        DB::table('levels')
            ->whereIn('name', self::ABMA_LEVEL_NAMES)
            ->update(['calendar_type' => 'abma']);
    }

    public function down(): void
    {
        DB::table('levels')
            ->whereIn('name', self::ABMA_LEVEL_NAMES)
            ->update(['calendar_type' => 'semester']);
    }
};
