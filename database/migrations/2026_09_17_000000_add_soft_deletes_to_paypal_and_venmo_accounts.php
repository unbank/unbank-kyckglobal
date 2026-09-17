<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('paypal_accounts', 'deleted_at')) {
            Schema::table('paypal_accounts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('venmo_accounts', 'deleted_at')) {
            Schema::table('venmo_accounts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paypal_accounts', 'deleted_at')) {
            Schema::table('paypal_accounts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('venmo_accounts', 'deleted_at')) {
            Schema::table('venmo_accounts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
