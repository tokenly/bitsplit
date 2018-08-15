<?php

/**
 *  SampleId
 *  Sequential Usage:
 *    $sample_id = new SampleId();
 *    $txid = $sample_id->nextTxid();
 *    $uuid = $sample_id->nextUuid();
 *  Random Usage:
 *    $txid = SampleId::txid();
 */
class SampleId
{

    public $next_id;

    public function __construct($next_id = 1)
    {
        $this->next_id = $next_id;
    }

    public function next()
    {
        return $this->next_id++;
    }

    public function nextTxid()
    {
        return $this->txid($this->next());
    }

    public function nextUuid()
    {
        return $this->uuid($this->next());
    }

    public static function txid($number = null)
    {
        if ($number === null) {
            $number = rand(1, 99999999);
        }
        return '00000000000000000000000000000000000000000000000000000000' . sprintf('%08d', $number);
    }

    public static function uuid($number = null, $group = 0)
    {
        if ($number === null) {
            $number = rand(1, 99999999);
        }
        return '10000001-0000-0000-' . sprintf('%04d', $group) . '-0000' . sprintf('%08d', $number);
    }

    public function __get($name)
    {
        if ($name === 'next') {return $this->next();}
        return $this->$name;
    }

}
