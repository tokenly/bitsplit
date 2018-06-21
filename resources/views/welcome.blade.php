@extends('app')

@section('content')
    
	<p class="pull-right">
		<a href="https://tokenly.com" target="_blank" class="small-tokenly"><img src="{{ asset('img/Tokenly_Logo_BorderlessA_ldpi.png') }}" alt=""></a>
	</p>	
    <h1>Bitsplit</h1>
    <div class="row">
        <div class="col col-lg-6">
            <h2>Token Distribution</h2>
            <p>
                Ever need to get your crypto tokens into the hands of 100's or 1000's of users,
                without spending your life manually sending each transaction from your wallet?<br>
                Enter the token distributor, a forwarding service which allows
                you to make a single transaction, walk away and receive an email notification
                when your entire distribution list has received their tokens. 
            </p>
            <p>
                <a href="{{ route('home') }}" class="btn btn-lg btn-success"><i class="fa fa-rocket"></i> Get Started</a>
            </p>
        </div>
    </div>

@stop


@section('title')
    Token Distributions
@stop
