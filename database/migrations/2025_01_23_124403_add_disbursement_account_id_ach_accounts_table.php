<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ach_accounts', function (Blueprint $table) {
            $table->bigInteger('disbursement_account_id')->after('account_type')->nullable()->unsigned()->index();
        });
    }

    public function down()
    {
        Schema::table('ach_accounts', function (Blueprint $table) {
            $table->dropColumn('disbursement_account_id');
        });
    }
};
