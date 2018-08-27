<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeeRecoveryLedgerEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_recovery_ledger_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->string('tx_type');

            $table->decimal('amount', 8, 0)->default(0);  // 0.0001
            $table->string('asset');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fee_recovery_ledger_entries');
    }
}
