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
        var _token = "{{ csrf_token()  }}"
        var userMetaSubmitRoute = {!! json_encode(route('account.complete')) !!};
        var fields = {!! json_encode($fields) !!};
        var home_route = "{{ route('home') }}";
		Vue.component('user-meta-form', {
			template: `
				@include('user_meta.partials.form')
			`,
			props: {
			},
			data() {
			  return {
			  	actionPath: userMetaSubmitRoute,
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
				],
                inputWarning: null,
				fields: fields,
				errors: {}
			  }
			},
	    	methods: {
			    submit: function () {
                    this.errors = {};
                    this.$http.post('/account/complete', {
                        _token: _token,
                        fields: this.fields,
                    }).then(response => {
                        window.location.replace(home_route);
                    }, response => {
                        let errors = response.body.data;
                        for (let key in errors) {
                            if (!Object.prototype.hasOwnProperty.call(errors, key)) {
                                continue;
                            }
							this.$set(this.errors, key, errors[key]);
                        }
                    });
				},
			    checkCondition: function (field) {
			        if(!field.condition) {
			            return true;
					}
					for (let i=0;i<this.fields.length;++i) {
			            if(this.fields[i].id != field.condition.field_to_compare_id) {
			                continue;
						}
						return this.fields[i].value == field.condition.value;
					}
			        return true;
				},
			    toggle: function (field, val) {
			        for (let i = 0; i < this.fields.length; ++i) {
			            if(this.fields[i].name === field.name) {
                    		this.$set(this.fields[i], 'value', val)
						}
					}

                },
	    		toggleListing: function (field, value) {
			        for(let i=0;i<this.fields.length;++i) {
			            if(this.fields[i].name === field.name) {
			                if(!this.fields[i].value) {
                                this.$set(this.fields[i], 'value', [value]);
							} else if(this.fields[i].value.indexOf(value) > -1) {
                                let index = this.fields[i].value.indexOf(value);
								this.fields[i].value.splice(index, 1);
							} else {
                                this.fields[i].value.push(value);
							}
						}
					}
	    		},
	    		checkIfValid(e) {
			        return true;
	    			if(this.formIsValid) {
	    				return true;
	    			} else {
	    				this.inputWarning = true;
	    				e.preventDefault();
	    			}
	    		}
	    	},
			computed: {
				formIsValid() {
				    return true;
				}
			}
		});
	</script>

@endsection