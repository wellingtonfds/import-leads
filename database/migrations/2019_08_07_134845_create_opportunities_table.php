<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('amount')->nullable();
            $table->string('status_negotiation')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_won')->default(false);
            $table->timestamp('close_date')->nullable();
            $table->bigInteger('lead_id')->unsigned()->nullable();
            $table->bigInteger('id_source_data')->unsigned()->nullable();
            $table->bigInteger('id_campaign')->unsigned()->nullable();
            $table->integer('brand_id')->unsigned()->nullable();
            $table->json('data');
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
        Schema::dropIfExists('opportunities');
    }
}
