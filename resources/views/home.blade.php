@extends('app')

@section('content')

<?php
	$dash_info = User::getDashInfo();
?>

<div class="content padded">
	<div class="page-information">
		<h1>Dashboard</h1>
	</div>
	<a href="{{ route('distribute.new') }}">Create a New Distribution</a>
	<div>
		<div class="dashboard__data">
			<div class="dashboard__data__entry">
				<div class="dashboard__data__entry__icon"></div>
				<div class="dashboard__data__entry__data">
					
				</div>
			</div>
		</div>
		<div class="dashboard__data">
			
		</div>
		<div class="dashboard__data">
			
		</div>

		<ul>
			<li><strong>User:</strong> {{ $user->username }}</li>
			<li><strong>BTC Fuel Balance </strong>
                {{ rtrim(rtrim(number_format($dash_info['fuel_balance']/100000000,8),"0"),".") }}
				@if($dash_info['fuel_pending'] > 0)
					({{ rtrim(rtrim(number_format($dash_info['fuel_pending']/100000000,8),"0"),".") }} pending)
				@endif
                <a href="#" data-toggle="tooltip" data-placement="bottom" title="BTC fees for distributions can be automatically funded from your fuel address. This balance cannot be withdrawn."><i class="fa fa-question-circle-o"></i></a>
			</li>
			<li><strong>BTC Fuel Address:</strong><br>
				<a href="https://blocktrail.com/BTC/address/{{ $dash_info['fuel_address'] }}" target="_blank">{{ $dash_info['fuel_address'] }}</a>
				<?php
					$fuel_token_list = array_keys(Config::get('settings.valid_fuel_tokens'));
				?><br>
				<span class="dynamic-payment-button" data-label="BitSplit Fuel Address" data-address="{{ $dash_info['fuel_address'] }}" data-tokens="{{ join(',', $fuel_token_list) }}"></span>
			</li>
			<li><strong>Fuel Spent:</strong> {{ rtrim(rtrim(number_format($dash_info['fuel_spent']/100000000,8),"0"),".") }}</li>
		</ul>
	</div>
	@include('inc.dash-sidebar')
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop
