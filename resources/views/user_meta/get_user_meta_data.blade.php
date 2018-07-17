@extends('app')

@section('content')

<div class="content padded document">
	<div class="alert-container centered" style="padding: 10px 0px;">
		<p class="tac-alert">Please complete your user profile before continuing to use Merged Folding</p>
	</div>
	<div class="document--paper">
		<user-meta-form></user-meta-form>
	</div>
</div>
@endsection

@section('title')
	Please complete your profile
@stop

@section('page_scripts')
	
	<script>
		Vue.component('user-meta-form', {
			template: `
				@include('user_meta.partials.form')
			`,
			props: {
			},
			data() {
			  return {
			  	firstName: '',
			  	lastName: '',
				companyName: '',
				website: '',
				email: '',
				phoneNumber: '',
				companyAddress: '',
				tokenName: '',
				tokenDescription: '',
				tokenExchangesListed: [],
				isListed: false,
				exchanges: [
					'Bittrex',
					'Bitfinex',
					'Binance',
					'Kraken',
					'Ethex'
				]
			  }
			},
	    	methods: {
	    		toggleListing(exchange) {
	    			_arrayIndex = this.tokenExchangesListed.indexOf(exchange);
	    			if(_arrayIndex > -1) {
	    				this.tokenExchangesListed.splice(_arrayIndex, 1);
	    			} else {
	    				this.tokenExchangesListed.push(exchange);
	    			}
	    		}
	    	},
			computed: {
				validFirstName() {
					return (this.firstName && this.firstName.length > 1);
				},
				validLastName() {
					return (this.lastName && this.lastName.length > 1);
				},
				validEmail() {
					return (this.email && this.email.length > 1);
				},
				validTokenName() {
					return (this.tokenName && this.tokenName.length > 1);
				},
				validTokenDescription() {
					return(this.tokenDescription && this.tokenDescription.length > 25);
				},
				formIsValid() {
					return (this.validFirstName && this.validLastName && this.validEmail && this.validTokenName && this.validTokenDescription);
				}
			}
		});
	</script>

@endsection