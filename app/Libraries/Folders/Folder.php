<?php
namespace App\Libraries\Folders;

use Tokenly\CryptoQuantity\CryptoQuantity;

class Folder
{

    public $new_points;
    public $address;
    private $amount = 0.0;
    private $amount_quantity;

    public function __construct(int $new_points, string $address)
    {
        $this->new_points = $new_points;
        $this->address = $address;
    }

    public function calculateAmount(float $total_points, int $asset_amount, $proportional = true)
    {
        if ($proportional) {
            $percentage_of_points = ($this->new_points * 100) / $total_points;
            $this->amount = ($percentage_of_points * $asset_amount) / 100;
        } else {
            $this->amount = $asset_amount;
        }
    }

    public function setAmountQuantity(CryptoQuantity $amount_quantity)
    {
        $this->amount_quantity = $amount_quantity;
        return $this;
    }

    public function getAmountQuantity(): CryptoQuantity
    {
        if (isset($this->amount_quantity)) {
            return $this->amount_quantity;
        }

        return CryptoQuantity::fromFloat($this->amount);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getAmountAsSatoshis(): string
    {
        if (isset($this->amount_quantity)) {
            return $this->amount_quantity->getSatoshisString();
        }

        return bcmul((string) $this->amount, "100000000", "0");
    }
}
