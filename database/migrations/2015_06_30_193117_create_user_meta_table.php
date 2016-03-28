<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserMetaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_meta', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->integer('userId')->unsigned();
			$table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
			$table->index('userId');
			$table->string('metaKey');
			$table->index('metaKey');
			$table->longText('value');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_meta');
	}

}
