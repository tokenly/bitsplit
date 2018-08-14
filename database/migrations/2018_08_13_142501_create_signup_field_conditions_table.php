<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSignupFieldConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signup_field_conditions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('field_id');
            $table->unsignedInteger('field_to_compare_id');
            $table->text('value');
            $table->timestamps();

            $table->foreign('field_id')->references('id')->on('signup_fields')->onDelete('cascade');
            $table->foreign('field_to_compare_id')->references('id')->on('signup_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signup_field_conditions');
    }
}
