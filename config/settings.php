<?php

return array(
	'max_tx_outputs' => intval(env('MAX_TX_OUTPUTS', '150')), //max amount of new utxos created per tx, e.g for priming
	'amount_decimals' => 8, //# of decimals to round quantities to (e.g for percent value type distros)
	'min_distribution_addresses' => 3, //no point in creating distributions to less than this amount of addresses
	'min_fuel_confirms' => 2, //how long to wait before crediting fuel
	'min_distribution_confirms' => 2, //how long before accepting distribution deposits
    // 'valid_fuel_tokens' => array('BTC' => 0, 'TOKENLY' => 5), //things you can use to pay your fuel with. # is in $$
	'valid_fuel_tokens' => ['BTC' => 0,], //things you can use to pay your fuel with. # is in $$
	'miner_fee' => 15000, //standard miner fee for basic transactions
	'miner_satoshi_per_byte' => intval(env('FEE_SATOSHI_PER_BYTE', '25')), //satoshis per to pay for miner fees
    'min_fee_per_byte' => 5,
    'max_fee_per_byte' => 600,
	'average_tx_bytes' => 300, //average btc transaction size
	'average_txo_bytes' => 34, //average amount of bytes per additional transaction output
    'tx_input_bytes' => (181 - 32),
    'tx_extra_bytes' => 10,
    'xcp_tx_bytes' => 214, // CIP-10 with no dust and no change output
    'auto_pump_stuck_distros' => false, //set to true to have 1 miner_fee pumped into a distribution if it gets stuck, set to false if this goes out of wack
    'distribute_service_fee' => 0, //amount of satoshis per address to tack on to their fee for service profit
    'official_fldc_email' => env('FLDC_EMAIL', 'foldingcoin.net@gmail.com'), //Email of the official fldc account
    'signup_field_types' => ['Text', 'Textarea', 'Toggle', 'Checkbox', 'Toggle'], //Email of the official fldc account
);
