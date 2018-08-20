<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Tokenly\AssetNameUtils\Validator as AssetValidator;

class Asset implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $asset = trim( $value ?? '');
        if(strlen($asset) == 0){
            return false;
        }
        if (!AssetValidator::isValidAssetName($asset)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid token name.';
    }
}
