<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_data', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('field_id');
            $table->unsignedInteger('user_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('field_id')->references('id')->on('signup_fields')->onDelete('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_data');
    }
}
