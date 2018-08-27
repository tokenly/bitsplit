<?php

namespace App\Models;

use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelApiProvider\Model\APIModel;

class FeeRecoveryLedgerEntry extends APIModel
{

    protected $api_attributes = ['id'];

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';

    public static function typeIdToTypeDescription($type_id)
    {
        switch ($type_id) {
            case self::TYPE_DEPOSIT:
                return 'deposit';

            case self::TYPE_WITHDRAWAL:
                return 'withdrawal';

            default:
                return $type_id;
        }
    }

    public function setAmountAttribute($amount)
    {
        if ($amount instanceof CryptoQuantity) {
            $this->attributes['amount'] = $amount->getSatoshisString();
        } else {
            $this->attributes['amount'] = $amount;
        }
    }

    /**
     * gets the amount
     * @return CryptoQuantity Amount as a CryptoQuantity class
     */
    public function getAmountAttribute()
    {
        return CryptoQuantity::fromSatoshis($this->attributes['amount']);
    }

    public function getFormattedAmountAttribute()
    {
        return formattedTokenQuantity($this->getAmountAttribute());
    }

    public function getTypeDescriptionAttribute()
    {
        return $this->getTypeDescription();
    }

    public function getTypeDescription()
    {
        return self::typeIdToTypeDescription($this['tx_type']);
    }

}
