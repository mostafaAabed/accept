<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcceptOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accept_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('buyer');
            $table->nullableMorphs('product');
            $table->string('order_id');
            $table->string('currency');
            $table->integer('amount_cents');
            $table->integer('quantity')->default(1);
            $table->boolean('is_3d');
            $table->string('ref_code')->nullable();
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
        Schema::dropIfExists('accept_orders');
    }
}
