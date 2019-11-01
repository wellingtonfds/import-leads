<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads',function (Blueprint $table){
            $table->bigIncrements('id');
            $table->string('origin_source_data')->nullable();
            $table->string('status_source_data')->nullable();
            $table->bigInteger('id_source_data')->nullable();
            $table->dateTime('created_source_data')->nullable();
            $table->dateTime('updated_source_data')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->json('data')->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('leads');
    }
}
