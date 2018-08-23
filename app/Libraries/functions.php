<?php

use Tokenly\CryptoQuantity\CryptoQuantity;

function FLDCAssetName() {
    return env('SUBSTATION_USE_LIVENET', true) ? 'FLDC' : 'TESTFLDC';
}

function genSlug($str)
{
    $url = strtolower(trim($str));
    $url = strip_tags($url);
    $url = preg_replace("/[^a-zA-Z0-9[:space:]\/s-]/", "", $url);
    $url = preg_replace("/(-| |\/)+/","-",$url);
    return $url;
}

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        @$sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

function timestamp()
{
	return date('Y-m-d H:i:s');
}


/**
 * Converts a quantity to a formatted string like:
 * 1
 * 1.25
 * 1,000
 * 1,000.25
 * @param  CryptoQuantity|float $q A quantity object or float
 * @return string a formatted string
 */
function formattedTokenQuantity($q, $with_commas = true) {
    $mantissa_string = '';
    $scale_string = '';
    if (!($q instanceof CryptoQuantity)) {
        // coerce into a crypto quantity
        $q = CryptoQuantity::fromFloat($q);
    }

    $string = $q->getSatoshisString();
    $precision = $q->getPrecision();

    // strip negative
    $negative_prefix = '';
    if (substr($string, 0, 1) == '-') {
        $negative_prefix = '-';
        $string = substr($string, 1);
    }


    $all_zeros = str_repeat('0', $precision);

    $scale_string = substr($string, 0, 0-$precision);
    $mantissa_string = substr($string, 0-$precision);

    if ($with_commas) {
        $scale_string = number_format(floatval($scale_string));
    } else {
        $scale_string = (string) floatval($scale_string);
    }

    $mantissa_string = str_pad($mantissa_string, $precision, '0', STR_PAD_LEFT);

    if ($mantissa_string == $all_zeros) {
        return $negative_prefix.$scale_string;
    } else {
        return $negative_prefix.$scale_string.'.'.rtrim($mantissa_string, '0');
    }
}
