<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKyckAllocationWithAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('kyck_allocation_with_accounts');
        Schema::create('kyck_allocation_with_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->index()->unsigned();
            $table->string('payee_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('account_type')->nullable()->index();
            $table->unsignedTinyInteger('allocation')->default(0);
            $table->nullableMorphs('account_method', 'account_method_index');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kyck_allocation_with_accounts');
    }
}
