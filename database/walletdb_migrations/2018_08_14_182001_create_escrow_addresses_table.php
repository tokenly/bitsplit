<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEscrowAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(Config::get('database.wallets'))->create('escrow_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->integer('offset');

            $table->string('address');
            $table->string('recovery_address');

            $table->integer('wallet_id')->unsigned();
            $table->foreign('wallet_id')->references('id')->on('escrow_wallets')->onDelete('cascade');
            $table->string('chain', 32);

            $table->integer('user_id')->unsigned();

            $table->timestamps();

            $table->index(['user_id','chain']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escrow_addresses');
    }
}
