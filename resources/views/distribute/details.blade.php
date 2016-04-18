@extends('app')

@section('content')
<div class="row">
	<div class="col-lg-6">
		<h2>Distribution #{{ $distro->id }}</h2>
		@if(trim($distro->label) != '')
			<h3>{{ $distro->label }}</h3>
		@endif
		<p>
			<a href="{{ route('home') }}"><i class="fa fa-mail-reply"></i> Go Back</a>
		</p>
		<h4>Details &amp; Status</h4>
		<form action="{{ route('distribute.details.update', $distro->deposit_address) }}" method="post" enctype="multipart/form-data">
			<input type="hidden" name="_token" value="{{ csrf_token() }}" />		
		<ul class="distro-info">
			<li>
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">Label:</span>
						<input type="text" name="label" id="label" class="form-control" value="{{ $distro->label }}" placeholder="(optional)" />
					</div>
				</div>
			</li>
			<li>
				<strong>Token:</strong> {{ $distro->asset }}
			</li>
			<li>
				<strong>Amount to Distribute:</strong> {{ rtrim(rtrim(number_format($distro->asset_total / 100000000, 8),"0"),".") }} {{ $distro->asset }}
			</li>
			<li>
				<strong>BTC Fuel Cost:</strong> {{ rtrim(rtrim(number_format($distro->fee_total / 100000000, 8),"0"),".") }} BTC
			</li>
			<li>
				<strong>Deposit Address:</strong> 
				<span class="dynamic-payment-button" data-amount="{{ round($distro->asset_total / 100000000, 8) }}" data-address="{{ $distro->deposit_address }}" data-tokens="{{ $distro->asset }},BTC"></span>
				{{ $distro->deposit_address }}
			</li>
			<li>
				<strong>Status:</strong>
				<?php
				if($distro->complete == 1){
					echo '<span class="text-success">COMPLETE</span>';
				}
				else{
					switch($distro->stage){
						case 0:
							echo '<span class="text-warning">Initializing</span>';
							break;
						case 1:
							echo '<span class="text-warning">Collecting Tokens</span>';
							break;
						case 2:
							echo '<span class="text-warning">Collecting Fuel</span>';
							break;
						case 3:
							echo '<span class="text-info">Priming Inputs</span>';
							break;
						case 4:
							echo '<span class="text-info">Preparing Transactions</span>';
							break;
						case 5:
							echo '<span class="text-info">Signing Transactions</span>';
							break;
						case 6:
							echo '<span class="text-info">Broadcasting Transactions</span>';
							break;
						case 7:
							echo '<span class="text-info">Confirming Broadcasts</span>';
							break;
						case 8:
							echo '<span class="text-success">Performing Cleanup</span>';
							break;
						case 9:
							echo '<span class="text-success">Confirming Cleanup</span>';
							break;
						default:
							echo '(unknown)';
							break;
					
					}
					if($distro->hold == 1){
						echo ' <strong class="text-danger">HOLD</strong>';
					}
				}
				?>
			</li>
			@if(trim($distro->stage_message) != '')
				<li><strong class="text-info">Status Message:</strong> {{ $distro->stage_message }}</li>
			@endif
			<li><strong>Date Created:</strong> {{ date('F j\, Y \a\t g:i A', strtotime($distro->created_at)) }} </li>
			<li><strong>Last Updated:</strong> {{ date('F j\, Y \a\t g:i A', strtotime($distro->updated_at)) }}</li>
			@if($distro->complete == 1)
				<li><strong>Completed:</strong> {{ date('F j\, Y \a\t g:i A', strtotime($distro->completed_at)) }}</li>
			@endif
			@if($distro->complete == 0)
			<li>
				<div class="checkbox">
					<label><input type="checkbox" name="hold" id="hold" value="1" style="margin-top: 2px;" @if($distro->hold == 1) checked="checked" @endif /> Pause Distribution</label>
				</div>
			</li>
			@endif
		</ul>
			<div class="form-group form-submit">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Update</button>
			</div>
		</form>
		<hr>
		<h4>Transactions ({{ $num_complete }} / {{ $address_count }} complete)</h4>
		<p>
			<strong>Received:</strong><br>
			{{ rtrim(rtrim(number_format($distro->asset_received / 100000000, 8),"0"),".") }} / {{ rtrim(rtrim(number_format($distro->asset_total / 100000000, 8),"0"),".") }} {{ $distro->asset }}
			<br>
			{{ rtrim(rtrim(number_format($distro->fee_received / 100000000, 8),"0"),".") }} / {{ rtrim(rtrim(number_format($distro->fee_total / 100000000, 8),"0"),".") }} BTC
		</p>
		@if(!$address_list OR count($address_list) == 0)
			<p>
				No distribution addresses found.
			</p>
		@else
			<table class="table table-bordered table-striped">
				<thead>
					<th>Address</th>
					<th>Quantity</th>
					<th>TX</th>
				</thead>
				<tbody>
					@foreach($address_list as $row)
						<tr>
							<td>{{ $row->destination }}</td>
							<td>{{ rtrim(rtrim(number_format($row->quantity / 100000000, 8),"0"),".") }}</td>
							<td>
								
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>
	@include('inc.dash-sidebar')
</div>
@stop

@section('title')
	Distribution #{{ $distro->id }} {{ $distro->deposit_address }}
@stop
