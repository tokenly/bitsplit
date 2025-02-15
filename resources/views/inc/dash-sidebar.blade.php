	<div class="col-lg-5 col-lg-offset-1" id="distro-dashboard">
		<?php
		$dash_info = User::getDashInfo();
		?>
		<h3>My Account</h3>
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
		<hr>
		<h3>Distribution History</h3>
		@if(!$dash_info['distribution_history']) OR count($dash_info['distribution_history']) == 0)
			<p>
				You have made no distributions yet.
			</p>
		@else
			<p>
				<strong>Count:</strong> <span id="distro-total-count">{{ number_format($dash_info['distribution_count']) }}</span><br>
				<strong>Completed:</strong> <span id="distro-total-completed">{{ number_format($dash_info['distributions_complete']) }}</span><br>
				<strong># Txs:</strong> <span id="distro-total-txs">{{ number_format($dash_info['distribution_txs']) }}</span>
			</p>
			<table class="table table-bordered table-striped distro-history-table" style="font-size: 12px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Token Total</th>
						<th>Status</th>
						<th>TX</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach($dash_info['distribution_history'] as $row)
						<tr>
							<td>
								<strong>#{{ $row->id }}</strong>
								@if($row->label != '')
									{{ $row->label }}
								@endif
								<br>
								<small>Address: {{ substr($row->deposit_address, 0, 7) }}..</small>
							</td>
							<td>
								{{ rtrim(rtrim(number_format($row->asset_total / 100000000, 8),"0"),".") }}
								{{ $row->asset }}							
							</td>
                            <td class="distro-{{ $row->id }}-status-text">
							<?php
							if($row->complete == 1){
								echo '<span class="text-success">Complete</span>';
							}
							elseif($row->hold == 1){
								echo '<strong>HOLD</strong>';
							}
							else{
								switch($row->stage){
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
							}
							?>
							</td>
							<td id="distro-{{ $row->id }}-table-complete-count-cont">
								<?php
								$num_tx = $row->addressCount();
								$num_complete = $row->countComplete();
								if($num_complete >= $num_tx){
									echo '<i class="fa fa-check text-success" title="Complete"></i> '.number_format($num_tx);
								}
								else{
									echo '<span class="distro-'.$row->id.'-complete-count">'.number_format($num_complete).'</span>/'.number_format($num_tx);
								}
								?>
							</td>
							<td id="distro-{{ $row->id }}-table-actions">
								<a href="{{ route('distribute.details', $row->deposit_address) }}" class="btn btn-info btn-sm" title="View details"><i class="fa fa-info"></i></a>
								<a href="{{ route('distribute.duplicate', $row->deposit_address) }}" class="btn btn-warning btn-sm" title="Duplicate this distribution"><i class="fa fa-clone"></i></a>
								@if($row->complete == 1 OR ($row->asset_received == 0 AND $row->fee_received == 0))
									<a href="{{ route('distribute.delete', $row->id) }}" class="btn btn-sm btn-danger delete" title="Delete"><i class="fa fa-close"></i></a>
								@endif
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>
