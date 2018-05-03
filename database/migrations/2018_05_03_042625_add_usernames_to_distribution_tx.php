<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsernamesToDistributionTx extends Migration
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
            $table->string('fldc_usernames')->nullable();
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
            $table->dropColumn(['fldc_usernames']);
        });
    }
}
