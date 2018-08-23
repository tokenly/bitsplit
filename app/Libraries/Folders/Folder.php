<?php
namespace App\Libraries\Folders;

use Models\Distribution;

class Folder
{

    public $new_points;
    public $address;
    private $amount;

    public function __construct(int $new_points, string $address)
    {
        $this->new_points = $new_points;
        $this->address = $address;
    }

    public function calculateAmount(float $total_points, int $asset_amount, $proportional=true) {
        if($proportional) {
            $percentage_of_points = ($this->new_points * 100) / $total_points;
            $this->amount = ($percentage_of_points * $asset_amount) / 100;
        } else {
            $this->amount = $asset_amount;
        }
    }

    public function getAmount(): float {
        return $this->amount;
    }
}