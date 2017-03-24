<?php

return array(
	'max_tx_outputs' => 150, //max amount of new utxos created per tx, e.g for priming
	'default_dust' => 5430, 
	'broadcast_interval' => 0.5, //number of seconds to wait in between tx broadcasts
	'amount_decimals' => 8, //# of decimals to round quantities to (e.g for percent value type distros)
	'min_distribution_addresses' => 3, //no point in creating distributions to less than this amount of addresses
	'min_fuel_confirms' => 2, //how long to wait before crediting fuel
	'min_distribution_confirms' => 1, //how long before accepting distribution deposits
	'valid_fuel_tokens' => array('BTC' => 0, 'TOKENLY' => 5), //things you can use to pay your fuel with. # is in $$
	'miner_fee' => 15000, //standard miner fee for basic transactions
	'miner_satoshi_per_byte' => 85, //satoshis per to pay for miner fees
	'average_tx_bytes' => 300, //average btc transaction size
	'average_txo_bytes' => 34, //average amount of bytes per additional transaction output
    'tx_input_bytes' => (181 - 32),
    'tx_extra_bytes' => 10,
    'xcp_tx_bytes' => 231,
    'auto_pump_stuck_distros' => true, //set to true to have 1 miner_fee pumped into a distribution if it gets stuck, set to false if this goes out of wack
    'distribute_service_fee' => 2000, //amount of satoshis per address to tack on to their fee for service profit
);
