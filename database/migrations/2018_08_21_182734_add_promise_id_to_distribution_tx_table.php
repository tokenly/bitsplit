<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromiseIdToDistributionTxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_tx', function (Blueprint $table) {
            $table->integer('promise_id')->unsigned()->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_tx', function (Blueprint $table) {
            $table->dropColumn('promise_id');

        });
    }
}
