@extends('app')

@section('content')
    
	<p class="pull-right" style="text-align: right;">
		<a href="https://tokenly.com" target="_blank" class="small-tokenly"><img src="{{ asset('img/Tokenly_Logo_BorderlessA_ldpi.png') }}" alt=""></a><br>
        <a href="http://foldingcoin.net" target="_blank"><img src="{{ asset('img/fldc/FLDC-Banner2.png') }}" alt=""  style="width: 200px;"></a>
	</p>	
    <h1>Bitsplit - FLDC edition</h1>
    <div class="row">
        <div class="col col-lg-6">
            <h2>Token Distribution</h2>
            <p>
                Use this service to distribute Counterparty tokens to participating Folding@Home users based on their folding contributions.
            </p>
            <p>
                <a href="{{ route('home') }}" class="btn btn-lg btn-success"><i class="fa fa-rocket"></i> Get Started</a>
            </p>
        </div>
    </div>

@stop


@section('title')
    Token Distributions & Payment Routing
@stop
