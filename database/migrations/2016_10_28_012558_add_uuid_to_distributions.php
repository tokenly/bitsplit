<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUuidToDistributions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distributions', function(Blueprint $table)
        {
            $table->char('uuid', 36)->unique()->nullable();
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
        Schema::table('distributions', function(Blueprint $table)
        {
            $table->dropColumn('uuid');
        });
    }
}
