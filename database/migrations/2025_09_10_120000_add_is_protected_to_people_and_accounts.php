<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                if (!Schema::hasColumn('people', 'is_protected')) {
                    $table->boolean('is_protected')->default(false)->after('is_enabled');
                }
            });
        }

        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('accounts', 'is_protected')) {
                    $table->boolean('is_protected')->default(false)->after('is_fondeo');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                if (Schema::hasColumn('people', 'is_protected')) {
                    $table->dropColumn('is_protected');
                }
            });
        }

        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                if (Schema::hasColumn('accounts', 'is_protected')) {
                    $table->dropColumn('is_protected');
                }
            });
        }
    }
};
