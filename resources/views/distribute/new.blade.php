@extends('app')

@section('content')
<div class="content padded">
	<div class="page-information">
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
	</div>
	<div id="new-distro-form">
		<distro-form></distro-form>
	</div>
	@if (env('SHOW_BITCOIN_CONGESTION_WARNING', false))
	<p class="text-danger">
        <strong>Attention:</strong> Transaction capacity on the Bitcoin network is at high levels of congestion.
        If your distribution is time sensitive at all, please make sure to double check that your miner fee rate
        is set appropriately, otherwise you may be stuck with a several day wait time.<br>
        You can use <a href="https://bitcoinfees.earn.com/" target="_blank">https://bitcoinfees.earn.com/</a> to help
        with estimations, or if unsure you can email <a href="mailto:team@tokenly.com">team@tokenly.com</a> for a recommendation.<br>
    </p>
	@endif
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop

@section('page_scripts')

<script>

	var oldTokenName = {!! json_encode(old('asset')) !!};
	var oldTokenAmount = {!! json_encode(old('asset_total')) !!};
	var oldStartDate = {!! json_encode(old('folding_start_date')) !!};
	var oldEndDate = {!! json_encode(old('folding_end_date')) !!};
	var standardBTCNetworkFee = {!! Config::get('settings.miner_satoshi_per_byte') !!};
	var minBTCNetworkFee = {!! Config::get('settings.min_fee_per_byte') !!};
	var maxBTCNetworkFee = {!! Config::get('settings.max_fee_per_byte') !!};

	Vue.component('distro-form', {

		template: `
			@include('distribute.partials.form')
		`,
		data() {
			return {
				tokenName: null,
				tokenAmount: null,
				calculationType: null,
				distributionClass: null,
				useAccountFuel: true,
				offchainDistribution: true,
				customBitcoinNetworkFee: false,
				startDate: null,
				endDate: null,
				minFAHPoints: null,
				amountTopFolders: null,
				showRandomInput: null,
				showTopFoldersInput: null,
				amountRandomFolders: null,
				weightChanceByFAHPoints: null,
				invalidInputWarning: null,
				btcNetworkFee: standardBTCNetworkFee
			}
		},
		props: {

		},
		methods: {
			selectRandomNumber(n) {
				this.amountRandomFolders = n;
				this.showRandomInput = false;
			},
			selectTopFolders(n) {
				this.amountTopFolders = n;
				this.showTopFoldersInput = false;
			},
			checkValid(e) {
				if(this.validConfiguration) {
    				return true;
    			} else {
    				this.invalidInputWarning = true;
    				e.preventDefault();
    			}
			}
		},
		computed: {
			validToken() {
				return (this.tokenName && this.tokenName.length > 2);
			},
			validTokenAmount() {
				return (!isNaN(this.tokenAmount) && this.tokenAmount > 0);
			},
			validDistributionClassConfig() {
				if(this.distributionClass) {
					switch(this.distributionClass) {
						case 'All Folders':
							return true;
							break;
						case 'Minimum FAH points':
							if(this.minFAHPoints) {
								return true;
							} else {
								return false;
							}
							break;
						case 'Top Folders':
							if(this.amountTopFolders) {
								return true;
							} else {
								return false;
							}
							break;
						case 'Random':
							if(this.amountRandomFolders) {
								return true;
							} else {
								return false;
							}
							break;
						case 'unique':
							return true;
							break;
					}
				} else {
					return false;
				}
			},
			validBTCNetworkFee() {
				return (this.btcNetworkFee && this.btcNetworkFee >= minBTCNetworkFee && this.btcNetworkFee <= maxBTCNetworkFee)
			},
			validDates() {
				var q = new Date();
				var m = q.getMonth()+1;
				var d = q.getDay();
				var y = q.getFullYear();

				var _date = new Date(y,m,d);

				_startDate = new Date(this.startDate);
				_endDate = new Date(this.endDate);

				console.log(_endDate <= _date);
				console.log(_startDate <= _date);

				return ((this.startDate && this.endDate) && (_endDate <= _date) && (_startDate <= _date) && (_endDate > _startDate));
			},
			validConfiguration() {
				return (this.validToken && this.validTokenAmount && this.calculationType && this.validDistributionClassConfig && this.startDate && this.endDate && this.validBTCNetworkFee);
			},
			isOfficialDistribution() {
				if (this.validToken) {
					if (this.tokenName == 'FLDC' || this.tokenName == 'TESTFLDC') {
						return true
					}
				}
				return false
			},
		},
		created: function(){
			if(oldTokenName) {
				this.tokenName = oldTokenName;
			}

			if(oldTokenAmount) {
				this.tokenAmount = oldTokenAmount;
			}

			if(oldStartDate) {
				this.startDate = oldStartDate;
			}

			if(oldEndDate) {
				this.endDate = oldEndDate;
			}
		}
	});
</script>

@stop
