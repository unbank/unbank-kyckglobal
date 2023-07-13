<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetAccountNumberRoutingNumberAndUserIdAsUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ach_account', function (Blueprint $table) {
            $table->dropUnique(['routing_number']);
            $table->dropUnique(['account_number']);

            $table->unique(['user_id', 'routing_number', 'account_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ach_account', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'routing_number', 'account_number']);

            $table->unique(['routing_number']);
            $table->unique(['account_number']);

        });
    }
}
