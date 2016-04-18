<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStageMessageToDistributions extends Migration
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
			$table->string('stage_message')->nullable();
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
			$table->dropColumn('stage_message');
		});
    }
}
