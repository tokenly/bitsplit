<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create('distributions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->dateTime('completed_at')->nullable();
			$table->integer('user_id')->unsigned()->default(0);
			$table->index('user_id');
			$table->integer('stage')->default(0);
			$table->index('stage');
			$table->boolean('complete')->default(0);
			$table->index('complete');
			$table->string('deposit_address');
			$table->index('deposit_address');
			$table->string('address_uuid');
			$table->index('address_uuid');
			$table->string('address_pubkey')->nullable();
			$table->string('network')->default('btc');
			$table->index('network');
			$table->string('asset');
			$table->bigInteger('asset_total');
			$table->bigInteger('fee_total');
			$table->bigInteger('asset_received')->default(0);
			$table->bigInteger('fee_received')->default(0);
			$table->string('label')->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('distributions');
    }
}
