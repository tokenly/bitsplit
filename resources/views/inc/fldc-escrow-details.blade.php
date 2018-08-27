<div>
    <strong>FLDC Escrow Balances </strong><br>
    @foreach ($dash_info['escrow_pending_balances'] as $token => $pending_balance)
    	<?php $balance = isset($dash_info['escrow_balances'][$token]) ? $dash_info['escrow_balances'][$token]->getFloatValue() : 0 ?>
    	&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $token }}:</strong>
        {{ rtrim(rtrim(number_format($balance, 8),"0"),".") }} 
    	@if($pending_balance->getFloatValue() != $balance) 
    		({{ rtrim(rtrim(number_format($pending_balance->getFloatValue(), 8),"0"),".") }} pending) 
    	@endif
        <br>
    @endforeach
</div>

<div>
    <strong>FLDC Escrow Address:</strong><br>
    <a href="https://{{ App\Libraries\Substation\Substation::chain() == 'counterpartyTestnet' ? 'testnet.' : '' }}xchain.io/address/{{ $dash_info['escrow_address'] }}" target="_blank" style="    word-break: break-all;">{{ $dash_info['escrow_address'] }}</a>
</div>

<div>
	<strong>FLDC Allocated:</strong>
    {{ formattedTokenQuantity($dash_info['allocated_fldc_balance']) }} 
</div>