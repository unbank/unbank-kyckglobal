<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountRefToKyckAllocationWithAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyck_allocation_with_accounts', function (Blueprint $table) {
            $table->string('account_reference')->nullable()->index();

            $table->renameColumn('account_method_id', 'disbursable_id');
            $table->renameColumn('account_method_type', 'disbursable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyck_allocation_with_accounts', function (Blueprint $table) {
            $table->dropIndex(['account_reference']);
            $table->dropColumn('account_reference');

            $table->renameColumn('disbursable_id', 'account_method_id');
            $table->renameColumn('disbursable_type', 'account_method_type');
        });
    }
}
