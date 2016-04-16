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
);
