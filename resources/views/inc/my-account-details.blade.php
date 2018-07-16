
<div><strong>User:</strong> {{ $user->username }}</div>
<div><strong>BTC Fuel Balance </strong>
    {{ rtrim(rtrim(number_format($dash_info['fuel_balance']/100000000,8),"0"),".") }}
	@if($dash_info['fuel_pending'] > 0)
		({{ rtrim(rtrim(number_format($dash_info['fuel_pending']/100000000,8),"0"),".") }} pending)
	@endif
    <a href="#" data-toggle="tooltip" data-placement="bottom" title="BTC fees for distributions can be automatically funded from your fuel address. This balance cannot be withdrawn."><i class="fa fa-question-circle-o"></i></a>
</div>
<div><strong>BTC Fuel Address:</strong><br>
	<a href="https://blocktrail.com/BTC/address/{{ $dash_info['fuel_address'] }}" target="_blank" style="    word-break: break-all;">{{ $dash_info['fuel_address'] }}</a>
	<?php
		$fuel_token_list = array_keys(Config::get('settings.valid_fuel_tokens'));
	?><br>
	<span class="dynamic-payment-button" data-label="BitSplit Fuel Address" data-address="{{ $dash_info['fuel_address'] }}" data-tokens="{{ join(',', $fuel_token_list) }}"></span>
</div>
<div><strong>Fuel Spent:</strong> {{ rtrim(rtrim(number_format($dash_info['fuel_spent']/100000000,8),"0"),".") }}</div>