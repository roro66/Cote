<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** El concepto de cuenta Fondeo se reemplazó por el ingreso a caja chica (transacción deposit). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('accounts', 'is_fondeo')) {
            DB::statement('DROP INDEX IF EXISTS accounts_one_fondeo');
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('is_fondeo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_fondeo')->default(false);
        });
    }
};
