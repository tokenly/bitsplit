@extends('app')

@section('content')
    
    <section id="home-hero" class="welcome-section full">
        <div id="home-hero-mask"></div>
        <div class="welcome-section-content hero">
            <div class="home-hero-header">
                <div class="home-hero-content-left">
                    <header>
                        <!-- <span>Welcome to</span> -->
                        <span>Merged Folding</span>
                    </header>
                   <!--  <div class="welcome-section-divider"></div> -->
                    <h2>
                        <span>Airdrops for</span>
                        <span>Altruists</span>
                    </h2>
                    <div class="welcome-section-divider light"></div>
                    <p>
                        <span>FoldingCoin, Inc proudly offers the ability for your project to</span>
                        <span><b>distribute your tokens</b></span>
                        <span>to participating <a class="home-hero-link" href="https://foldingathome.org/" target="_blank">FoldingAtHome</a> users</span>
                        <span>with the Merged Folding platform. Using this tool, you may distribute your token to some or all of the participants based on your own criteria.</span>
                    </p>
                    
                    <p>We currently track contributions from between 1,500 and 2,000 participating altruists each month who could receive your token via Merged Folding.</p>

                    <div class="home-hero-cta">
                        <a class="welcome-cta blue" href="{{ route('account.authorize') }}">
                            <span>Get Started</span>
                        </a>
                    </div>
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
        </div>
        <div class="centered home-hero-scroll">
            <a href="#about">
                <i class="fa fa-arrow-down"></i>
            </a>
        </div>
    </section>

    <section id="about" class="welcome-section">
        <div class="welcome-section-content">
            <div class="centered welcome-section-heading">
                <header>Customized Token Distributions</header>
                <div class="welcome-section-divider"></div>
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

    <section id="" class="welcome-section">
        
        <div class="welcome-section-content">
            <div class="centered welcome-section-heading">
                <header>Why Use Merged Folding?</header>
                <div class="welcome-section-divider"></div>
            </div>
            
            <p>This can be useful for your project for the following reasons:</p> 

            <p>• Help to promote altruistic scientific research (like Protein Simulations) by giving more incentives to those that Fold to increase the overall Folding@Home network at a small cost to you. This will help your companies image as your project will be helping promote people to use computational power toward medical research rather than mining for an altcoin.</p>

            <p>• Do a promotional giveaway of your token to get our community excited about your new product before it’s released. This is a good chance to advertise to our community base with a relatively low cost to perform the distributions.</p>

            <p>• Create a special token that works within your platform that can only be earned via folding. This helps to incentivize people in our community to be more engaged with your project if there is a token they can only earn via folding.</p>

            <p>• Rather than creating your own blockchain requiring people to maintain, you can simply use an already existing mining base that chooses to fold for science instead.</p>

            <p>• If your project is looking for a way to “Airdrop” your token, and you’re not sure who should receive your token, airdropping to those that are using their mining equipment for folding is a great way for your project to help a charity with a good cause at the same time of promoting your own project. This is a win-win situation for our project, yours, and most importantly medical research.</p>

            <p>• FoldingCoin, Inc. provides this service for free, you only need to pay the BTC required to confirm the transactions of your tokens to be sent to our participants.</p>
        </div>

    </section>

    <section id="how-it-works" class="welcome-section">
        <div class="welcome-section-content">
            <div class="welcome-section-heading">
                <header>How does it work?</header>
                <div class="welcome-section-divider"></div>
            </div>
        
            <div class="how-it-works-row">
                <div class="how-it-works-row__panel">
                    <h3 class="step-1">Create an account</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered">
                    <img src="{{ asset('img/signup-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel centered full-screen-only">
                    <img src="{{ asset('img/tokenpass-add-new-address-form.PNG') }}" alt=""/>
                </div>
                <div class="how-it-works-row__panel">
                    <h3 class="step-2">Add Your Wallet Address</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered mobile-only">
                    <img src="{{ asset('img/tokenpass-add-new-address-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel">
                    <h3 class="step-3">Create Your Distribution</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered">
                    <img src="{{ asset('img/distribution-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel centered full-screen-only">
                    <img src="{{ asset('img/distribution-details.PNG') }}" alt=""/>
                </div>
                <div class="how-it-works-row__panel">
                    <h3 class="step-4">Review the Details of Your Distribution</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered mobile-only">
                    <img src="{{ asset('img/distribution-details.PNG') }}" alt=""/>
                </div>
            </div>
        </div>
    </section>

        <section id="" class="welcome-section">
        
        <div class="welcome-section-content">
            <div class="centered welcome-section-heading">
                <header>About Merged Folding</header>
                <div class="welcome-section-divider"></div>
            </div>
            
            <p>This can be useful for your project for the following reasons:</p> 

            <p>• Help to promote altruistic scientific research (like Protein Simulations) by giving more incentives to those that Fold to increase the overall Folding@Home network at a small cost to you. This will help your companies image as your project will be helping promote people to use computational power toward medical research rather than mining for an altcoin.</p>

            <p>• Do a promotional giveaway of your token to get our community excited about your new product before it’s released. This is a good chance to advertise to our community base with a relatively low cost to perform the distributions.</p>

            <p>• Create a special token that works within your platform that can only be earned via folding. This helps to incentivize people in our community to be more engaged with your project if there is a token they can only earn via folding.</p>

            <p>• Rather than creating your own blockchain requiring people to maintain, you can simply use an already existing mining base that chooses to fold for science instead.</p>

            <p>• If your project is looking for a way to “Airdrop” your token, and you’re not sure who should receive your token, airdropping to those that are using their mining equipment for folding is a great way for your project to help a charity with a good cause at the same time of promoting your own project. This is a win-win situation for our project, yours, and most importantly medical research.</p>

            <p>• FoldingCoin, Inc. provides this service for free, you only need to pay the BTC required to confirm the transactions of your tokens to be sent to our participants.</p>
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
