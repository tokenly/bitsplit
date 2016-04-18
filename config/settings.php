<?php

return array(
	'max_tx_outputs' => 100, //max amount of new utxos created per tx, e.g for priming
	'fee_levels' => array( //amounts used for fees (change these later)
						'low' => array('miner' => 1000, 'dust' => 1000, 'service' => 1000),
						'medium' => array('miner' => 1000, 'dust' => 1000, 'service' => 1000),
						'high' => array('miner' => 1000, 'dust' => 1000, 'service' => 1000),
						),
	'broadcast_interval' => 0.5, //number of seconds to wait in between tx broadcasts
	'amount_decimals' => 8, //# of decimals to round quantities to (e.g for percent value type distros)
	'min_distribution_addresses' => 3, //no point in creating distributions to less than this amount of addresses
	'min_fuel_confirms' => 2, //how long to wait before crediting fuel
	'min_distribution_confirms' => 1, //how long before accepting distribution deposits
	'valid_fuel_tokens' => array('BTC' => 0, 'TOKENLY' => 6), //things you can use to pay your fuel with. # is in $$
	'miner_fee' => 10000, //standard miner fee for basic transactions
	'miner_satoshi_per_byte' => 50, //satoshis per to pay for miner fees
);
