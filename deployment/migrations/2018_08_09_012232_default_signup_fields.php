<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultSignupFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $fields = [
            ['name' => 'First name', 'type' => 'text', 'required' => true],
            ['name' => 'Last name', 'type' => 'text', 'required' => true],
            ['name' => 'Email', 'type' => 'text', 'required' => true],
            ['name' => 'Phone number', 'type' => 'text', 'required' => false],
            ['name' => 'Company Name', 'type' => 'text', 'required' => false],
            ['name' => 'Website URL', 'type' => 'text', 'required' => false],
            ['name' => 'Company Address', 'type' => 'text', 'required' => false],
            ['name' => 'Name of Token You Want to Distribute', 'type' => 'text', 'required' => true],
            ['name' => 'A description of the token you want to distribute', 'type' => 'textarea', 'required' => true],
            ['name' => 'Is the token you want to distribute listed on any exchanges?', 'type' => 'toggle', 'required' => true],
            ['name' => 'Which exchanges list the token you want to distribute?', 'type' => 'checkbox', 'required' => false],
        ];
        \App\Models\SignupField::insert($fields);
        /* Token exchanges*/
        $field = \App\Models\SignupField::where('name', 'Which exchanges list the token you want to distribute?')->first();
        $options = [
            ['value' => 'Bittrex', 'field_id' => $field->id],
            ['value' => 'Bitfinex', 'field_id' => $field->id],
            ['value' => 'Binance', 'field_id' => $field->id],
            ['value' => 'Kraken', 'field_id' => $field->id],
            ['value' => 'Ethex', 'field_id' => $field->id],
        ];
        \App\Models\SignupFieldOption::insert($options);
        //
        $token_field = \App\Models\SignupField::where('name', 'Is the token you want to distribute listed on any exchanges?')->first();
        $condition = new \App\Models\SignupFieldCondition();
        $condition->field_id = $field->id;
        $condition->field_to_compare_id = $token_field->id;
        $condition->value = '1';
        $condition->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
