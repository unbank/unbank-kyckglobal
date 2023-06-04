<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayeeIdToPayeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('payees');
        Schema::dropIfExists('kyck_payees');
        Schema::create('payees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->tinyInteger('verified')->default(0);
            $table->string('status')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_active');
            $table->string('service_provider');
            $table->string('payee_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });

//        Schema::table('users', function (Blueprint $table) {
//            $table->string('phone_number')->nullable()->change();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payees');
        Schema::create('kyck_payees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->tinyInteger('verified')->default(0);
            $table->string('status')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_active');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

}
