<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_returns', 'source_purchase_return_id')) {
                $table->foreignId('source_purchase_return_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('purchase_returns')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sales_returns', 'source_purchase_return_id')) {
                $table->dropConstrainedForeignId('source_purchase_return_id');
            }
        });
    }
};
