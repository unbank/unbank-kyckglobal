<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAchAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ach_account', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('payee_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('routing_number')->unique()->nullable();
            $table->string('account_number')->unique()->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ach_account');
    }
}
