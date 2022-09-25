<?php

use App\Models\MemberTransaction;
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
        Schema::create('xendit_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('xendit_transaction_id');
            $table->string('external_id');
            $table->unsignedBigInteger('member_id');
            $table->string('payment_method')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_destination')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->text('invoice_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('paid_amount')->nullable();
            $table->char('currency')->nullable();
            $table->string('bank')->nullable();
            $table->string('ewallet_type')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('description')->nullable();
            $table->string('event')->nullable();
            $table->bigInteger('adjusted_received_amount')->nullable();
            $table->bigInteger('fees_paid_amount')->nullable();
            $table->text('failure_code')->nullable();
            $table->dateTime('updated')->nullable();
            $table->dateTime('created')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xendit_transactions');
    }
};
