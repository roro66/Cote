<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('person_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->foreignId('account_type_id')->nullable()->constrained('account_types')->nullOnDelete();
            $table->string('account_number')->nullable();
            $table->string('alias')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unicidad por persona para la combinación banco+tipo+número
            $table->unique(['person_id', 'bank_id', 'account_type_id', 'account_number'], 'uniq_personal_account_combo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_bank_accounts');
    }
};
