<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_fondeo')->default(false)->after('is_enabled');
        });

        // Create a partial unique index so only one account can be marked as is_fondeo = true (Postgres)
        // Use DB::statement to run raw SQL for portability with Postgres.
        try {
            \Illuminate\Support\Facades\DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS accounts_one_fondeo ON accounts ((is_fondeo)) WHERE is_fondeo = true;");
        } catch (\Throwable $e) {
            // If the DB doesn't support partial indexes, ignore — app-level checks will still enforce uniqueness.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index if exists
        try {
            \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS accounts_one_fondeo;");
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('is_fondeo');
        });
    }
};
