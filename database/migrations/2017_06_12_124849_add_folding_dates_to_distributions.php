<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFoldingDatesToDistributions extends Migration
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
            $table->date('folding_start_date');
            $table->date('folding_end_date');
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
            $table->dropColumn(['folding_start_date', 'folding_end_date']);
        });
    }
}
