<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignEntityToEscrowAddressLedgerEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('escrow_address_ledger_entries', function (Blueprint $table) {
            $table->string('foreign_entity', 100)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('escrow_address_ledger_entries', function (Blueprint $table) {
            $table->dropColumn('foreign_entity');
        });
    }
}
