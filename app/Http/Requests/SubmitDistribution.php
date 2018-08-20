<?php

namespace App\Http\Requests;

use App\Rules\Asset;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class SubmitDistribution extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = $this->user();
        //check if user has been approved to initiate distribution
        if(!$user->approval_admin_id AND !$user->admin) {
            Session::flash('message', 'You may not initiate a token distribution until your account is approved. Please be patient.');
            Session::flash('message-class', 'alert-danger');
            return false;
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $min_rate = config('settings.min_fee_per_byte');
        $max_rate = config('settings.max_fee_per_byte');
        $end_day_time = date('Y-m-d').' 00:00:00';
        return [
            //validate asset name (Counterparty/BTC only)
            'asset' => ['required', new Asset()],
            //Validate folding dates
            'folding_start_date' => ['required', 'before:'.$end_day_time],
            'folding_end_date' => ['required', 'before:'.$end_day_time, 'after:'.$this->input('folding_start_date')],
            'distribution_class' => 'required'
        ];
    }

    public function messages()
    {
        $min_rate = config('settings.min_fee_per_byte');
        $max_rate = config('settings.max_fee_per_byte');
        return [
            'btc_fee_rate.min' => 'Invalid custom miner fee rate. Enter a number between ' . $min_rate. ' and ' . $max_rate,
            'btc_fee_rate.max' => 'Invalid custom miner fee rate. Enter a number between ' . $min_rate. ' and ' . $max_rate
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();
        $error = reset($errors)[0];
        Session::flash('message', $error);
        Session::flash('message-class', 'alert-danger');
        throw new HttpResponseException($this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))->withErrorMessage($error));
    }
}
