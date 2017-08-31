<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFoldingCreditcolumnToDistributionTxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_tx', function(Blueprint $table)
        {
            $table->bigInteger('folding_credit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_tx', function(Blueprint $table)
        {
            $table->dropColumn(['folding_credit']);
        });
    }
}
