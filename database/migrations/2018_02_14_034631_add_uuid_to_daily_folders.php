<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUuidToDailyFolders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_folders', function(Blueprint $table)
        {
            $table->char('uuid', 32)->nullable();
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_folders', function(Blueprint $table)
        {
            $table->dropColumn(['uuid']);
        });
    }
}
