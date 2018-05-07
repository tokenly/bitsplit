<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFiatTokenQuoteToDistributions extends Migration
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
            $table->string('fiat_token_quote')->nullable();
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
            $table->dropColumn(['fiat_token_quote']);
        });
    }
}
