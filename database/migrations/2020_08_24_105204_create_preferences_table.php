<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->enum('customer_type', ['Only Retailers', 'Individual Customer', 'Both'])->nullable();
            $table->enum('visibility', ['yes', 'no'])->nullable();
            $table->enum('SKU', ['yes', 'no'])->nullable();
            $table->enum('product_code', ['yes', 'no'])->nullable();
            $table->enum('display_rate', ['yes', 'no'])->nullable();
            $table->enum('display_mrp', ['yes', 'no'])->nullable();
            $table->enum('display_margin', ['yes', 'no'])->nullable();
            $table->text('general_info')->nullable();
            $table->text('categories')->nullable();
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
        Schema::dropIfExists('preferences');
    }
}
