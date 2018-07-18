@if(!$dash_info['distribution_history'] OR count($dash_info['distribution_history']) == 0)
	<div class="blank-state-container centered">
		<p class="blank-state-text">
			<span>You have not made any distributions yet.</span>
		</p>
	</div>
@else
	<p>
		<strong>Count:</strong> <span id="distro-total-count">{{ number_format($dash_info['distribution_count']) }}</span><br>
		<strong>Completed:</strong> <span id="distro-total-completed">{{ number_format($dash_info['distributions_complete']) }}</span><br>
		<strong># Txs:</strong> <span id="distro-total-txs">{{ number_format($dash_info['distribution_txs']) }}</span>
	</p>

	@foreach($dash_info['distribution_history'] as $row)
		@include('distribute.partials.distribution')
	@endforeach
@endif
