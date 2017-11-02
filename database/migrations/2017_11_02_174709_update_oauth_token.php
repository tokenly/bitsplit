<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOauthToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('oauth_token');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->text('oauth_token')->nullable();
            });
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('oauth_token');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->char('oauth_token', 40)->nullable()->unique();
            });
        });
    }
}
