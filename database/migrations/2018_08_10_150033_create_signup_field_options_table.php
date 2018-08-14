<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSignupFieldOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signup_field_options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('value');
            $table->unsignedInteger('field_id');
            $table->timestamps();

            $table->foreign('field_id')->references('id')->on('signup_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signup_field_options');
    }
}
