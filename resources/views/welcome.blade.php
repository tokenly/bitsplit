@extends('app')

@section('content')
    
    <section id="home-hero" class="welcome-section">
        <div class="centered home-hero-header">
            <header>
                <span>Welcome to</span>
                <span>Merged Folding</span>
            </header>
            <h2>
                <span>Airdrops for</span>
                <span>Altruists</span>
            </h2>
            <div class="welcome-section-divider"></div>
        </div>

        <div class="centered">
            <a class="welcome-cta green" href="{{ route('account.authorize') }}">
                <span>Get Started</span>
            </a>
        </div>
        <div class="home-hero-content">
            <div class="home-hero-content-left">
                
                <p>
                    <span>FoldingCoin, Inc proudly offers the ability for your project to</span>
                    <span style="color: #C61410;"><b>distribute your token to participating <a href="https://foldingathome.org/" target="_blank">FoldingAtHome users</a></b></span>
                    <span>with the Merged Folding platform. Using this tool, you may distribute your token to some or all of the participants based on your own criteria.</span>
                </p>
                
                <p>We currently track contributions from between 1,500 and 2,000 participating altruists each month who could receive your token via Merged Folding.</p>
                
                <p>After reading this page over, please read our Terms and Conditions Page before your first distribution.</p>

            </div>
            <div class="home-hero-content-right">
                <iframe
                    height="315"
                    src="https://www.youtube.com/embed/2GSe4RoEGCo"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </section>

    <section id="about" class="welcome-section">
        <div class="welcome-section-content">
            <div class="centered welcome-section-heading">
                <header>Customized Token Distributions</header>
                <div class="welcome-section-divider light"></div>
            </div>
       
            <p>
            • You can give away tokens proportionally to our Folders based on computational power or give a set amount to each participant regardless of computational work.
            </p>

            <p>
            • You can choose to give away randomly to our Folders, or even choose to give away to only the Folders who have given a certain amount of computational power.
            </p>

            <p>
            • You set the number of tokens you will give away. There is no minimum or maximum requirement for the number of tokens you give away.
            </p>

            <p>   
            • You pick whether to award your tokens to folders active only on a given day or use the entire multi-year Foldingcoin history as your guide. You can give your tokens away for a limited time, or indefinitely. You have options.
            </p>
        </div>
    </section>

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
    Token Distributions
@stop
