	<div class="col-lg-5 col-lg-offset-1">
		<?php
		$dash_info = User::getDashInfo();
		?>
		<h3>My Account</h3>
		<ul>
			<li><strong>User:</strong> {{ $user->username }}</li>
			<li><strong>BTC Fuel Balance:</strong> {{ rtrim(rtrim(number_format($dash_info['fuel_balance'],8),"0"),".") }}</li>
			<li><strong>BTC Fuel Address:</strong> <a href="https://blocktrail.com/BTC/address/{{ $dash_info['fuel_address'] }}" target="_blank">{{ $dash_info['fuel_address'] }}</a></li>
			<li><strong>Fuel Spent:</strong> {{ rtrim(rtrim(number_format($dash_info['fuel_spent'],8),"0"),".") }}</li>
		</ul>
		<hr>
		<h3>Distribution History</h3>
		@if(!$dash_info['distribution_history']) OR count($dash_info['distribution_history']) == 0)
			<p>
				You have made no distributions yet.
			</p>
		@else
			<p>
				<strong>Count:</strong> {{ number_format($dash_info['distribution_count']) }}<br>
				<strong>Completed:</strong> {{ number_format($dash_info['distributions_complete']) }}<br>
				<strong># Txs:</strong> {{ number_format($dash_info['distribution_txs']) }}
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
							<td>
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
										echo '<span class="text-warning">Fueling Address</span>';
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
							}
							?>
							</td>
							<td>
								<?php
								$num_tx = $row->addressCount();
								$num_complete = $row->countComplete();
								if($num_complete >= $num_tx){
									echo '<i class="fa fa-check text-success" title="Complete"></i> '.number_format($num_tx);
								}
								else{
									echo number_format($num_complete).'/'.number_format($num_tx);
								}
								?>
							</td>
							<td>
								<a href="{{ route('distribute.details', $row->deposit_address) }}" class="btn btn-info btn-sm" title="View details"><i class="fa fa-info"></i></a>
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
