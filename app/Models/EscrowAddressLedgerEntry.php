<?php

namespace App\Models;

use App\Models\EscrowAddress;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelApiProvider\Model\APIModel;

class EscrowAddressLedgerEntry extends APIModel
{

    protected $api_attributes = ['id'];

    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_PROMISE_CREATED = 'promise_created';
    const TYPE_PROMISE_FULFILLED = 'promise_fulfilled';
    const TYPE_BLOCKCHAIN_DELIVERY = 'blockchain_delivery';
    const TYPE_BLOCKCHAIN_DELIVERY_FEE = 'blockchain_delivery_fee';

    const CRYPTOQUANTITY_CLASS = 'Tokenly\CryptoQuantity\CryptoQuantity';


    public static function cryptoQuantityForAddress($amount, EscrowAddress $address)
    {
        $quantity_class = self::cryptoQuantityClassForAddress($address);
        return call_user_func([$quantity_class, 'fromSatoshis'], $amount);
    }
    public static function cryptoQuantityClassForAddress(EscrowAddress $address)
    {
        return self::CRYPTOQUANTITY_CLASS;
    }

    public static function typeIdToTypeDescription($type_id)
    {
        switch ($type_id) {
            case self::TYPE_DEPOSIT:
                return 'deposit';

            case self::TYPE_WITHDRAWAL:
                return 'withdrawal';

            case self::TYPE_PROMISE_CREATED:
                return 'promise created';

            case self::TYPE_PROMISE_FULFILLED:
                return 'promise fulfilled';

            case self::TYPE_BLOCKCHAIN_DELIVERY:
                return 'blockchain delivery';

            case self::TYPE_BLOCKCHAIN_DELIVERY_FEE:
                return 'blockchain delivery fee';

            default:
                return $type_id;
        }
    }

    // ------------------------------------------------------------------------


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
        return call_user_func([self::CRYPTOQUANTITY_CLASS, 'fromSatoshis'], $this->attributes['amount']);
    }

    public function getTimestampAttribute()
    {
        return $this['created_at']->getTimestamp();
    }

    public function getFormattedAmountAttribute()
    {
        return formattedTokenQuantity($this->getAmountAttribute());
    }

    public function getTypeDescriptionAttribute()
    {
        return $this->getTypeDescription();
    }

    public function getTransactionIdAttribute()
    {
        switch ($this['tx_type']) {
            case self::TYPE_DEPOSIT:
            case self::TYPE_WITHDRAWAL:
                return $this['txid'];
        }

        return "";
    }

    public function getTypeDescription()
    {
        return self::typeIdToTypeDescription($this['tx_type']);
    }

    public function escrowAddress()
    {
        return $this->belongsTo(EscrowAddress::class, 'address_id');
    }

}
