<?php

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

function xchain()
{
	$client = new \Tokenly\XChainClient\Client(
				env('XCHAIN_CONNECTION_URL'),
				env('XCHAIN_API_TOKEN'),
				env('XCHAIN_API_KEY'));
	return $client;
}
