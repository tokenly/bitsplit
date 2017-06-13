<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_folders', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->bigInteger('new_credit');
            $table->bigInteger('total_credit');
            $table->integer('team');
            $table->string('bitcoin_address', 40);
            $table->string('reward_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('daily_folders');
    }
}
