<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAccountDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_account_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('company_address')->nullable();
            $table->string('token_name')->nullable();
            $table->text('token_description')->nullable();
            $table->text('token_exchanges_listed')->nullable();

            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('phone_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_account_datas');
    }
}
