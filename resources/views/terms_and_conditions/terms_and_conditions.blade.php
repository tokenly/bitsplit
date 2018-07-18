@extends('app')

@section('content')

<div class="content padded document">
	@if($accept_cta)
	<div class="alert-container centered">
		<p class="tac-alert">Please accept our terms and conditions before continuing to use Merged Folding.</p>
	</div>
	@endif
	<div class="document--paper @if($accept_cta) document-scroll @endif">
		<div class="tou-header">
			<h1>Terms and Conditions</h1>
			<p>Last updated: July 17, 2018</p>
			<p>Effective as of July 17, 2018</p>
			<p>Thank you for using Merged Folding</p>
		</div>
		<p>By distributing your token on Merged Folding, you are aware that this does NOT mean FoldingCoin, Inc. (“FoldingCoin”) has endorsed your project. We have made this platform public and as permission less as possible so FoldingCoin does not need to vet your project before you can do distributions. You are not allowed to say to your community that you have a partnership with FoldingCoin, but you can state that you distribute to the FoldingCoin community.</p>

		<p>Because of unknown regulations at this time, by distributing on the platform, you are doing this on behalf of your project, and therefore take on all responsibility of doing proper reporting for your project. FoldingCoin is not responsible for reporting your distributions to governments or regulators. If your project has done a distribution in the past, you must do your own research to determine whether you can do airdrops on the platform. By using this platform, you are agreeing to take on all potential regulatory violations, including indemnifying and holding FoldingCoin harmless.</p>

		<p>FoldingCoin can block your project from distributing at any time if we deem your project to be lacking in development, you are trying to exploit the community with malicious intent, or any other reason that we deem as compromising the FoldingCoin brand or image.</p>

		<p>The Merged Folding platform is free to use, but there are still miner fees associated with sending out tokens on blockchains. FoldingCoin is not responsible for covering these costs on your behalf.</p>

		<p>The platform is still considered in Beta and you are using it at your own risk. Should an issue arise in which you lose funds, distribution amounts are not accurate, or any other unforeseeable bugs occur, FoldingCoin will not be responsible for covering the lost tokens, BTC, or other cryptocurrencies used.</p>

		<p>Your distributions are public information and FoldingCoin has the rights to post your distributions, project logo, project website, and other information collected from MergedFolding.net on our website and other places in which we choose to display the information.</p>

		<p>FoldingCoin will provide assistance and answer questions on how the platform works. FoldingCoin will not hold any funds or distribute funds for you. It is up to your project to hold and distribute your funds; you must use the Merged Folding airdrop system to do this. Please email rross@foldingcoin.net or team@tokenly.com with any questions or concerns.</p>

		<p>Currently the system only supports Counterparty based tokens and does not support other chains. Since this is a first release, our system still requires the BTC dust for Counterparty transactions.  While FoldingCoin is working on removing this requirement, your project must pay the BTC dust for the Counterparty transactions.</p>
	</div>
	@if($accept_cta)
		<terms-of-use-accept></terms-of-use-accept>
	@endif
</div>
@endsection

@section('title')
	Terms and Conditions
@stop

@section('page_scripts')
	
	<script>
		Vue.component('terms-of-use-accept', {
			template: `
				@include('terms_and_conditions.partials.terms_of_use_accept')
			`,
			props: {
			},
			data() {
			  return {
			  	userAccepted: null,
			  	actionPath: '/account/accept_tac/',
			  	acceptPrompt: null
			  }
			},
	    	methods: {
	    		checkAccept(e) {
	    			if(this.userAccepted) {
	    				return true;
	    			} else {
	    				this.acceptPrompt = true;
	    				e.preventDefault();
	    			}
	    		}
	    	},
			computed: {
			}
		});
	</script>

@endsection