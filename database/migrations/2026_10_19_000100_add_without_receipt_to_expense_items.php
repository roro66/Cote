<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->boolean('without_receipt')->default(false)->after('document_number');
            $table->string('without_receipt_reason')->nullable()->after('without_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn(['without_receipt', 'without_receipt_reason']);
        });
    }
};
