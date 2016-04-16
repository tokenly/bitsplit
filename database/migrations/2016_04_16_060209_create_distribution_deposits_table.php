<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDistributionDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
	public function up()
	{
		Schema::create('distribution_deposits', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->integer('distribution_id')->unsigned();
			$table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('cascade');
			$table->index('distribution_id');
			$table->string('asset');
			$table->bigInteger('quantity');
			$table->string('txid');
			$table->index('txid');
			$table->boolean('confirmed')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('distribution_deposits');
	}
}
