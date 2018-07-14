@extends('app')

@section('content')
<div class="content padded">
	<div class="">
		<h1>Distribute Tokens</h1>
        <p>
            Use this tool to distribute Counterparty tokens to participating Folding@Home users based on their folding contributions in the given time period. 
        </p>
		<p>
			A deposit address will be generated for you along with a total amount of tokens + 
			total amount of <em>fuel</em> (bitcoin) it will cost. Fuel can be paid directly, 
			or sourced from your account <em>fuel address</em>. Once confirmed, your tokens will enter
			the distribution process.
		</p>
        <p>
            <strong>Participating folders:</strong> {{ number_format(\App\Models\DailyFolder::countUniqueFolders(true)) }}
        </p>
		<hr>
		<div id="new-distro-form">
			<distro-form></distro-form>
		</div>

		<p class="text-danger">
            <strong>Attention:</strong> Transaction capacity on the Bitcoin network is at high levels of congestion.
            If your distribution is time sensitive at all, please make sure to double check that your miner fee rate
            is set appropriately, otherwise you may be stuck with a several day wait time.<br>
            You can use <a href="https://bitcoinfees.earn.com/" target="_blank">https://bitcoinfees.earn.com/</a> to help
            with estimations, or if unsure you can email <a href="mailto:team@tokenly.com">team@tokenly.com</a> for a recommendation.<br>
        </p>
	</div>
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop

@section('page_scripts')

<script>
	Vue.component('distro-form', {

		template: `
			@include('distribute.partials.form')
		`,
		data() {
			return {
				calculationType: null,
				distributionClass: null,
				useAccountFuel: true,
				customBitcoinNetworkFee: false
			}
		},
		props: {

		},
		methods: {

		},
		computed: {
		},
		created: function(){
		}
	});
</script>

@stop
