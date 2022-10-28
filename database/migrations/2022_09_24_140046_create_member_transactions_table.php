<?php

use App\Models\Bootcamp;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_transactions', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->uuid('transaction_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('bootcamp_id');
            $table->bigInteger('price');
            $table->bigInteger('final_price');
            $table->string('status');
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('users');
            $table->foreign('bootcamp_id')->references('id')->on('bootcamps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_transactions');
    }
};
