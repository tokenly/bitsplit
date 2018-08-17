<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEscrowAddressLedgerEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escrow_address_ledger_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->integer('address_id')->unsigned()->index();
            $table->integer('promise_id')->unsigned()->nullable();

            $table->string('tx_type');
            $table->string('tx_identifier', 100);
            $table->string('txid', 100)->index();

            $table->decimal('amount', 28, 0)->default(0);  // 0.0001
            $table->string('asset');
            $table->boolean('confirmed')->default(1);

            $table->timestamps();

            $table->unique(['tx_identifier', 'address_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escrow_address_ledger_entries');
    }
}
