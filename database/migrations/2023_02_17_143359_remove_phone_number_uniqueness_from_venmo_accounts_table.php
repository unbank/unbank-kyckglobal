<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePhoneNumberUniquenessFromVenmoAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('venmo_accounts', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venmo_accounts', function (Blueprint $table) {
            $table->unique(['phone_number']);
        });
    }
}
