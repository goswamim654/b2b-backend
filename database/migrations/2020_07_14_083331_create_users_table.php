<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_code');
            $table->bigInteger('mobile_number')->unique()->notNullable();
            $table->integer('otp')->notNullable();
            $table->integer('is_otp_verified')->default(0)->comment('0-not verified, 1-verified');
            $table->integer('status')->default(0)->comment('0-inactive , 1-active');
            $table->char('user_type')->comment('b-buyer , s-seller, g-guest, a-admin');
            $table->string('password')->notNullable();
            $table->string('business_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('other')->nullable();
            $table->text('business_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();
            $table->string('gst')->nullable();
            $table->string('contact_numbers')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
