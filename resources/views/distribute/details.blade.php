@extends('app')

@section('content')
<div class="row">
	<div class="col-lg-6" id="distro-details" data-address="{{ $distro->deposit_address }}">
		<h2>Distribution #{{ $distro->id }}</h2>
		@if(trim($distro->label) != '')
			<h3>{{ $distro->label }}</h3>
		@endif
		<p>
			@if (Request::url() === url()->previous() AND $user)
				<a href="{{ route('home') }}"><i class="fa fa-mail-reply"></i> Go Back</a>
			@elseif(Request::url() === url()->previous() AND !$user)
				<a href="{{ route('distribute.history') }}"><i class="fa fa-mail-reply"></i> Go Back</a>
			@else
				<a href="{{ url()->previous() }}"><i class="fa fa-mail-reply"></i> Go Back</a>
			@endif
		</p>
		<h4>Details &amp; Status</h4>
		<form action="{{ route('distribute.details.update', $distro->deposit_address) }}" method="post" enctype="multipart/form-data">
			<input type="hidden" name="_token" value="{{ csrf_token() }}" />
		<ul class="distro-info">
			<li>
				@if($user && $user->id === $distro->user_id)
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Label:</span>
							<input type="text" name="label" id="label" class="form-control" value="{{ $distro->label }}" placeholder="(optional)" />
						</div>
					</div>
				@endif
			</li>
			<li>
				<strong>Token:</strong> <a href="https://xchain.io/asset/{{ $distro->asset }}" target="_blank">{{ $distro->asset }}</a>
			</li>
			<li>
				<strong>Amount to Distribute:</strong> {{ rtrim(rtrim(number_format($distro->asset_total / 100000000, 8),"0"),".") }} {{ $distro->asset }}
			</li>
			<li>
				<strong>BTC Fuel Cost:</strong> {{ rtrim(rtrim(number_format($distro->fee_total / 100000000, 8),"0"),".") }} BTC
			</li>
			<li>
				<strong>BTC Dust Size:</strong> {{ rtrim(rtrim(number_format($distro->btc_dust / 100000000, 8),"0"),".") }} BTC
			</li>
			<li>
				<strong>Total FAH points:</strong> {{ $distro->fah_points }}
			</li>
			<li>
				<strong>Average FAH points per folder:</strong> {{ $distro->average_points }}
			</li>
			<li>
				<strong>Amount of tokens per FAH point:</strong> {{ $distro->tokens_per_point }}
			</li>
			@if($distro->fee_rate != null)
			<li>
				<strong>Miner Fee Rate:</strong> {{ $distro->fee_rate }} satoshis per byte
			</li>
            @endif
			<li>
				<strong>Deposit Address:</strong>
				<a href="https://blocktrail.com/BTC/address/{{ $distro->deposit_address }}" target="_blank">{{ $distro->deposit_address }}</a>
				<span class="dynamic-payment-button" data-label="BitSplit Distribution #{{ $distro->id }} @if(trim($distro->label) != '') '{{ $distro->label }}' @endif" data-amount="{{ round($distro->asset_total / 100000000, 8) }}" data-address="{{ $distro->deposit_address }}" data-tokens="{{ $distro->asset }}"></span>
			</li>
			<li>
				<strong>Status:</strong>
                <span class="distro-{{ $distro->id }}-status-text">
				<?php
				if($distro->complete == 1){
					echo '<span class="text-success">Complete</span>';
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
							echo '<span class="text-info">Broadcasting Transactions</span>';
							break;
						case 6:
							echo '<span class="text-info">Confirming Broadcasts</span>';
							break;
						case 7:
							echo '<span class="text-success">Performing Cleanup</span>';
							break;
						case 8:
							echo '<span class="text-success">Finalizing Cleanup</span>';
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
                </span>
			</li>
			@if(trim($distro->stage_message) != '')
				<li class="status-message-cont"><strong class="text-info">Status Message:</strong> <span id="distro-{{ $distro->id }}-stage-message">{{ $distro->stage_message }}</span></li>
			@else
                <li class="status-message-cont" style="display: none;"><strong class="text-info">Status Message:</strong> <span id="distro-{{ $distro->id }}-stage-message"></span></li>
            @endif
			@if(!empty($distro->distribution_class))
				<li><strong>Distribution Class:</strong> {{ $distro->distribution_class }} </li>
			@endif
			@if(!empty($distro->calculation_type))
				<li><strong>Calculation Type:</strong> {{ $distro->calculation_type }} </li>
			@endif
			<li><strong>Date Created:</strong> {{ date('F j\, Y \a\t g:i A', strtotime($distro->created_at)) }} </li>
			<li><strong>Last Updated:</strong> <span id="distro-{{ $distro->id }}-last-update">{{ date('F j\, Y \a\t g:i A', strtotime($distro->updated_at)) }}</span></li>
			<li><strong>Folding Start Date:</strong> <span id="distro-{{ $distro->id }}-last-update">{{ date('F j\, Y', strtotime($distro->folding_start_date)) }}</span></li>
			<li><strong>Folding End Date:</strong> <span id="distro-{{ $distro->id }}-last-update">{{ date('F j\, Y', strtotime($distro->folding_end_date)) }}</span></li>
			@if($distro->complete == 1)
				<li id="distro-complete-cont"><strong>Completed:</strong> <span id="distro-{{ $distro->id }}-complete-date">{{ date('F j\, Y \a\t g:i A', strtotime($distro->completed_at)) }}</span></li>
			@else
				<li id="distro-complete-cont" style="display: none;"><strong>Completed:</strong> <span id="distro-{{ $distro->id }}-complete-date"></span></li> 
            @endif
			@if($distro->complete == 0)
			<li id="distro-hold-input-cont">
				<div class="checkbox">
					<label><input type="checkbox" name="hold" id="hold" value="1" style="margin-top: 2px;" @if($distro->hold == 1) checked="checked" @endif /> Pause Distribution</label>
				</div>
			</li>
			@endif
		</ul>
			@if($user && $distro->user_id === $user->id)
				<div class="form-group form-submit">
					<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Update</button>
				</div>
			@endif
		</form>
		<hr>
		<h4>Transactions (<span class="distro-{{ $distro->id }}-complete-count">{{ $num_complete }}</span> / {{ $address_count }} complete)</h4>
		<p>
			<strong>Received:</strong><br>
			<span id="distro-{{ $distro->id }}-token-received">{{ rtrim(rtrim(number_format($distro->asset_received / 100000000, 8),"0"),".") }}</span> / {{ rtrim(rtrim(number_format($distro->asset_total / 100000000, 8),"0"),".") }} {{ $distro->asset }}
			<br>
			<span id="distro-{{ $distro->id }}-fee-received">{{ rtrim(rtrim(number_format($distro->fee_received / 100000000, 8),"0"),".") }}</span> / {{ rtrim(rtrim(number_format($distro->fee_total / 100000000, 8),"0"),".") }} BTC
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
					<th>F@H Points</th>
					<th>TX</th>
				</thead>
				<tbody>
					@foreach($address_list as $row)
						<tr>
							<td>
                                <a href="https://xchain.io/address/{{ $row->destination }}" target="_blank">{{ $row->destination }}</a>
                                @if($row->tokenpass_user)
                                   <br> ({{ $row->tokenpass_user }})
                                @endif
                            </td>
							<td>{{ rtrim(rtrim(number_format($row->quantity / 100000000, 8),"0"),".") }}</td>
							<td>{{ $row->folding_credit }}</td>
							<td id="distro-tx-{{ $row->id }}-status">
								@if($row->confirmed == 1)
									<a href="https://blocktrail.com/BTC/tx/{{ $row->txid }}" target="_blank" title="View complete transaction"><i class="fa fa-check text-success"></i></a>
								@elseif(trim($row->txid) != '')
									<a href="https://blocktrail.com/BTC/tx/{{ $row->txid }}" target="_blank" title="View transaction (in progress)"><i class="fa fa-spinner fa-spin"></i></a>
								@elseif(trim($row->utxo) != '')
									<i class="fa fa-cog fa-spin" title="Preparing transaction"></i>
								@else
									<i class="fa fa-cog" title="Awaiting fuel availability" style="color: #ccc;"></i>
								@endif
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>
	@if($user)
		@include('inc.dash-sidebar')
	@endif
</div>
@stop

@section('title')
	Distribution #{{ $distro->id }} {{ $distro->deposit_address }}
@stop
